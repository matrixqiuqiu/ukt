<script setup>
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <form @submit.prevent="updatePassword">
        <div class="profile-form-group">
            <label for="current_password" class="profile-form-label">Password Saat Ini</label>
            <input
                id="current_password"
                ref="currentPasswordInput"
                v-model="form.current_password"
                type="password"
                class="profile-form-input"
                autocomplete="current-password"
            />
            <InputError :message="form.errors.current_password" class="profile-form-error" />
        </div>

        <div class="profile-form-group">
            <label for="password" class="profile-form-label">Password Baru</label>
            <input
                id="password"
                ref="passwordInput"
                v-model="form.password"
                type="password"
                class="profile-form-input"
                autocomplete="new-password"
            />
            <InputError :message="form.errors.password" class="profile-form-error" />
        </div>

        <div class="profile-form-group">
            <label for="password_confirmation" class="profile-form-label">Konfirmasi Password</label>
            <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                class="profile-form-input"
                autocomplete="new-password"
            />
            <InputError :message="form.errors.password_confirmation" class="profile-form-error" />
        </div>

        <div class="profile-form-actions">
            <button type="submit" class="profile-form-btn profile-form-btn--primary" :disabled="form.processing">
                <i class="fas fa-key"></i>
                {{ form.processing ? 'Menyimpan...' : 'Ubah Password' }}
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
