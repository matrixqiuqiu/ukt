<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Daftar" />

        <form @submit.prevent="submit">
            <div class="form-group">
                <label for="name" class="form-label">Nama Lengkap</label>
                <input
                    id="name"
                    type="text"
                    class="input"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Masukkan nama lengkap Anda"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input
                    id="email"
                    type="email"
                    class="input"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="Masukkan email Anda"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input
                    id="password"
                    type="password"
                    class="input"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                    placeholder="Masukkan password Anda"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                <input
                    id="password_confirmation"
                    type="password"
                    class="input"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Ulangi password Anda"
                />
                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="form-group d-flex align-items-center justify-content-between">
                <Link
                    :href="route('login')"
                    style="font-size: 0.875rem; color: var(--color-muted-foreground, #878a99); text-decoration: none;"
                >
                    Sudah punya akun? Masuk
                </Link>

                <button
                    type="submit"
                    class="button button--primary"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Daftar
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
