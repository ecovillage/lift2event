<template>
    <div class="p-5">
        <h2 class="font-semibold text-gray-800 mb-4">
            {{ isEdit ? 'Eintrag bearbeiten' : t('ride.new_entry') }}
        </h2>

        <form class="space-y-4" @submit.prevent="submit">

            <!-- Type -->
            <fieldset>
                <legend class="field-label">{{ t('ride.type_label') }}</legend>
                <div class="flex flex-wrap gap-4 mt-1">
                    <label v-for="opt in typeOptions" :key="opt.value" class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="radio" v-model="form.type" :value="opt.value" class="accent-[--color-primary]" />
                        <span v-html="opt.label"></span>
                    </label>
                </div>
            </fieldset>

            <!-- Direction -->
            <fieldset>
                <legend class="field-label">{{ t('ride.direction_label') }}</legend>
                <div class="flex flex-col gap-1.5 mt-1">
                    <label v-for="opt in directionOptions" :key="opt.value" class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="radio" v-model="form.direction" :value="opt.value" class="accent-[--color-primary]" />
                        {{ opt.label }}
                    </label>
                </div>
            </fieldset>

            <!-- Name -->
            <div>
                <label class="field-label">{{ t('ride.name') }}</label>
                <input v-model="form.name" type="text" required class="field-input" />
            </div>

            <!-- Email -->
            <div>
                <label class="field-label">{{ t('ride.email') }}</label>
                <input v-model="form.email" type="email" required class="field-input" />
            </div>

            <!-- Phone -->
            <div>
                <label class="field-label">{{ t('ride.phone') }}</label>
                <input v-model="form.phone" type="tel" class="field-input" />
                <p v-if="countryWarning" class="mt-1 text-xs text-amber-600">{{ t('error.phone_country') }}</p>
            </div>

            <!-- Contact methods -->
            <fieldset>
                <legend class="field-label">{{ t('ride.contact_label') }}</legend>
                <div class="flex flex-wrap gap-x-4 gap-y-1.5 mt-1">
                    <label
                        v-for="m in contactMethods"
                        :key="m"
                        :class="['flex items-center gap-1.5 text-sm', phoneRequired(m) ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer']"
                    >
                        <input
                            type="checkbox"
                            v-model="form.contact_methods"
                            :value="m"
                            :disabled="phoneRequired(m)"
                            class="accent-[--color-primary]"
                        />
                        {{ t('ride.contact_' + m) }}
                    </label>
                </div>
                <p v-if="noContactMethod" class="mt-1 text-xs text-red-400">{{ t('error.contact_required') }}</p>
            </fieldset>

            <!-- Route heading -->
            <div class="pt-1">
                <h3 class="font-semibold text-sm text-gray-700 mb-3">{{ t('ride.route_heading') }}</h3>

                <!-- Departure location -->
                <div class="relative mb-3">
                    <label class="field-label">
                        {{ form.direction === 'return-only' ? t('ride.destination') : t('ride.departure') }}
                    </label>
                    <input
                        v-model="addressInput"
                        type="text"
                        autocomplete="off"
                        class="field-input"
                        @input="onAddressInput"
                        @blur="closeSuggestions"
                    />
                    <ul
                        v-if="suggestions.length"
                        class="absolute z-50 left-0 right-0 bg-white border border-gray-200 rounded shadow-lg max-h-48 overflow-y-auto text-sm"
                    >
                        <li
                            v-for="s in suggestions"
                            :key="s.place_id"
                            class="px-3 py-2 hover:bg-gray-100 cursor-pointer leading-snug"
                            @mousedown.prevent="selectSuggestion(s)"
                        >{{ s.display_name }}</li>
                    </ul>
                    <p v-if="!form.location && addressInput" class="mt-1 text-xs text-red-400">
                        {{ t('event.location_required') }}
                    </p>
                </div>

                <!-- Outbound date block -->
                <div v-if="hasOutbound" class="mb-3">
                    <label class="field-label">{{ t('ride.outbound_label') }}</label>
                    <div class="flex items-center gap-2">
                        <input v-model="outboundDate" type="date" class="field-input flex-1" :required="hasOutbound" />
                        <input v-model="outboundTime" type="time" placeholder="--:--" class="field-input w-28" :required="hasOutbound" />
                    </div>
                    <div class="flex items-center gap-2 mt-1.5">
                        <button type="button" class="day-btn" @click="shiftDay('outbound', -1)">← {{ t('ride.day_back') }}</button>
                        <span class="text-xs text-gray-400 flex-1 text-center">{{ outboundLabel }}</span>
                        <button type="button" class="day-btn" @click="shiftDay('outbound', +1)">{{ t('ride.day_forward') }} →</button>
                    </div>
                </div>

                <!-- Return date block -->
                <div v-if="hasReturn">
                    <label class="field-label">{{ t('ride.return_label') }}</label>
                    <div class="flex items-center gap-2">
                        <input v-model="returnDate" type="date" class="field-input flex-1" :required="hasReturn" />
                        <input v-model="returnTime" type="time" :placeholder="eventEndTime" class="field-input w-28" />
                    </div>
                    <div class="flex items-center gap-2 mt-1.5">
                        <button type="button" class="day-btn" @click="shiftDay('return', -1)">← {{ t('ride.day_back') }}</button>
                        <span class="text-xs text-gray-400 flex-1 text-center">{{ returnLabel }}</span>
                        <button type="button" class="day-btn" @click="shiftDay('return', +1)">{{ t('ride.day_forward') }} →</button>
                    </div>
                </div>
            </div>

            <!-- Seats (stepper) -->
            <div>
                <label class="field-label">
                    {{ t(form.type === 'offer' ? 'ride.seats_available' : 'ride.seats_needed') }}
                </label>
                <div class="flex items-center gap-0">
                    <button type="button" class="stepper-btn rounded-l" @click="form.seats = Math.max(1, form.seats - 1)">−</button>
                    <span class="px-5 py-2 border-y border-gray-300 text-sm font-medium text-center min-w-[3rem]">{{ form.seats }}</span>
                    <button type="button" class="stepper-btn rounded-r" @click="form.seats = Math.min(8, form.seats + 1)">+</button>
                </div>
            </div>

            <!-- Info -->
            <div>
                <label class="field-label">{{ t('ride.info_label') }}</label>
                <textarea
                    v-model="form.info"
                    :placeholder="t('ride.info_placeholder')"
                    rows="3"
                    class="field-input resize-none"
                ></textarea>
            </div>

            <!-- Errors -->
            <p v-for="e in errors" :key="e" class="text-sm text-red-500">{{ e }}</p>

            <!-- Buttons -->
            <div class="flex gap-2 pt-1">
                <button type="button" class="flex-1 py-2 border border-gray-300 rounded text-sm hover:bg-gray-50" @click="emit('cancelled')">
                    {{ t('ride.cancel') }}
                </button>
                <button
                    type="submit"
                    :disabled="saving || !form.location || form.contact_methods.length === 0"
                    class="flex-1 py-2 bg-[--color-primary] hover:bg-[--color-primary-dark] text-white rounded text-sm font-medium transition-colors disabled:opacity-60"
                >{{ saving ? '…' : t('ride.create') }}</button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/api/axios';

const props = defineProps({
    event:     { type: Object, required: true },
    ride:      { type: Object, default: null },
    editToken: { type: String, default: null },
});

const emit = defineEmits(['submitted', 'cancelled']);
const { t } = useI18n();
const isEdit = computed(() => !!props.ride);

// ── Helpers ───────────────────────────────────────────────────────────────────

function isoDate(iso) { return iso ? iso.substring(0, 10) : ''; }
function isoTime(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
}
function shiftDateStr(dateStr, days) {
    if (!dateStr) return dateStr;
    const d = new Date(dateStr + 'T12:00');
    d.setDate(d.getDate() + days);
    return d.toISOString().substring(0, 10);
}

// ── Form state ────────────────────────────────────────────────────────────────

const form = reactive({
    type:            props.ride?.type            ?? 'offer',
    direction:       props.ride?.direction        ?? 'both-ways',
    seats:           props.ride?.seats            ?? 1,
    name:            props.ride?.name             ?? '',
    email:           props.ride?.email            ?? '',
    phone:           props.ride?.phone            ?? '',
    contact_methods: props.ride?.contact_methods  ?? ['email'],
    info:            props.ride?.info             ?? '',
    location: props.ride?.location ? {
        address:      props.ride.location.address,
        latitude:     parseFloat(props.ride.location.latitude),
        longitude:    parseFloat(props.ride.location.longitude),
        country_code: props.ride.location.country_code ?? null,
    } : null,
});

const outboundDate = ref(props.ride?.outbound_at ? isoDate(props.ride.outbound_at) : isoDate(props.event?.start_at));
const outboundTime = ref(props.ride?.outbound_at ? isoTime(props.ride.outbound_at) : '');
const returnDate   = ref(props.ride?.return_at   ? isoDate(props.ride.return_at)   : isoDate(props.event?.end_at));
const returnTime   = ref(props.ride?.return_at   ? isoTime(props.ride.return_at)   : '');

const eventEndTime = computed(() => isoTime(props.event?.end_at));

const addressInput = ref(props.ride?.location?.address ?? '');
const suggestions  = ref([]);
const saving       = ref(false);
const errors       = ref([]);
let   searchTimer  = null;

const hasOutbound = computed(() => ['both-ways', 'outbound-only'].includes(form.direction));
const hasReturn   = computed(() => ['both-ways', 'return-only'].includes(form.direction));

// Clear dates when direction changes
watch(() => form.direction, (d) => {
    if (!['both-ways', 'outbound-only'].includes(d)) { outboundDate.value = ''; outboundTime.value = ''; }
    if (!['both-ways', 'return-only'].includes(d))   { returnDate.value   = ''; returnTime.value   = ''; }
    // Re-apply event defaults for newly shown blocks
    if (['both-ways', 'outbound-only'].includes(d) && !outboundDate.value) outboundDate.value = isoDate(props.event?.start_at);
    if (['both-ways', 'return-only'].includes(d)   && !returnDate.value)   returnDate.value   = isoDate(props.event?.end_at);
});

// ── Contact methods ───────────────────────────────────────────────────────────

const contactMethods = ['email', 'phone', 'signal', 'telegram', 'whatsapp', 'sms', 'call'];
const phoneMethods   = ['phone', 'signal', 'telegram', 'whatsapp', 'sms', 'call'];

function phoneRequired(method) {
    return phoneMethods.includes(method) && !form.phone;
}

// Remove phone methods from selection when phone is cleared
watch(() => form.phone, (val) => {
    if (!val) {
        form.contact_methods = form.contact_methods.filter(m => !phoneMethods.includes(m));
        if (form.contact_methods.length === 0) form.contact_methods = ['email'];
    }
});

const noContactMethod = computed(() => form.contact_methods.length === 0);

const countryWarning = computed(() => {
    if (!form.phone || !form.location?.country_code) return false;
    const evCc = props.event?.location?.country_code ?? '';
    return evCc && form.location.country_code !== evCc && !form.phone.startsWith('+');
});

// ── Type / direction options ──────────────────────────────────────────────────

const typeOptions = computed(() => [
    { value: 'offer',   label: t('ride.type_offer').replace('biete', '<em>biete</em>') },
    { value: 'request', label: t('ride.type_request').replace('suche', '<em>suche</em>') },
]);
const directionOptions = computed(() => [
    { value: 'both-ways',     label: t('ride.direction_both') },
    { value: 'outbound-only', label: t('ride.direction_outbound') },
    { value: 'return-only',   label: t('ride.direction_return') },
]);

// ── Relative date labels ──────────────────────────────────────────────────────

function dayDiff(dateStr, anchor) {
    if (!dateStr || !anchor) return null;
    const a = new Date(dateStr + 'T12:00');
    const b = new Date(anchor.substring(0, 10) + 'T12:00');
    return Math.round((a - b) / 86400000);
}

const outboundLabel = computed(() => {
    const d = dayDiff(outboundDate.value, props.event?.start_at);
    if (d === null) return '';
    if (d === 0) return t('ride.on_start_day');
    if (d < 0)  return t('ride.days_before_start', { n: -d });
    return t('ride.days_after_start', { n: d });
});

const returnLabel = computed(() => {
    const d = dayDiff(returnDate.value, props.event?.end_at);
    if (d === null) return '';
    if (d === 0) return t('ride.on_last_day');
    if (d < 0)  return t('ride.days_before_end', { n: -d });
    return t('ride.days_after_end', { n: d });
});

function shiftDay(field, delta) {
    if (field === 'outbound') outboundDate.value = shiftDateStr(outboundDate.value, delta);
    else                      returnDate.value   = shiftDateStr(returnDate.value, delta);
}

// ── Nominatim autocomplete ────────────────────────────────────────────────────

function onAddressInput() {
    clearTimeout(searchTimer);
    form.location = null;
    const q = addressInput.value.trim();
    if (q.length < 3) { suggestions.value = []; return; }
    searchTimer = setTimeout(async () => {
        try {
            const { data } = await api.get('/geocode/search', { params: { q } });
            suggestions.value = data;
        } catch { suggestions.value = []; }
    }, 350);
}

function closeSuggestions() { setTimeout(() => { suggestions.value = []; }, 150); }

function selectSuggestion(s) {
    const cc = (s.address?.country_code ?? '').toUpperCase().slice(0, 2) || null;
    form.location      = { address: s.display_name, latitude: parseFloat(s.lat), longitude: parseFloat(s.lon), country_code: cc };
    addressInput.value = s.display_name;
    suggestions.value  = [];
}

// ── Submit ────────────────────────────────────────────────────────────────────

async function submit() {
    errors.value = [];
    if (!form.location)                   { errors.value = [t('event.location_required')]; return; }
    if (form.contact_methods.length === 0) { errors.value = [t('error.contact_required')]; return; }

    // Combine date + time; use event end time as default for return if time is empty
    const outbound_at = hasOutbound.value && outboundDate.value
        ? `${outboundDate.value}T${outboundTime.value || '00:00'}`
        : null;
    const return_at = hasReturn.value && returnDate.value
        ? `${returnDate.value}T${returnTime.value || eventEndTime.value || '00:00'}`
        : null;

    saving.value = true;
    try {
        const payload = { ...form, outbound_at, return_at };
        if (isEdit.value && props.editToken) payload.edit_token = props.editToken;

        const url = isEdit.value
            ? `/e/${props.event.slug}/rides/${props.ride.id}`
            : `/e/${props.event.slug}/rides`;

        const { data } = isEdit.value
            ? await api.put(url, payload)
            : await api.post(url, payload);

        emit('submitted', data);
    } catch (e) {
        const resp = e.response?.data;
        errors.value = resp?.errors
            ? Object.values(resp.errors).flat()
            : [resp?.message ?? t('event.save_error')];
    } finally {
        saving.value = false;
    }
}
</script>

<style scoped>
@reference "tailwindcss";
.field-label  { @apply block text-sm font-medium text-gray-700 mb-1; }
.field-input  { @apply w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:border-[--color-primary] focus:ring-2 focus:ring-[--color-primary]/20; }
.day-btn      { @apply px-2 py-1 text-xs border border-gray-200 rounded hover:bg-gray-50 text-gray-500 transition-colors; }
.stepper-btn  { @apply px-4 py-2 border border-gray-300 text-sm font-medium hover:bg-gray-50 transition-colors; }
</style>
