<template>
    <div>
        <h1 class="text-xl font-semibold mb-6">{{ t('nav.profile') }}</h1>

        <div class="bg-white rounded-lg shadow-sm p-6 max-w-md space-y-8">

            <!-- Name, Email, Language -->
            <section>
                <label for="profile-name" class="field-label">{{ t('auth.name') }}</label>
                <input
                    id="profile-name"
                    v-model="form.name"
                    type="text"
                    class="field-input mb-3"
                    data-testid="profile-name"
                />
                <label for="profile-email" class="field-label">{{ t('auth.email') }}</label>
                <input id="profile-email" v-model="form.email" type="email" class="field-input mb-3" data-testid="profile-email" />
                <label for="profile-lang" class="field-label">{{ t('profile.language') }}</label>
                <select
                    id="profile-lang"
                    v-model="form.preferred_language"
                    class="field-input mb-3"
                    data-testid="profile-language"
                >
                    <option value="de">Deutsch</option>
                    <option value="en">English</option>
                    <option value="fr">Français</option>
                    <option value="zh">中文</option>
                </select>
                <button
                    class="px-3 py-2 bg-[var(--color-primary)] text-white rounded text-sm font-medium hover:bg-[var(--color-primary-dark)] disabled:opacity-60"
                    :disabled="saving.profile"
                    data-testid="save-profile-btn"
                    @click="save('profile')"
                >{{ saving.profile ? '…' : t('settings.save') }}</button>
                <Feedback :msg="msg.profile" />
            </section>

            <!-- Password -->
            <section>
                <h2 class="text-lg font-semibold text-gray-900 mb-3">{{ t('profile.change_password') }}</h2>
                <label for="profile-cpw" class="field-label">{{ t('profile.current_password') }}</label>
                <input id="profile-cpw" v-model="form.current_password" type="password" class="field-input mb-3" data-testid="profile-current-password" />
                <label for="profile-npw" class="field-label">{{ t('profile.new_password') }}</label>
                <input id="profile-npw" v-model="form.password" type="password" class="field-input mb-3" data-testid="profile-new-password" />
                <label for="profile-cpwc" class="field-label">{{ t('auth.confirm_password') }}</label>
                <input
                    id="profile-cpwc"
                    v-model="form.password_confirmation"
                    type="password"
                    class="field-input mb-3"
                    data-testid="profile-password-confirm"
                />
                <button
                    class="px-3 py-2 bg-[var(--color-primary)] text-white rounded text-sm font-medium hover:bg-[var(--color-primary-dark)] disabled:opacity-60"
                    :disabled="saving.password"
                    data-testid="save-password-btn"
                    @click="save('password')"
                >{{ saving.password ? '…' : t('settings.save') }}</button>
                <Feedback :msg="msg.password" />
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
    current_password:       '',
    password:               '',
    password_confirmation:  '',
    preferred_language:     'de',
});

const saving = reactive({ profile: false, password: false });
const msg    = reactive({ profile: null, password: null });

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

    if (section === 'profile') {
        payload = {
            name:               form.name,
            email:              form.email,
            preferred_language: form.preferred_language,
        };
    } else if (section === 'password') {
        payload = {
            current_password:      form.current_password,
            password:              form.password,
            password_confirmation: form.password_confirmation,
        };
    }

    try {
        const { data } = await api.put('/user/profile', payload);
        state.user = data;

        if (section === 'profile') {
            locale.value = data.preferred_language;
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
