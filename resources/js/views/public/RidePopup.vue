<template>
    <div data-testid="ride-popup" class="fixed inset-0 z-[1500] bg-black/50 flex items-end md:items-center justify-center" @click.self="emit('close')">
        <div
            class="bg-white border-l-4 rounded-t-2xl md:rounded-xl w-full md:max-w-md max-h-[90vh] overflow-hidden"
            :style="{ borderLeftColor: ride.type === 'offer' ? 'var(--color-offer)' : 'var(--color-request)' }"
        >
          <div class="max-h-[90vh] overflow-y-auto">

            <!-- Header: close button -->
            <div class="flex justify-end p-3 pb-0">
                <button class="text-gray-400 hover:text-gray-600" :aria-label="t('ride.close')" @click="emit('close')"><X class="w-5 h-5" :stroke-width="2" /></button>
            </div>

            <div class="px-5 pb-6 space-y-4">

                <!-- Seats badge + type -->
                <div class="flex items-center gap-2">
                    <Badge :variant="ride.type === 'offer' ? 'offer' : 'request'">
                        <span>{{ ride.seats }}</span>
                        <span>{{ t(ride.type === 'offer' ? 'ride.seats_available' : 'ride.seats_needed') }}</span>
                    </Badge>
                    <Badge v-if="isBooked" variant="warning">{{ t('ride.booked') }}</Badge>
                    <Badge
                        v-if="ride.direction !== 'both-ways'"
                        variant="warning"
                    >{{ t('ride.' + (ride.direction === 'outbound-only' ? 'outbound_only' : 'return_only')) }}</Badge>
                </div>

                <!-- Route: dates + location -->
                <div class="rounded-lg border border-gray-200 bg-gray-50/60 p-3 space-y-3">
                    <h3 class="flex items-center gap-1.5 font-semibold text-sm text-gray-700">
                        <MapPin class="w-4 h-4" :stroke-width="2" aria-hidden="true" /> {{ t('ride.route_heading') }}
                    </h3>

                    <!-- Dates -->
                    <div class="space-y-1">
                        <div v-if="hasOutbound" class="flex items-start gap-2 text-sm">
                            <ArrowRight class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" :stroke-width="2" />
                            <div>
                                <span class="font-medium">{{ t('ride.outbound_label') }}</span>
                                <span class="ml-2 text-gray-600">{{ fmtDateTime(ride.outbound_at) }}</span>
                                <span v-if="outboundWarning" class="ml-2 text-amber-600 text-xs inline-flex items-center gap-1">
                                    <TriangleAlert class="w-3 h-3" :stroke-width="2" /> {{ outboundRelLabel }}
                                </span>
                            </div>
                        </div>
                        <div v-if="hasReturn" class="flex items-start gap-2 text-sm">
                            <ArrowLeft class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" :stroke-width="2" />
                            <div>
                                <span class="font-medium">{{ t('ride.return_label') }}</span>
                                <span class="ml-2 text-gray-600">{{ fmtDateTime(ride.return_at) }}</span>
                                <span v-if="returnWarning" class="ml-2 text-amber-600 text-xs inline-flex items-center gap-1">
                                    <TriangleAlert class="w-3 h-3" :stroke-width="2" /> {{ returnRelLabel }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Departure / destination label + address -->
                    <div class="flex items-start gap-2 text-sm">
                        <MapPin class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" :stroke-width="2" />
                        <div>
                            <span class="font-medium">
                                {{ ride.direction === 'return-only' ? t('ride.destination_label') : t('ride.departure_label') }}
                            </span>
                            <span class="ml-1 text-gray-600">{{ formatLocation(ride.location, event.location, { allowPostal: true }) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Info text -->
                <div v-if="ride.info" class="rounded-lg border border-gray-200 bg-gray-50/60 p-3 space-y-1">
                    <h3 class="flex items-center gap-1.5 font-semibold text-sm text-gray-700">
                        <MessageSquare class="w-4 h-4" :stroke-width="2" aria-hidden="true" /> {{ t('ride.info_label') }}
                    </h3>
                    <p class="text-sm text-gray-600 leading-snug">{{ ride.info }}</p>
                </div>

                <!-- Contact section -->
                <div class="rounded-lg border border-gray-200 bg-gray-50/60 p-3 space-y-3">
                    <h3 class="flex items-center gap-1.5 font-semibold text-sm text-gray-700">
                        <User class="w-4 h-4" :stroke-width="2" aria-hidden="true" /> {{ t('ride.contact_heading') }}
                    </h3>

                    <div class="space-y-1">
                        <p class="font-medium text-gray-800">{{ ride.name }}</p>
                        <a v-if="showEmail" :href="`mailto:${ride.email}`" class="flex items-center gap-1.5 text-sm text-gray-600 hover:text-gray-900">
                            <Mail class="w-3.5 h-3.5" :stroke-width="2" /> {{ ride.email }}
                        </a>
                        <a v-if="showPhone" :href="`tel:${ride.phone}`" class="flex items-center gap-1.5 text-sm text-gray-600 hover:text-gray-900">
                            <Phone class="w-3.5 h-3.5" :stroke-width="2" /> {{ ride.phone }}
                        </a>
                    </div>

                    <!-- Contact deep-link buttons -->
                    <div class="flex flex-wrap gap-2">
                        <a
                            v-for="btn in contactButtons"
                            :key="btn.method"
                            :href="btn.href"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors"
                        >
                            <span class="shrink-0 inline-flex" aria-hidden="true">
                                <BrandIcon v-if="contactIcons[btn.method].kind === 'brand'" :icon="contactIcons[btn.method].icon" class="w-3.5 h-3.5" />
                                <component v-else :is="contactIcons[btn.method].component" class="w-3.5 h-3.5" :stroke-width="2" />
                            </span>
                            {{ btn.label }}
                        </a>
                    </div>
                </div>

                <!-- Edit / delete links (if token in localStorage) -->
                <div v-if="editToken" class="border-t pt-3 flex gap-4 text-xs text-gray-400">
                    <RouterLink
                        :to="{ name: 'ride.edit', params: { slug: event.slug, id: ride.id }, query: { token: editToken } }"
                        class="hover:text-gray-600 underline"
                    >Bearbeiten</RouterLink>
                    <RouterLink
                        :to="{ name: 'ride.delete', params: { slug: event.slug, id: ride.id }, query: { token: editToken } }"
                        class="hover:text-red-500 underline"
                    >Löschen</RouterLink>
                </div>

                <!-- Manage icons for admin / event creator -->
                <div v-if="manageable" class="border-t pt-3 flex justify-end gap-3 text-gray-400">
                    <button
                        type="button"
                        class="hover:text-gray-700"
                        :title="t('ride.edit')"
                        :aria-label="t('ride.edit')"
                        @click="emit('edit')"
                    ><Pencil class="w-4 h-4" :stroke-width="2" /></button>
                    <button
                        type="button"
                        class="hover:text-red-500"
                        :title="t('ride.delete')"
                        :aria-label="t('ride.delete')"
                        @click="emit('delete')"
                    ><Trash2 class="w-4 h-4" :stroke-width="2" /></button>
                </div>

            </div>
          </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { X, ArrowRight, ArrowLeft, TriangleAlert, Mail, Phone, MapPin, User, Pencil, Trash2, MessageSquare } from '@lucide/vue';
import BrandIcon from '@/components/BrandIcon.vue';
import Badge from '@/components/Badge.vue';
import { contactIcons } from '@/constants/contactIcons';
import { useEscapeKey } from '@/composables/useEscapeKey';
import { formatLocation } from '@/utils/formatLocation';

const props = defineProps({
    ride:       { type: Object, required: true },
    event:      { type: Object, required: true },
    manageable: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'edit', 'delete']);
const { t, locale } = useI18n();

useEscapeKey(() => emit('close'));

const editToken   = computed(() => localStorage.getItem(`ride_token_${props.ride.id}`));
const isBooked    = computed(() => props.ride.type === 'offer' && props.ride.seats === 0);
const hasOutbound = computed(() => ['both-ways', 'outbound-only'].includes(props.ride.direction));
const hasReturn   = computed(() => ['both-ways', 'return-only'].includes(props.ride.direction));
const showEmail   = computed(() => props.ride.contact_methods?.includes('email'));
const showPhone   = computed(() => !!props.ride.phone);

const dtFmt = computed(() => new Intl.DateTimeFormat(locale.value, { dateStyle: 'medium', timeStyle: 'short', timeZone: 'UTC' }));
function fmtDateTime(iso) { return iso ? dtFmt.value.format(new Date(iso)) : ''; }

function dayDiff(iso, anchor) {
    const a = new Date(iso.substring(0, 10) + 'T00:00:00Z');
    const b = new Date(anchor.substring(0, 10) + 'T00:00:00Z');
    return Math.round((a - b) / 86400000);
}

const outboundDiff    = computed(() => props.ride.outbound_at && props.event?.start_at ? dayDiff(props.ride.outbound_at, props.event.start_at) : 0);
const returnDiff      = computed(() => props.ride.return_at   && props.event?.end_at   ? dayDiff(props.ride.return_at,   props.event.end_at)   : 0);
const outboundWarning = computed(() => outboundDiff.value !== 0);
const returnWarning   = computed(() => returnDiff.value !== 0);

const outboundRelLabel = computed(() => {
    const d = outboundDiff.value;
    if (d === 0) return t('ride.on_start_day');
    return t(d < 0 ? 'ride.days_before_start' : 'ride.days_after_start', { n: Math.abs(d) });
});
const returnRelLabel = computed(() => {
    const d = returnDiff.value;
    if (d === 0) return t('ride.on_last_day');
    return t(d < 0 ? 'ride.days_before_end' : 'ride.days_after_end', { n: Math.abs(d) });
});

const contactButtons = computed(() => {
    const m = props.ride.contact_methods ?? [];
    const phone = props.ride.phone ?? '';
    const email = props.ride.email ?? '';
    const buttons = [];
    if (m.includes('email'))    buttons.push({ method: 'email',    href: `mailto:${email}`,                     label: t('ride.contact_email') });
    if (!phone) return buttons;
    if (m.includes('call'))     buttons.push({ method: 'call',     href: `tel:${phone}`,                        label: t('ride.contact_call') });
    if (m.includes('sms'))      buttons.push({ method: 'sms',      href: `sms:${phone}`,                        label: t('ride.contact_sms') });
    if (m.includes('whatsapp')) buttons.push({ method: 'whatsapp', href: `https://wa.me/${phone.replace(/\D/g,'')}`, label: t('ride.contact_whatsapp') });
    if (m.includes('signal'))   buttons.push({ method: 'signal',   href: `https://signal.me/#p/${phone}`,       label: t('ride.contact_signal') });
    if (m.includes('telegram')) buttons.push({ method: 'telegram', href: `https://t.me/${phone}`,               label: t('ride.contact_telegram') });
    return buttons;
});
</script>
