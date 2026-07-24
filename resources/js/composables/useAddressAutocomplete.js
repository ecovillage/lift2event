import { ref } from 'vue';
import api from '@/api/axios';

// Shared Nominatim address-autocomplete behaviour (debounced search, keyboard
// navigation, suggestion selection) used by both the event and ride forms.
export function useAddressAutocomplete({ initialAddress = '', debounceMs = 350, onInput, onResults, onSelect } = {}) {
    const addressInput     = ref(initialAddress);
    const suggestions      = ref([]);
    const highlightedIndex = ref(-1);
    let searchTimer        = null;

    function onAddressInput() {
        clearTimeout(searchTimer);
        highlightedIndex.value = -1;
        onInput?.();

        const q = addressInput.value.trim();
        if (q.length < 3) { suggestions.value = []; return; }

        searchTimer = setTimeout(async () => {
            try {
                const { data } = await api.get('/geocode/search', { params: { q } });
                suggestions.value      = data;
                highlightedIndex.value = -1;
            } catch {
                suggestions.value = [];
            }
            onResults?.(suggestions.value, q);
        }, debounceMs);
    }

    function closeSuggestions() {
        // Small delay so mousedown on suggestion fires before blur clears the list
        setTimeout(() => { suggestions.value = []; }, 150);
    }

    function onAddressKeydown(e) {
        if (!suggestions.value.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            highlightedIndex.value = highlightedIndex.value < suggestions.value.length - 1 ? highlightedIndex.value + 1 : 0;
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            highlightedIndex.value = highlightedIndex.value > 0 ? highlightedIndex.value - 1 : suggestions.value.length - 1;
        } else if (e.key === 'Enter' && highlightedIndex.value >= 0) {
            e.preventDefault();
            selectSuggestion(suggestions.value[highlightedIndex.value]);
        }
    }

    function selectSuggestion(s) {
        suggestions.value = [];
        onSelect?.(s);
    }

    return {
        addressInput,
        suggestions,
        highlightedIndex,
        onAddressInput,
        onAddressKeydown,
        closeSuggestions,
        selectSuggestion,
    };
}
