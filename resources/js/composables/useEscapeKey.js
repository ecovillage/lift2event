import { onMounted, onUnmounted } from 'vue';

export function useEscapeKey(callback) {
    function onKeydown(e) {
        if (e.key === 'Escape') callback();
    }

    onMounted(() => document.addEventListener('keydown', onKeydown));
    onUnmounted(() => document.removeEventListener('keydown', onKeydown));
}
