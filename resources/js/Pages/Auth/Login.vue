<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const page = usePage();
const logoUrl = computed(() => page.props.theme?.invoice_logo || '');

const form = useForm({
    nim: '',
    password: '',
});

const showPassword = ref(false);

const togglePassword = () => {
    showPassword.value = !showPassword.value;
    console.log('Password toggled:', showPassword.value);
};

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk" />

        <div v-if="status" class="status-message">
            {{ status }}
        </div>

        <div class="login-head">
            <div class="login-brand" :class="{ 'has-logo': logoUrl }">
                <img v-if="logoUrl" :src="logoUrl" alt="Logo" class="login-brand-img" />
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 1.5l3.4 7.1 7.1 3.4-7.1 3.4-3.4 7.1-3.4-7.1L1.5 12l7.1-3.4z" opacity=".45"/>
                    <path d="M12 1.5l3.4 7.1L12 12 8.6 8.6z"/>
                </svg>
            </div>
            <h2 class="login-title">Mahasiswa Login</h2>
            <p class="login-subtitle">Masuk menggunakan NIM dan Password Siakad</p>
        </div>

        <form @submit.prevent="submit" class="login-form">
            <!-- NIM -->
            <div class="field">
                <label for="loginNim" class="field__label">NIM (Nomor Induk Mahasiswa)</label>
                <div class="input-group input-group--lg">
                    <span class="input-group__text">
                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true">
                            <g fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M12 15a3 3 0 1 0 0-6a3 3 0 0 0 6 0Z"/>
                                <path d="M2 12c0-3.771 0-5.657 1.172-6.828S6.229 4 10 4h4c3.771 0 5.657 0 6.828 1.172S22 8.229 22 12s0 5.657-1.172 6.828S17.771 20 14 20h-4c-3.771 0-5.657 0-6.828-1.172S2 15.771 2 12Z"/>
                            </g>
                        </svg>
                    </span>
                    <input
                        id="loginNim"
                        type="text"
                        class="input"
                        v-model="form.nim"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="Masukkan NIM Anda"
                    />
                </div>
                <InputError class="form-error" :message="form.errors.nim" />
            </div>

            <!-- Password -->
            <div class="field">
                <div class="flex items-center justify-between gap-2">
                    <label for="loginPasswordSiakad" class="field__label">Password Siakad</label>
                </div>
                <div class="input-group input-group--lg">
                    <span class="input-group__text">
                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true">
                            <g fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M2 16c0-2.828 0-4.243.879-5.121C3.757 10 5.172 10 8 10h8c2.828 0 4.243 0 5.121.879C22 11.757 22 13.172 22 16s0 4.243-.879 5.121C20.243 22 18.828 22 16 22H8c-2.828 0-4.243 0-5.121-.879C2 20.243 2 18.828 2 16Z"/>
                                <circle cx="12" cy="16" r="2"/>
                                <path stroke-linecap="round" d="M6 10V8a6 6 0 1 1 12 0v2"/>
                            </g>
                        </svg>
                    </span>
                    <input
                        id="loginPasswordSiakad"
                        :type="showPassword ? 'text' : 'password'"
                        class="input"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;"
                    />
                    <button
                        type="button"
                        class="input-group__text--toggle"
                        @click="togglePassword"
                    >
                        <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true">
                            <g fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M3.275 15.296C2.425 14.192 2 13.639 2 12c0-1.64.425-2.191 1.275-3.296C4.972 6.5 7.818 4 12 4s7.028 2.5 8.725 4.704C21.575 9.81 22 10.361 22 12c0 1.64-.425 2.191-1.275 3.296C19.028 17.5 16.182 20 12 20s-7.028-2.5-8.725-4.704Z"/>
                                <path d="M15 12a3 3 0 1 1-6 0a3 3 0 0 1 6 0Z"/>
                            </g>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true">
                            <g fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M2 12c0-3.771 0-5.657 1.172-6.828S6.229 4 10 4h4c3.771 0 5.657 0 6.828 1.172S22 8.229 22 12s0 5.657-1.172 6.828S17.771 20 14 20h-4c-3.771 0-5.657 0-6.828-1.172S2 15.771 2 12Z"/>
                                <path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0-6 0Z"/>
                            </g>
                        </svg>
                    </button>
                </div>
                <InputError class="form-error" :message="form.errors.password" />
            </div>

            <button
                type="submit"
                class="button--primary login-submit"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Memproses...' : 'Masuk' }}
                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 12h16m0 0l-6-6m6 6l-6 6"/>
                </svg>
            </button>
        </form>

        <div class="login-footer">
            <span>Belum punya akun? Hubungi bagian administrasi.</span>
        </div>
    </GuestLayout>
</template>

<style scoped>
/* ============================================
   LOGIN FORM - ELEGANT
   ============================================ */
.login-head {
    text-align: center;
    margin-bottom: 0.25rem;
}
.login-brand {
    width: 64px;
    height: 64px;
    margin: 0 auto 1.25rem;
    border-radius: 1.25rem;
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 55%, #818cf8 100%);
    color: white;
    font-size: 1.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 12px 28px -6px rgba(79, 70, 229, 0.45);
}
.login-brand.has-logo {
    background: transparent;
    box-shadow: none;
}
.login-brand-img {
    width: 64px;
    height: 64px;
    object-fit: contain;
}
.login-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: #1e1b4b;
    margin: 0;
    letter-spacing: -0.02em;
}
.login-subtitle {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0.375rem 0 0;
}

.login-form {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

/* Refined inputs */
.login-form :deep(.input-group--lg .input) {
    border: 1.5px solid #e2e8f0 !important;
    border-radius: 0.75rem !important;
    padding: 0.75rem 2.75rem 0.75rem 2.75rem !important;
    background: #f8fafc !important;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s !important;
}
.login-form :deep(.input-group--lg .input:focus) {
    outline: none;
    border-color: #6366f1;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.14);
}
.login-form :deep(.input-group) {
    position: relative !important;
    display: flex !important;
    align-items: center !important;
}

.login-form :deep(.input-group__text:not(.input-group__text--toggle)) {
    position: absolute !important;
    left: 0rem !important;
    top: 0 !important;
    bottom: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 2.75rem !important;
    height: 100% !important;
    color: #9ca3af !important;
    pointer-events: none !important;
    padding: 0 !important;
    margin: 0 !important;
}

.login-form :deep(.input-group__text--toggle) {
    position: absolute !important;
    right: 0 !important;
    left: auto !important;
    top: 0 !important;
    bottom: 0 !important;
    transform: none !important;
    width: 2.75rem !important;
    height: 100% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    pointer-events: auto !important;
    cursor: pointer !important;
    background: none !important;
    border: none !important;
    padding: 0 !important;
    color: #9ca3af !important;
    z-index: 999 !important;
}

/* Elegant submit button */
.login-submit {
    margin-top: 0.75rem;
    padding: 0.875rem 1.5rem;
    border-radius: 0.75rem;
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
    box-shadow: 0 10px 24px -8px rgba(79, 70, 229, 0.5);
    font-weight: 700;
    letter-spacing: 0.01em;
}
.login-submit:hover {
    background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%);
    box-shadow: 0 14px 30px -8px rgba(79, 70, 229, 0.55);
    transform: translateY(-1px);
}
.login-submit:active {
    transform: translateY(0);
}
.login-submit:disabled {
    opacity: 0.6;
    box-shadow: none;
    transform: none;
}

.login-footer {
    margin-top: 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.875rem;
    font-size: 0.8125rem;
    color: #9ca3af;
}
.admin-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1.25rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 2rem;
    color: #4f46e5;
    font-weight: 600;
    background: #fafafa;
    transition: all 0.2s;
    text-decoration: none;
}
.admin-link:hover {
    border-color: #6366f1;
    background: #eef2ff;
    text-decoration: none;
}
</style>
