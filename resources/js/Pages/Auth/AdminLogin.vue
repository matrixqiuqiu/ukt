<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const togglePassword = () => {
    showPassword.value = !showPassword.value;
    console.log('Password toggled:', showPassword.value);
};

const submit = () => {
    form.post(route('admin.login.store'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Admin Login" />

        <div v-if="status" class="status-message">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-4">
            <div style="text-align:center;margin-bottom:0.5rem;">
                <h2 style="font-size:1.5rem;font-weight:700;color:var(--gray-900);margin:0;">Admin Panel</h2>
                <p style="font-size:0.875rem;color:var(--gray-500);margin:0.25rem 0 0;">Masuk sebagai administrator</p>
            </div>

            <!-- Email -->
            <div class="field">
                <label for="loginEmail" class="field__label">Email</label>
                <div class="input-group input-group--lg">
                    <span class="input-group__text">
                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true">
                            <g fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M2 12c0-3.771 0-5.657 1.172-6.828S6.229 4 10 4h4c3.771 0 5.657 0 6.828 1.172S22 8.229 22 12s0 5.657-1.172 6.828S17.771 20 14 20h-4c-3.771 0-5.657 0-6.828-1.172S2 15.771 2 12Z"/>
                                <path stroke-linecap="round" d="m6 8l2.159 1.8c1.837 1.53 2.755 2.295 3.841 2.295s2.005-.765 3.841-2.296L18 8"/>
                            </g>
                        </svg>
                    </span>
                    <input
                        id="loginEmail"
                        type="email"
                        class="input"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="admin@ubg.ac.id"
                    />
                </div>
                <InputError class="form-error" :message="form.errors.email" />
            </div>

            <!-- Password -->
            <div class="field">
                <div class="flex items-center justify-between gap-2">
                    <label for="loginPassword" class="field__label">Password</label>
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
                        id="loginPassword"
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

            <div class="field__check">
                <input type="checkbox" id="loginRemember" v-model="form.remember" />
                <label for="loginRemember">Keep me signed in</label>
            </div>

            <button
                type="submit"
                class="button--primary"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Signing in...' : 'Sign in as Admin' }}
                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 12h16m0 0l-6-6m6 6l-6 6"/>
                </svg>
            </button>
        </form>


    </GuestLayout>
</template>
