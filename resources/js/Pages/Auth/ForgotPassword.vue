<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Lupa Password" />

        <div style="font-size: 0.875rem; color: var(--color-muted-foreground, #878a99); margin-bottom: 1rem;">
            Lupa password? Masukkan email Anda dan kami akan mengirimkan link reset password.
        </div>

        <div
            v-if="status"
            class="mb-4 text-sm font-medium"
            style="color: oklch(0.55 0.15 145);"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input
                    id="email"
                    type="email"
                    class="input"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Masukkan email Anda"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="form-group d-flex align-items-center justify-content-between">
                <Link
                    :href="route('login')"
                    style="font-size: 0.875rem; color: var(--color-muted-foreground, #878a99); text-decoration: none;"
                >
                    Kembali ke halaman masuk
                </Link>

                <button
                    type="submit"
                    class="button button--primary"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Kirim Link Reset Password
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
