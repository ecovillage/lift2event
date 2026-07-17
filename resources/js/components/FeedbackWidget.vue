<template>
    <div>
        <!-- Tab button -->
        <button
            class="fixed right-0 top-1/2 -translate-y-1/2 z-[1500] bg-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] text-white text-sm font-medium px-2 py-3 rounded-l-md shadow-md transition-colors"
            style="writing-mode: vertical-rl; text-orientation: mixed;"
            @click="open = true"
        >{{ t('feedback.button') }}</button>

        <!-- Backdrop + drawer -->
        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="open"
                    class="fixed inset-0 z-[1500] bg-black/30"
                    @click.self="close"
                >
                    <Transition name="slide">
                        <div
                            v-if="open"
                            class="absolute right-0 top-0 h-full w-80 bg-white shadow-xl flex flex-col"
                            @click.stop
                        >
                            <!-- Header -->
                            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 flex-shrink-0">
                                <h2 class="text-base font-semibold text-gray-800">{{ t('feedback.title') }}</h2>
                                <button
                                    class="text-gray-400 hover:text-gray-600 transition-colors"
                                    @click="close"
                                >
                                    <X class="w-5 h-5" />
                                </button>
                            </div>

                            <!-- Form -->
                            <div class="flex-1 overflow-y-auto p-4">
                                <div v-if="submitted" class="flex flex-col items-center gap-3 py-10 text-center">
                                    <CircleCheck class="w-10 h-10 text-green-500" :stroke-width="1.5" />
                                    <p class="text-sm text-gray-700">{{ t('feedback.success') }}</p>
                                </div>

                                <form v-else class="flex flex-col gap-4" @submit.prevent="submit">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('feedback.name') }}</label>
                                        <input
                                            v-model="form.name"
                                            type="text"
                                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent"
                                            autocomplete="name"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('feedback.email') }}</label>
                                        <input
                                            v-model="form.email"
                                            type="email"
                                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent"
                                            autocomplete="email"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">
                                            {{ t('feedback.message') }}
                                            <span class="text-red-500 ml-0.5">*</span>
                                        </label>
                                        <textarea
                                            v-model="form.message"
                                            rows="5"
                                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent resize-none"
                                            :placeholder="t('feedback.message_placeholder')"
                                        ></textarea>
                                        <p v-if="messageError" class="mt-1 text-xs text-red-500">{{ t('feedback.message_required') }}</p>
                                    </div>

                                    <p v-if="sendError" class="text-xs text-red-500">{{ t('feedback.error') }}</p>

                                    <button
                                        type="submit"
                                        :disabled="sending"
                                        class="w-full py-2 rounded text-sm font-medium text-white bg-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] disabled:opacity-50 transition-colors"
                                    >{{ sending ? '…' : t('feedback.submit') }}</button>
                                </form>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useI18n } from 'vue-i18n';
import { X, CircleCheck } from '@lucide/vue';
import axios from 'axios';

const { t } = useI18n();

const open         = ref(false);
const submitted    = ref(false);
const sending      = ref(false);
const sendError    = ref(false);
const messageError = ref(false);

const form = reactive({ name: '', email: '', message: '' });

function close() {
    open.value = false;
}

async function submit() {
    messageError.value = false;
    sendError.value    = false;

    if (!form.message.trim()) {
        messageError.value = true;
        return;
    }

    sending.value = true;
    try {
        await axios.post('/api/feedback', {
            name:    form.name  || null,
            email:   form.email || null,
            message: form.message,
        });
        submitted.value = true;
    } catch {
        sendError.value = true;
    } finally {
        sending.value = false;
    }
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
    transition: transform 0.25s ease;
}
.slide-enter-from,
.slide-leave-to {
    transform: translateX(100%);
}
</style>
