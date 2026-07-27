<template>
    <div
        class="relative rounded-xl shadow-sm bg-white cursor-pointer hover:shadow-md transition-all overflow-hidden"
        @click="emit('open')"
    >
        <span
            :class="['absolute left-0 top-0 bottom-0 w-[3px]',
                ride.type === 'offer' ? 'bg-[var(--color-offer)]' : 'bg-[var(--color-request)]']"
            aria-hidden="true"
        ></span>
        <div class="p-3 pl-4">
            <div class="flex items-start gap-2">
                <!-- City name + creator name -->
                <div class="flex-1 min-w-0">
                    <h2 class="text-base font-bold text-gray-800 leading-tight">
                        {{ cityLocation }}
                    </h2>
                    <p v-if="ride.name" class="text-sm text-gray-800 leading-tight">{{ ride.name }}</p>
                </div>

                <!-- Right side: badges + manage icons -->
                <div class="flex flex-col items-end gap-1 shrink-0">
                    <!-- Type badge -->
                    <Badge :variant="ride.type === 'offer' ? 'offer' : 'request'" size="sm">
                        {{ t('ride.' + ride.type) }}
                    </Badge>

                    <!-- Booked badge (ride offers with 0 seats left) -->
                    <Badge v-if="isBooked" variant="warning" size="sm">
                        {{ t('ride.booked') }}
                    </Badge>

                    <!-- Direction badge (only when not both-ways) -->
                    <Badge v-if="ride.direction !== 'both-ways'" variant="warning" size="sm">
                        {{ t('ride.' + (ride.direction === 'outbound-only' ? 'outbound_only' : 'return_only')) }}
                    </Badge>

                    <!-- Manage icons (admin / event creator on the edit page) -->
                    <div v-if="manageable" class="flex gap-2 shrink-0 text-gray-400 mt-0.5">
                        <button
                            type="button"
                            class="hover:text-gray-700"
                            :title="t('ride.edit')"
                            :aria-label="t('ride.edit')"
                            :data-testid="`ride-edit-${ride.id}`"
                            @click.stop="emit('edit')"
                        ><Pencil class="w-4 h-4" :stroke-width="2" /></button>
                        <button
                            type="button"
                            class="hover:text-red-500"
                            :title="t('ride.delete')"
                            :aria-label="t('ride.delete')"
                            :data-testid="`ride-delete-${ride.id}`"
                            @click.stop="emit('delete')"
                        ><Trash2 class="w-4 h-4" :stroke-width="2" /></button>
                    </div>
                </div>
            </div>

            <!-- Outbound date/time -->
            <p v-if="hasOutbound" class="text-sm text-gray-500 mt-1">
                <ArrowRight class="w-3 h-3 inline align-middle" /> <strong>{{ outboundTime }}</strong><template v-if="outboundWarning">&nbsp;&nbsp;<TriangleAlert class="w-3 h-3 inline align-middle text-[var(--color-warning)]" :title="t('ride.warning_date')" />&nbsp;{{ outboundDayText }}</template>
            </p>

            <!-- Return date/time (only shown when deviating from event end) -->
            <p v-if="hasReturn && returnWarning" class="text-sm text-gray-500 mt-0.5">
                <ArrowLeft class="w-3 h-3 inline align-middle" /> <strong>{{ returnTime }}</strong>&nbsp;&nbsp;<TriangleAlert class="w-3 h-3 inline align-middle text-[var(--color-warning)]" :title="t('ride.warning_date')" />&nbsp;{{ returnDayText }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { ArrowRight, ArrowLeft, TriangleAlert, Pencil, Trash2 } from '@lucide/vue';
import Badge from '@/components/Badge.vue';
import { formatLocation } from '@/utils/formatLocation';

const props = defineProps({
    ride:       { type: Object, required: true },
    event:      { type: Object, required: true },
    manageable: { type: Boolean, default: false },
});

const emit = defineEmits(['open', 'edit', 'delete']);
const { t } = useI18n();

const cityLocation = computed(() => formatLocation(props.ride.location, props.event.location, { cityOnly: true }));
const isBooked = computed(() => props.ride.type === 'offer' && props.ride.seats === 0);

const hasOutbound = computed(() => ['both-ways', 'outbound-only'].includes(props.ride.direction));
const hasReturn   = computed(() => ['both-ways', 'return-only'].includes(props.ride.direction));

function dayDiff(iso, anchor) {
    const a = new Date(iso.substring(0, 10) + 'T00:00:00Z');
    const b = new Date(anchor.substring(0, 10) + 'T00:00:00Z');
    return Math.round((a - b) / 86400000);
}

function fmtTime(iso) { return iso ? iso.substring(11, 16) : ''; }

const outboundDiff    = computed(() => props.ride.outbound_at && props.event?.start_at ? dayDiff(props.ride.outbound_at, props.event.start_at) : 0);
const returnDiff      = computed(() => props.ride.return_at && props.event?.end_at ? dayDiff(props.ride.return_at, props.event.end_at) : 0);
const outboundWarning = computed(() => outboundDiff.value !== 0);
const returnWarning   = computed(() => returnDiff.value !== 0);

const outboundTime    = computed(() => fmtTime(props.ride.outbound_at));
const outboundDayText = computed(() => {
    const d = outboundDiff.value;
    return t(d < 0 ? 'ride.days_before_start' : 'ride.days_after_start', { n: Math.abs(d) });
});

const returnTime    = computed(() => fmtTime(props.ride.return_at));
const returnDayText = computed(() => {
    const d = returnDiff.value;
    return t(d < 0 ? 'ride.days_before_end' : 'ride.days_after_end', { n: Math.abs(d) });
});
</script>
