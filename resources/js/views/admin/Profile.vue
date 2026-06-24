<template>
    <div>
        <h1 class="text-xl font-semibold mb-6">{{ t('nav.profile') }}</h1>

        <div class="bg-white rounded-lg shadow-sm p-6 max-w-md space-y-8">

            <!-- Name -->
            <section>
                <label for="profile-name" class="field-label">{{ t('auth.name') }}</label>
                <div class="flex gap-2">
                    <input
                        id="profile-name"
                        v-model="form.name"
                        type="text"
                        class="field-input flex-1"
                        data-testid="profile-name"
                    />
                    <button
                        class="px-3 py-2 bg-[var(--color-primary)] text-white rounded text-sm font-medium hover:bg-[var(--color-primary-dark)] disabled:opacity-60"
                        :disabled="saving.name"
                        data-testid="save-name-btn"
                        @click="save('name')"
                    >{{ saving.name ? '…' : t('settings.save') }}</button>
                </div>
                <Feedback :msg="msg.name" />
            </section>

            <!-- Email -->
            <section>
                <label for="profile-email" class="field-label">{{ t('auth.email') }}</label>
                <input id="profile-email" v-model="form.email" type="email" class="field-input mb-3" data-testid="profile-email" />
                <label for="profile-cpw-email" class="field-label">{{ t('profile.current_password') }}</label>
                <div class="flex gap-2">
                    <input
                        id="profile-cpw-email"
                        v-model="form.current_password_email"
                        type="password"
                        class="field-input flex-1"
                        data-testid="profile-cpw-email"
                    />
                    <button
                        class="px-3 py-2 bg-[var(--color-primary)] text-white rounded text-sm font-medium hover:bg-[var(--color-primary-dark)] disabled:opacity-60"
                        :disabled="saving.email"
                        data-testid="save-email-btn"
                        @click="save('email')"
                    >{{ saving.email ? '…' : t('settings.save') }}</button>
                </div>
                <Feedback :msg="msg.email" />
            </section>

            <!-- Password -->
            <section>
                <p class="font-medium text-sm text-gray-700 mb-3">{{ t('profile.change_password') }}</p>
                <label for="profile-cpw" class="field-label">{{ t('profile.current_password') }}</label>
                <input id="profile-cpw" v-model="form.current_password" type="password" class="field-input mb-3" data-testid="profile-current-password" />
                <label for="profile-npw" class="field-label">{{ t('profile.new_password') }}</label>
                <input id="profile-npw" v-model="form.password" type="password" class="field-input mb-3" data-testid="profile-new-password" />
                <label for="profile-cpwc" class="field-label">{{ t('auth.confirm_password') }}</label>
                <div class="flex gap-2">
                    <input
                        id="profile-cpwc"
                        v-model="form.password_confirmation"
                        type="password"
                        class="field-input flex-1"
                        data-testid="profile-password-confirm"
                    />
                    <button
                        class="px-3 py-2 bg-[var(--color-primary)] text-white rounded text-sm font-medium hover:bg-[var(--color-primary-dark)] disabled:opacity-60"
                        :disabled="saving.password"
                        data-testid="save-password-btn"
                        @click="save('password')"
                    >{{ saving.password ? '…' : t('settings.save') }}</button>
                </div>
                <Feedback :msg="msg.password" />
            </section>

            <!-- Language -->
            <section>
                <label for="profile-lang" class="field-label">{{ t('profile.language') }}</label>
                <div class="flex gap-2">
                    <select
                        id="profile-lang"
                        v-model="form.preferred_language"
                        class="field-input flex-1"
                        data-testid="profile-language"
                    >
                        <option value="de">Deutsch</option>
                        <option value="en">English</option>
                        <option value="fr">Français</option>
                        <option value="zh">中文</option>
                    </select>
                    <button
                        class="px-3 py-2 bg-[var(--color-primary)] text-white rounded text-sm font-medium hover:bg-[var(--color-primary-dark)] disabled:opacity-60"
                        :disabled="saving.language"
                        data-testid="save-language-btn"
                        @click="save('language')"
                    >{{ saving.language ? '…' : t('settings.save') }}</button>
                </div>
                <Feedback :msg="msg.language" />
            </section>
        </div>
    </div>
</template>

<script setup>
import { defineComponent, h, reactive, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuth } from '@/composables/useAuth';
import api from '@/api/axios';

const { t, locale } = useI18n();
const { state } = useAuth();

const form = reactive({
    name:                   '',
    email:                  '',
    current_password_email: '',
    current_password:       '',
    password:               '',
    password_confirmation:  '',
    preferred_language:     'de',
});

const saving = reactive({ name: false, email: false, password: false, language: false });
const msg    = reactive({ name: null, email: null, password: null, language: null });

onMounted(() => {
    if (state.user) {
        form.name               = state.user.name;
        form.email              = state.user.email;
        form.preferred_language = state.user.preferred_language ?? 'de';
    }
});

async function save(section) {
    saving[section] = true;
    msg[section]    = null;

    let payload = {};

    if (section === 'name') {
        payload = { name: form.name };
    } else if (section === 'email') {
        payload = { email: form.email, current_password: form.current_password_email };
    } else if (section === 'password') {
        payload = {
            current_password:      form.current_password,
            password:              form.password,
            password_confirmation: form.password_confirmation,
        };
    } else if (section === 'language') {
        payload = { preferred_language: form.preferred_language };
    }

    try {
        const { data } = await api.put('/user/profile', payload);
        state.user = data;

        if (section === 'language') {
            locale.value = data.preferred_language;
        }
        if (section === 'email') {
            form.current_password_email = '';
        }
        if (section === 'password') {
            form.current_password = '';
            form.password = '';
            form.password_confirmation = '';
        }

        msg[section] = { ok: true, text: t('profile.saved') };
        setTimeout(() => { msg[section] = null; }, 3000);
    } catch (e) {
        const resp = e.response?.data;
        const first = resp?.errors ? Object.values(resp.errors).flat()[0] : null;
        msg[section] = { ok: false, text: first ?? resp?.message ?? t('profile.error') };
    } finally {
        saving[section] = false;
    }
}

// Inline feedback component
const Feedback = defineComponent({
    props: { msg: Object },
    setup(props) {
        return () => props.msg
            ? h('p', {
                class: ['mt-1 text-sm', props.msg.ok ? 'text-green-600' : 'text-red-500'],
                'data-testid': 'profile-feedback',
              }, props.msg.text)
            : null;
    },
});
</script>

<style scoped>
@reference "tailwindcss";
.field-label { @apply block text-sm font-medium text-gray-700 mb-1; }
.field-input { @apply w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]/20; }
</style>
