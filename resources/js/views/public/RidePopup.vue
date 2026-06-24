<template>
    <div class="fixed inset-0 z-[1500] bg-black/50 flex items-end md:items-center justify-center" @click.self="emit('close')">
        <div class="bg-white rounded-t-2xl md:rounded-xl w-full md:max-w-md max-h-[90vh] overflow-y-auto">

            <!-- Close button -->
            <div class="flex justify-end p-3 pb-0">
                <button class="text-gray-400 hover:text-gray-600 text-xl leading-none" @click="emit('close')">&#215;</button>
            </div>

            <div class="px-5 pb-6 space-y-4">

                <!-- Seats badge + type -->
                <div class="flex items-center gap-2">
                    <span :class="['px-3 py-1.5 rounded-full text-sm font-semibold flex items-center gap-1.5',
                        ride.type === 'offer'
                            ? 'bg-[var(--color-offer-light)] text-[var(--color-offer)]'
                            : 'bg-[var(--color-request-light)] text-[var(--color-request)]']">
                        <span>{{ ride.seats }}</span>
                        <span>{{ t(ride.type === 'offer' ? 'ride.seats_available' : 'ride.seats_needed') }}</span>
                    </span>
                    <span class="text-sm text-gray-500">{{ directionLabel }}</span>
                </div>

                <!-- Dates -->
                <div class="space-y-1">
                    <div v-if="hasOutbound" class="flex items-start gap-2 text-sm">
                        <span class="text-gray-400 mt-0.5">→</span>
                        <div>
                            <span class="font-medium">{{ t('ride.outbound_label') }}</span>
                            <span class="ml-2 text-gray-600">{{ fmtDateTime(ride.outbound_at) }}</span>
                            <span v-if="outboundWarning" class="ml-2 text-amber-600 text-xs">
                                ⚠ {{ outboundRelLabel }}
                            </span>
                        </div>
                    </div>
                    <div v-if="hasReturn" class="flex items-start gap-2 text-sm">
                        <span class="text-gray-400 mt-0.5">←</span>
                        <div>
                            <span class="font-medium">{{ t('ride.return_label') }}</span>
                            <span class="ml-2 text-gray-600">{{ fmtDateTime(ride.return_at) }}</span>
                            <span v-if="returnWarning" class="ml-2 text-amber-600 text-xs">
                                ⚠ {{ returnRelLabel }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Departure / destination label + address -->
                <div class="text-sm">
                    <span class="text-gray-500">
                        {{ ride.direction === 'return-only' ? t('ride.destination_label') : t('ride.departure_label') }}
                    </span>
                    <span class="ml-1 text-gray-800">{{ ride.location?.address }}</span>
                </div>

                <!-- Info text -->
                <p v-if="ride.info" class="text-sm text-gray-600 italic leading-snug">{{ ride.info }}</p>

                <!-- Contact section -->
                <div class="border-t pt-4 space-y-3">
                    <h3 class="font-semibold text-sm text-gray-700">{{ t('ride.contact_heading') }}</h3>

                    <div class="space-y-1">
                        <p class="font-medium text-gray-800">{{ ride.name }}</p>
                        <a v-if="showEmail" :href="`mailto:${ride.email}`" class="flex items-center gap-1.5 text-sm text-gray-600 hover:text-gray-900">
                            <span>✉</span> {{ ride.email }}
                        </a>
                        <p v-if="showPhone" class="flex items-center gap-1.5 text-sm text-gray-600">
                            <span>📞</span> {{ ride.phone }}
                        </p>
                    </div>

                    <!-- Contact deep-link buttons -->
                    <div class="flex flex-wrap gap-2">
                        <a
                            v-for="btn in contactButtons"
                            :key="btn.method"
                            :href="btn.href"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="px-3 py-1.5 rounded-full text-xs font-medium bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors"
                        >{{ btn.label }}</a>
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
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    ride:  { type: Object, required: true },
    event: { type: Object, required: true },
});

const emit = defineEmits(['close']);
const { t, locale } = useI18n();

const editToken   = computed(() => localStorage.getItem(`ride_token_${props.ride.id}`));
const hasOutbound = computed(() => ['both-ways', 'outbound-only'].includes(props.ride.direction));
const hasReturn   = computed(() => ['both-ways', 'return-only'].includes(props.ride.direction));
const showEmail   = computed(() => props.ride.contact_methods?.includes('email'));
const showPhone   = computed(() => {
    const pm = ['phone', 'signal', 'telegram', 'whatsapp', 'sms', 'call'];
    return props.ride.phone && props.ride.contact_methods?.some(m => pm.includes(m));
});

const directionLabel = computed(() => ({
    'both-ways':     t('ride.direction_both'),
    'outbound-only': t('ride.direction_outbound'),
    'return-only':   t('ride.direction_return'),
}[props.ride.direction] ?? ''));

const dtFmt = computed(() => new Intl.DateTimeFormat(locale.value, { dateStyle: 'medium', timeStyle: 'short' }));
function fmtDateTime(iso) { return iso ? dtFmt.value.format(new Date(iso)) : ''; }

function dayDiff(iso, anchor) {
    const a = new Date(iso);    a.setHours(0, 0, 0, 0);
    const b = new Date(anchor); b.setHours(0, 0, 0, 0);
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
    if (m.includes('call'))     buttons.push({ method: 'call',     href: `tel:${phone}`,                        label: t('ride.contact_call') });
    if (m.includes('sms'))      buttons.push({ method: 'sms',      href: `sms:${phone}`,                        label: t('ride.contact_sms') });
    if (m.includes('whatsapp')) buttons.push({ method: 'whatsapp', href: `https://wa.me/${phone.replace(/\D/g,'')}`, label: t('ride.contact_whatsapp') });
    if (m.includes('signal'))   buttons.push({ method: 'signal',   href: `https://signal.me/#p/${phone}`,       label: t('ride.contact_signal') });
    if (m.includes('telegram')) buttons.push({ method: 'telegram', href: `https://t.me/${phone}`,               label: t('ride.contact_telegram') });
    return buttons;
});
</script>
