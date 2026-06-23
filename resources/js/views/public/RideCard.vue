<template>
    <div
        :class="['border-l-4 bg-white cursor-pointer hover:bg-gray-50 transition-colors p-3',
            ride.type === 'offer' ? 'border-[--color-offer]' : 'border-[--color-request]']"
        @click="emit('open')"
    >
        <div class="flex items-start justify-between gap-2">
            <!-- Type badge -->
            <span :class="['inline-block px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide mb-1',
                ride.type === 'offer'
                    ? 'bg-[--color-offer-light] text-[--color-offer]'
                    : 'bg-[--color-request-light] text-[--color-request]']">
                {{ t('ride.' + ride.type) }}
            </span>

            <!-- Manage icons (admin / event creator on the edit page, no token required) -->
            <div v-if="manageable" class="flex gap-2 shrink-0 text-gray-400">
                <button
                    type="button"
                    class="hover:text-gray-700"
                    :title="t('ride.edit')"
                    :aria-label="t('ride.edit')"
                    :data-testid="`ride-edit-${ride.id}`"
                    @click.stop="emit('edit')"
                >✎</button>
                <button
                    type="button"
                    class="hover:text-red-500"
                    :title="t('ride.delete')"
                    :aria-label="t('ride.delete')"
                    :data-testid="`ride-delete-${ride.id}`"
                    @click.stop="emit('delete')"
                >🗑</button>
            </div>
        </div>

        <!-- Direction note (only when not both-ways) -->
        <span
            v-if="ride.direction !== 'both-ways'"
            class="ml-1 text-[10px] text-gray-400"
        >· {{ ride.direction === 'outbound-only' ? t('ride.direction_outbound') : t('ride.direction_return') }}</span>

        <!-- Departure address (truncated) -->
        <p class="text-xs text-gray-700 truncate mt-0.5">{{ ride.location?.address }}</p>

        <!-- Outbound date/time -->
        <p v-if="hasOutbound" class="text-xs text-gray-500 mt-0.5 flex items-start gap-1">
            <span v-if="outboundWarning" title="Abweichendes Datum">⚠</span>
            <span>{{ outboundLine }}</span>
        </p>

        <!-- Return date/time -->
        <p v-if="hasReturn && returnWarning" class="text-xs text-gray-500 mt-0.5 flex items-start gap-1">
            <span title="Abweichendes Datum">⚠</span>
            <span>{{ returnLine }}</span>
        </p>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    ride:       { type: Object, required: true },
    event:      { type: Object, required: true },
    manageable: { type: Boolean, default: false },
});

const emit = defineEmits(['open', 'edit', 'delete']);
const { t, locale } = useI18n();

const hasOutbound = computed(() => ['both-ways', 'outbound-only'].includes(props.ride.direction));
const hasReturn   = computed(() => ['both-ways', 'return-only'].includes(props.ride.direction));

function dayDiff(iso, anchor) {
    const a = new Date(iso);    a.setHours(0, 0, 0, 0);
    const b = new Date(anchor); b.setHours(0, 0, 0, 0);
    return Math.round((a - b) / 86400000);
}

const timeFmt = computed(() => new Intl.DateTimeFormat(locale.value, { timeStyle: 'short' }));
function fmtTime(iso) { return iso ? timeFmt.value.format(new Date(iso)) : ''; }

const outboundDiff    = computed(() => props.ride.outbound_at && props.event?.start_at ? dayDiff(props.ride.outbound_at, props.event.start_at) : 0);
const returnDiff      = computed(() => props.ride.return_at && props.event?.end_at ? dayDiff(props.ride.return_at, props.event.end_at) : 0);
const outboundWarning = computed(() => outboundDiff.value !== 0);
const returnWarning   = computed(() => returnDiff.value !== 0);

const outboundLine = computed(() => {
    if (!props.ride.outbound_at) return '';
    const time = fmtTime(props.ride.outbound_at);
    if (!outboundWarning.value) return `→ ${time}`;
    const d = outboundDiff.value;
    const key = d < 0 ? 'ride.days_before_start' : 'ride.days_after_start';
    return `→ ${time} · ${t(key, { n: Math.abs(d) })}`;
});

const returnLine = computed(() => {
    if (!props.ride.return_at) return '';
    const time = fmtTime(props.ride.return_at);
    const d = returnDiff.value;
    const key = d < 0 ? 'ride.days_before_end' : 'ride.days_after_end';
    return `← ${time} · ${t(key, { n: Math.abs(d) })}`;
});
</script>
