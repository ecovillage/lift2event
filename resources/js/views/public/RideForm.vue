<template>
    <div
        class="p-5 border-l-4 transition-colors"
        :style="{ borderLeftColor: form.type === 'offer' ? 'var(--color-offer)' : 'var(--color-request)' }"
    >
        <h2 class="font-semibold text-gray-800 mb-4 text-center">
            {{ isEdit ? 'Eintrag bearbeiten' : t('ride.new_entry_heading') }}
        </h2>

        <form class="space-y-4" novalidate @submit.prevent="submit">

            <!-- Type -->
            <fieldset>
                <legend class="field-label">{{ t('ride.type_label') }}</legend>
                <div class="grid grid-cols-2 gap-2 mt-1">
                    <label
                        v-for="opt in typeOptions" :key="opt.value"
                        :class="[
                            'flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-lg border-2 text-sm font-medium cursor-pointer transition-colors',
                            form.type === opt.value
                                ? (opt.value === 'offer'
                                    ? 'border-[var(--color-offer)] bg-[var(--color-offer-light)] text-[var(--color-offer)]'
                                    : 'border-[var(--color-request)] bg-[var(--color-request-light)] text-[var(--color-request)]')
                                : 'border-gray-200 text-gray-500 hover:bg-gray-50'
                        ]"
                    >
                        <input type="radio" v-model="form.type" :value="opt.value" class="sr-only" />
                        <component :is="opt.icon" class="w-4 h-4" :stroke-width="2" aria-hidden="true" />
                        <span v-html="opt.label"></span>
                    </label>
                </div>
            </fieldset>

            <!-- Direction -->
            <fieldset>
                <legend class="field-label">{{ t('ride.direction_label') }}</legend>
                <div class="grid grid-cols-3 gap-2 mt-1">
                    <label
                        v-for="opt in directionOptions" :key="opt.value"
                        :class="[
                            'flex flex-col items-center gap-1 py-2 rounded-lg border text-xs text-center cursor-pointer transition-colors',
                            form.direction === opt.value
                                ? 'border-[var(--color-primary)] bg-[var(--color-primary)]/5 text-[var(--color-primary-dark)] font-medium'
                                : 'border-gray-200 text-gray-500 hover:bg-gray-50'
                        ]"
                    >
                        <input type="radio" v-model="form.direction" :value="opt.value" class="sr-only" />
                        <component :is="opt.icon" class="w-4 h-4" :stroke-width="2" aria-hidden="true" />
                        {{ opt.label }}
                    </label>
                </div>
            </fieldset>

            <!-- Kontakt -->
            <div class="rounded-lg border border-gray-200 bg-gray-50/60 p-3 space-y-3">
                <h3 class="flex items-center gap-1.5 font-semibold text-sm text-gray-700">
                    <User class="w-4 h-4" :stroke-width="2" aria-hidden="true" /> Kontakt
                </h3>

                <!-- Name -->
                <div>
                    <label for="ride-name" class="field-label">{{ t('ride.name') }} <span class="text-red-400">*</span></label>
                    <input
                        id="ride-name" v-model="form.name" type="text" required class="field-input" data-testid="ride-name"
                        @focus="onFocus('name')" @blur="onBlur('name')"
                    />
                    <p v-if="fieldErrors.name" class="mt-1 text-xs text-red-400">{{ fieldErrors.name }}</p>
                </div>

                <!-- Email -->
                <div>
                    <label for="ride-email" class="field-label">{{ t('ride.email') }} <span class="text-red-400">*</span></label>
                    <input
                        id="ride-email" v-model="form.email" type="email" required class="field-input" data-testid="ride-email"
                        @focus="onFocus('email')" @blur="onBlur('email')"
                    />
                    <p v-if="fieldErrors.email" class="mt-1 text-xs text-red-400">{{ fieldErrors.email }}</p>
                </div>

                <!-- Phone -->
                <div>
                    <label for="ride-phone" class="field-label">{{ t('ride.phone') }} <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input
                        id="ride-phone" v-model="form.phone" type="tel" class="field-input" data-testid="ride-phone"
                        @focus="onFocus('phone')" @blur="onBlur('phone')"
                    />
                    <p v-if="countryWarning" class="mt-1 text-xs text-amber-600">{{ t('error.phone_country') }}</p>
                </div>

                <!-- Contact methods -->
                <fieldset>
                    <legend class="field-label">{{ t('ride.contact_label') }}</legend>
                    <div class="flex flex-wrap gap-1.5 mt-1">
                        <label
                            v-for="m in contactMethods"
                            :key="m"
                            :class="[
                                'flex items-center gap-1 pl-2 pr-2.5 py-1 rounded-full border text-xs transition-colors',
                                phoneRequired(m)
                                    ? 'opacity-40 cursor-not-allowed border-gray-200 text-gray-400'
                                    : form.contact_methods.includes(m)
                                        ? 'cursor-pointer border-[var(--color-primary)] bg-[var(--color-primary)]/10 text-[var(--color-primary-dark)]'
                                        : 'cursor-pointer border-gray-300 text-gray-600 hover:bg-gray-100'
                            ]"
                        >
                            <input
                                type="checkbox"
                                v-model="form.contact_methods"
                                :value="m"
                                :disabled="phoneRequired(m)"
                                class="sr-only"
                                @focus="onFocus('contactMethods')" @blur="onBlur('contactMethods')"
                            />
                            <span class="shrink-0 inline-flex" aria-hidden="true">
                                <BrandIcon v-if="contactIcons[m].kind === 'brand'" :icon="contactIcons[m].icon" class="w-3.5 h-3.5" />
                                <component v-else :is="contactIcons[m].component" class="w-3.5 h-3.5" :stroke-width="2" />
                            </span>
                            {{ t('ride.contact_' + m) }}
                        </label>
                    </div>
                    <p v-if="noContactMethod" class="mt-1 text-xs text-red-400">{{ t('error.contact_required') }}</p>
                </fieldset>
            </div>

            <!-- Route -->
            <div class="rounded-lg border border-gray-200 bg-gray-50/60 p-3 space-y-3">
                <h3 class="flex items-center gap-1.5 font-semibold text-sm text-gray-700">
                    <MapPin class="w-4 h-4" :stroke-width="2" aria-hidden="true" /> {{ t('ride.route_heading') }}
                </h3>

                <!-- Departure location -->
                <div class="relative">
                    <label class="field-label">
                        {{ form.direction === 'return-only' ? t('ride.destination') : t('ride.departure') }} <span class="text-red-400">*</span>
                    </label>
                    <input
                        v-model="addressInput"
                        type="text"
                        autocomplete="off"
                        class="field-input"
                        data-testid="ride-address"
                        @input="onAddressInput"
                        @keydown="onAddressKeydown"
                        @focus="onFocus('address')"
                        @blur="onAddressBlur"
                    />
                    <div
                        v-if="suggestions.length"
                        data-testid="ride-suggestions"
                        class="absolute z-50 left-0 right-0 bg-white border border-gray-200 rounded shadow-lg max-h-48 overflow-hidden"
                    >
                      <ul class="max-h-48 overflow-y-auto text-sm">
                        <li
                            v-for="(s, i) in suggestions"
                            :key="s.place_id"
                            :class="['px-3 py-2 cursor-pointer leading-snug', i === highlightedIndex ? 'bg-gray-100' : 'hover:bg-gray-100']"
                            @mousedown.prevent="selectSuggestion(s)"
                        >{{ s.display_name }}</li>
                      </ul>
                    </div>
                    <p v-if="addressNotFound" class="mt-1 text-xs text-amber-600">
                        {{ t('ride.address_not_found', { query: addressNotFoundQuery }) }}
                    </p>
                    <p v-else-if="fieldErrors.address" class="mt-1 text-xs text-red-400">
                        {{ fieldErrors.address }}
                    </p>
                </div>

                <!-- Outbound date block -->
                <div v-if="hasOutbound">
                    <label class="field-label">{{ t('ride.outbound_label') }}</label>
                    <div class="flex items-center gap-2">
                        <input
                            v-model="outboundDate" type="date" class="field-input flex-1" :required="hasOutbound"
                            @focus="onFocus('outboundDate')" @blur="onBlur('outboundDate')"
                        />
                        <input
                            v-model="outboundTime" type="time" placeholder="--:--" class="field-input w-28" :required="hasOutbound"
                            @focus="onFocus('outboundTime')" @blur="onBlur('outboundTime')"
                        />
                    </div>
                    <p v-if="fieldErrors.outboundDate" class="mt-1 text-xs text-red-400">{{ fieldErrors.outboundDate }}</p>
                    <p v-if="fieldErrors.outboundTime" class="mt-1 text-xs text-red-400">{{ fieldErrors.outboundTime }}</p>
                    <div class="flex items-center gap-2 mt-1.5">
                        <button type="button" class="day-btn" @click="shiftDay('outbound', -1)"><ArrowLeft class="w-3 h-3 inline align-middle" :stroke-width="2" /> {{ t('ride.day_back') }}</button>
                        <span class="text-xs text-gray-400 flex-1 text-center">{{ outboundLabel }}</span>
                        <button type="button" class="day-btn" @click="shiftDay('outbound', +1)">{{ t('ride.day_forward') }} <ArrowRight class="w-3 h-3 inline align-middle" :stroke-width="2" /></button>
                    </div>
                </div>

                <!-- Return date block -->
                <div v-if="hasReturn">
                    <label class="field-label">{{ t('ride.return_label') }}</label>
                    <div class="flex items-center gap-2">
                        <input
                            v-model="returnDate" type="date" class="field-input flex-1" :required="hasReturn"
                            @focus="onFocus('returnDate')" @blur="onBlur('returnDate')"
                        />
                        <input v-model="returnTime" type="time" :placeholder="eventEndTime" class="field-input w-28" />
                    </div>
                    <p v-if="fieldErrors.returnDate" class="mt-1 text-xs text-red-400">{{ fieldErrors.returnDate }}</p>
                    <div class="flex items-center gap-2 mt-1.5">
                        <button type="button" class="day-btn" @click="shiftDay('return', -1)"><ArrowLeft class="w-3 h-3 inline align-middle" :stroke-width="2" /> {{ t('ride.day_back') }}</button>
                        <span class="text-xs text-gray-400 flex-1 text-center">{{ returnLabel }}</span>
                        <button type="button" class="day-btn" @click="shiftDay('return', +1)">{{ t('ride.day_forward') }} <ArrowRight class="w-3 h-3 inline align-middle" :stroke-width="2" /></button>
                    </div>
                </div>
            </div>

            <!-- Details -->
            <div class="rounded-lg border border-gray-200 bg-gray-50/60 p-3 space-y-3">
                <h3 class="flex items-center gap-1.5 font-semibold text-sm text-gray-700">
                    <Car class="w-4 h-4" :stroke-width="2" aria-hidden="true" /> Details
                </h3>

                <!-- Seats (stepper) -->
                <div>
                    <label class="field-label">
                        {{ t(form.type === 'offer' ? 'ride.seats_available' : 'ride.seats_needed') }}
                    </label>
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-0">
                            <button type="button" class="stepper-btn rounded-l" @click="form.seats = Math.max(minSeats, form.seats - 1)">−</button>
                            <span class="px-5 py-2 border-y border-[var(--color-primary)] text-sm font-medium text-center min-w-[3rem]">{{ form.seats }}</span>
                            <button type="button" class="stepper-btn rounded-r" @click="form.seats = Math.min(8, form.seats + 1)">+</button>
                        </div>
                        <span v-if="form.type === 'offer' && form.seats === 0" class="text-xs font-medium text-orange-600">{{ t('ride.booked') }}</span>
                    </div>
                </div>

                <!-- Info -->
                <div>
                    <label class="field-label">{{ t('ride.info_label') }} <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea
                        v-model="form.info"
                        :placeholder="t('ride.info_placeholder')"
                        rows="3"
                        class="field-input resize-none"
                    ></textarea>
                </div>
            </div>

            <!-- Errors -->
            <p v-for="e in errors" :key="e" class="text-sm text-red-500">{{ e }}</p>

            <!-- Privacy notice (create mode only) -->
            <div v-if="!isEdit" class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                <p class="text-xs text-amber-800 leading-snug">{{ organisationName ? t('ride.privacy_notice_with_org', { eventName: event.name, organisationName, n: retentionDays }) : t('ride.privacy_notice', { eventName: event.name, n: retentionDays }) }}</p>
            </div>

            <!-- Buttons -->
            <div class="flex gap-2 pt-1">
                <button type="button" class="flex-1 py-2 border border-gray-300 rounded text-sm hover:bg-gray-50" @click="emit('cancelled')">
                    {{ t('ride.cancel') }}
                </button>
                <button
                    type="submit"
                    :disabled="saving || !form.location || form.contact_methods.length === 0"
                    class="flex-1 py-2 bg-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] text-white rounded text-sm font-medium transition-colors disabled:opacity-60"
                    data-testid="ride-submit"
                >{{ saving ? '…' : t('ride.create') }}</button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { User, MapPin, Car, Hand, ArrowLeftRight, ArrowRight, ArrowLeft } from '@lucide/vue';
import BrandIcon from '@/components/BrandIcon.vue';
import { contactIcons } from '@/constants/contactIcons';
import api from '@/api/axios';
import { useEscapeKey } from '@/composables/useEscapeKey';
import { useAddressAutocomplete } from '@/composables/useAddressAutocomplete';
import { locationFromNominatim } from '@/utils/formatLocation';

const props = defineProps({
    event:            { type: Object, required: true },
    ride:             { type: Object, default: null },
    editToken:        { type: String, default: null },
    manage:           { type: Boolean, default: false },
    retentionDays:    { type: Number, default: 90 },
    organisationName: { type: String, default: '' },
});

const emit = defineEmits(['submitted', 'cancelled']);
const { t, locale } = useI18n();
const isEdit = computed(() => !!props.ride);

useEscapeKey(() => emit('cancelled'));

// ── Helpers ───────────────────────────────────────────────────────────────────

// Times are stored naively (no timezone). Slice directly – never use new Date() here.
function isoDate(iso) { return iso ? iso.substring(0, 10) : ''; }
function isoTime(iso) { return iso ? iso.substring(11, 16) : ''; }

function shiftDateStr(dateStr, days) {
    if (!dateStr) return dateStr;
    const pad = n => String(n).padStart(2, '0');
    const d = new Date(dateStr + 'T12:00:00Z');
    d.setUTCDate(d.getUTCDate() + days);
    return `${d.getUTCFullYear()}-${pad(d.getUTCMonth() + 1)}-${pad(d.getUTCDate())}`;
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
        street:       props.ride.location.street ?? null,
        house_number: props.ride.location.house_number ?? null,
        postal_code:  props.ride.location.postal_code ?? null,
        city:         props.ride.location.city ?? null,
    } : null,
});

const outboundDate = ref(props.ride?.outbound_at ? isoDate(props.ride.outbound_at) : isoDate(props.event?.start_at));
const outboundTime = ref(props.ride?.outbound_at ? isoTime(props.ride.outbound_at) : '');
const returnDate   = ref(props.ride?.return_at   ? isoDate(props.ride.return_at)   : isoDate(props.event?.end_at));
const returnTime   = ref(props.ride?.return_at   ? isoTime(props.ride.return_at)   : isoTime(props.event?.end_at));

const eventEndTime = computed(() => isoTime(props.event?.end_at));

const saving                = ref(false);
const errors                = ref([]);
const addressNotFound       = ref(false);
const addressNotFoundQuery  = ref('');
let   notFoundTimer         = null;

const {
    addressInput,
    suggestions,
    highlightedIndex,
    onAddressInput,
    onAddressKeydown,
    closeSuggestions,
    selectSuggestion,
} = useAddressAutocomplete({
    initialAddress: props.ride?.location?.address ?? '',
    onInput: () => {
        form.location          = null;
        addressNotFound.value  = false;
        clearTimeout(notFoundTimer);
    },
    onResults: (results, query) => {
        if (results.length === 0) {
            notFoundTimer = setTimeout(() => {
                if (addressInput.value.trim() === query && !form.location && suggestions.value.length === 0) {
                    addressNotFound.value      = true;
                    addressNotFoundQuery.value = query;
                }
            }, 1000);
        }
    },
    onSelect: (s) => {
        form.location          = locationFromNominatim(s);
        addressInput.value     = s.display_name;
        addressNotFound.value  = false;
        clearTimeout(notFoundTimer);
    },
});

const hasOutbound = computed(() => ['both-ways', 'outbound-only'].includes(form.direction));
const hasReturn   = computed(() => ['both-ways', 'return-only'].includes(form.direction));

// Ride offers may drop to 0 seats to mark themselves as fully booked; requests always need at least 1.
const minSeats = computed(() => (form.type === 'offer' ? 0 : 1));
watch(() => form.type, () => { if (form.seats < minSeats.value) form.seats = minSeats.value; });

// ── Field validation (validate on blur, re-validate earlier fields on focus) ───

const fieldOrder = ['name', 'email', 'phone', 'contactMethods', 'address', 'outboundDate', 'outboundTime', 'returnDate'];
const touched    = reactive(Object.fromEntries(fieldOrder.map(k => [k, false])));

function onFocus(key) {
    fieldOrder.slice(0, fieldOrder.indexOf(key)).forEach(k => { touched[k] = true; });
}
function onBlur(key) {
    touched[key] = true;
}

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function validateField(key) {
    switch (key) {
        case 'name':         return form.name.trim() ? null : t('error.name_required');
        case 'email': {
            if (!form.email.trim()) return t('error.email_required');
            return emailPattern.test(form.email) ? null : t('error.email_invalid');
        }
        case 'address':       return form.location ? null : t('ride.location_required');
        case 'outboundDate':  return hasOutbound.value && !outboundDate.value ? t('error.outbound_date_required') : null;
        case 'outboundTime':  return hasOutbound.value && !outboundTime.value ? t('error.outbound_time_required') : null;
        case 'returnDate':    return hasReturn.value && !returnDate.value ? t('error.return_date_required') : null;
        default:              return null;
    }
}

const fieldErrors = computed(() => Object.fromEntries(
    fieldOrder.map(k => [k, touched[k] ? validateField(k) : null])
));

// Clear dates when direction changes
watch(() => form.direction, (d) => {
    if (!['both-ways', 'outbound-only'].includes(d)) { outboundDate.value = ''; outboundTime.value = ''; }
    if (!['both-ways', 'return-only'].includes(d))   { returnDate.value   = ''; returnTime.value   = ''; }
    // Re-apply event defaults for newly shown blocks
    if (['both-ways', 'outbound-only'].includes(d) && !outboundDate.value) outboundDate.value = isoDate(props.event?.start_at);
    if (['both-ways', 'return-only'].includes(d)   && !returnDate.value)   returnDate.value   = isoDate(props.event?.end_at);
    if (['both-ways', 'return-only'].includes(d)   && !returnTime.value)   returnTime.value   = isoTime(props.event?.end_at);
});

// ── Contact methods ───────────────────────────────────────────────────────────

const contactMethods = ['email', 'signal', 'telegram', 'whatsapp', 'sms', 'call'];
const phoneMethods   = ['signal', 'telegram', 'whatsapp', 'sms', 'call'];

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
    { value: 'offer',   icon: Car,  label: t('ride.type_offer').replace('biete', '<em>biete</em>') },
    { value: 'request', icon: Hand, label: t('ride.type_request').replace('suche', '<em>suche</em>') },
]);
const directionOptions = computed(() => [
    { value: 'both-ways',     icon: ArrowLeftRight, label: t('ride.direction_both') },
    { value: 'outbound-only', icon: ArrowRight,     label: t('ride.direction_outbound') },
    { value: 'return-only',   icon: ArrowLeft,      label: t('ride.direction_return') },
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

function onAddressBlur() { closeSuggestions(); onBlur('address'); }

// ── Submit ────────────────────────────────────────────────────────────────────

async function submit() {
    errors.value = [];
    fieldOrder.forEach(k => { touched[k] = true; });
    if (fieldOrder.some(k => fieldErrors.value[k])) return;
    if (form.contact_methods.length === 0) { errors.value = [t('error.contact_required')]; return; }

    // Combine date + time; no timezone suffix – times are stored as entered
    const outbound_at = hasOutbound.value && outboundDate.value
        ? `${outboundDate.value}T${outboundTime.value || '00:00'}`
        : null;
    const return_at = hasReturn.value && returnDate.value
        ? `${returnDate.value}T${returnTime.value || eventEndTime.value || '00:00'}`
        : null;

    saving.value = true;
    try {
        const payload = { ...form, outbound_at, return_at, locale: locale.value };
        if (isEdit.value && props.editToken) payload.edit_token = props.editToken;

        const url = isEdit.value
            ? (props.manage
                ? `/events/${props.event.id}/rides/${props.ride.id}`
                : `/e/${props.event.slug}/rides/${props.ride.id}`)
            : `/e/${props.event.slug}/rides`;

        const { data } = isEdit.value
            ? await api.put(url, payload)
            : await api.post(url, payload);

        emit('submitted', data);
    } catch (e) {
        const resp = e.response?.data;
        if (e.response?.status === 503) {
            errors.value = [t('ride.mail_send_failed')];
        } else {
            errors.value = resp?.errors
                ? Object.values(resp.errors).flat()
                : [resp?.message ?? t('event.save_error')];
        }
    } finally {
        saving.value = false;
    }
}
</script>

<style scoped>
@reference "tailwindcss";
.field-label  { @apply block text-sm font-medium text-gray-700 mb-1; }
.field-input  { @apply w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]/20; }
.day-btn      { @apply px-2.5 py-1 text-xs font-medium border border-[var(--color-primary)] rounded-md bg-[var(--color-primary)]/5 shadow-sm text-[var(--color-primary-dark)] hover:bg-[var(--color-primary)]/10 active:bg-[var(--color-primary)]/20 transition-colors; }
.stepper-btn  { @apply px-4 py-2 border border-[var(--color-primary)] bg-[var(--color-primary)]/5 text-sm font-medium text-[var(--color-primary-dark)] hover:bg-[var(--color-primary)]/10 active:bg-[var(--color-primary)]/20 transition-colors; }
</style>
