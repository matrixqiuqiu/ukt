<script setup>
import InputError from '@/Components/InputError.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <form @submit.prevent="form.patch(route('profile.update'))">
        <div class="profile-form-group">
            <label for="name" class="profile-form-label">Nama Lengkap</label>
            <input
                id="name"
                type="text"
                class="profile-form-input"
                v-model="form.name"
                required
                autofocus
                autocomplete="name"
            />
            <InputError class="profile-form-error" :message="form.errors.name" />
        </div>

        <div class="profile-form-group">
            <label for="email" class="profile-form-label">Email</label>
            <input
                id="email"
                type="email"
                class="profile-form-input"
                v-model="form.email"
                required
                autocomplete="username"
            />
            <InputError class="profile-form-error" :message="form.errors.email" />
        </div>

        <div v-if="mustVerifyEmail && user.email_verified_at === null" class="profile-form-alert profile-form-alert--warning">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                Email belum terverifikasi.
                <Link
                    :href="route('verification.send')"
                    method="post"
                    as="button"
                    class="profile-form-link"
                >
                    Klik untuk kirim ulang verifikasi.
                </Link>
            </div>
        </div>

        <div v-show="status === 'verification-link-sent'" class="profile-form-alert profile-form-alert--success">
            <i class="fas fa-check-circle"></i>
            Link verifikasi baru telah dikirim ke email Anda.
        </div>

        <div class="profile-form-actions">
            <button type="submit" class="profile-form-btn profile-form-btn--primary" :disabled="form.processing">
                <i class="fas fa-save"></i>
                {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
            </button>
            <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                <span v-if="form.recentlySuccessful" class="profile-form-success">
                    <i class="fas fa-check-circle"></i> Tersimpan
                </span>
            </Transition>
        </div>
    </form>
</template>

<style scoped>
.profile-form-group {
    margin-bottom: 1.25rem;
}

.profile-form-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
}

.profile-form-input {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 1.5px solid var(--gray-200);
    border-radius: 0.75rem;
    font-size: 0.9375rem;
    font-family: inherit;
    color: var(--gray-900);
    background: white;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.profile-form-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.profile-form-input::placeholder {
    color: var(--gray-400);
}

.profile-form-error {
    font-size: 0.75rem;
    color: var(--danger);
    margin-top: 0.375rem;
}

.profile-form-alert {
    display: flex;
    align-items: flex-start;
    gap: 0.625rem;
    padding: 0.875rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    margin-bottom: 1.25rem;
    line-height: 1.5;
}

.profile-form-alert--warning {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}

.profile-form-alert--success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.profile-form-alert i {
    margin-top: 0.125rem;
    flex-shrink: 0;
}

.profile-form-link {
    color: var(--primary);
    text-decoration: underline;
    background: none;
    border: none;
    cursor: pointer;
    font-size: inherit;
    font-family: inherit;
    padding: 0;
}

.profile-form-link:hover {
    color: var(--primary-dark);
}

.profile-form-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 1.5rem;
}

.profile-form-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border-radius: 2rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    font-family: inherit;
}

.profile-form-btn--primary {
    background: var(--primary);
    color: white;
}

.profile-form-btn--primary:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.profile-form-btn--primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.profile-form-success {
    font-size: 0.8125rem;
    color: var(--success);
    font-weight: 500;
}
</style>
