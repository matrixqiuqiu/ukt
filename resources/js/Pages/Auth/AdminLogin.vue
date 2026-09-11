<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
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
};

const submit = () => {
  form.post(route('admin.login.store'), {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="Login Administrator - Portal UKT" />

    <!-- Status Message Alert -->
    <div v-if="status" class="alert-status-box">
      <i class="fas fa-info-circle"></i> {{ status }}
    </div>

    <!-- Login Header -->
    <div class="auth-header">
      <div class="role-badge-admin">
        <i class="fas fa-user-shield"></i> PANEL ADMINISTRATOR
      </div>
      <h2 class="auth-title">Masuk Administrator</h2>
      <p class="auth-subtitle">Akses khusus staf pengelola keuangan &amp; admisi universitas</p>
    </div>

    <!-- Notice Box Restricted Area -->
    <div class="restricted-notice-box">
      <div class="notice-icon"><i class="fas fa-lock"></i></div>
      <div class="notice-text">
        <strong>Akses Terbatas &amp; Terenkripsi</strong>
        <p>Gunakan akun email resmi staf administrator yang telah diberikan wewenang pengelolaan UKT.</p>
      </div>
    </div>

    <!-- Form -->
    <form @submit.prevent="submit" class="auth-form-body">
      <!-- Email Field -->
      <div class="form-field-solid">
        <label for="adminEmail">Alamat Email Staf</label>
        <div class="input-control-wrap" :class="{ 'has-error': form.errors.email }">
          <span class="input-icon"><i class="fas fa-envelope"></i></span>
          <input
            id="adminEmail"
            type="email"
            class="input-native"
            v-model="form.email"
            required
            autofocus
            autocomplete="email"
            placeholder="admin@universitasbumigora.ac.id"
          />
        </div>
        <InputError class="field-error-msg" :message="form.errors.email" />
      </div>

      <!-- Password Field -->
      <div class="form-field-solid">
        <label for="adminPassword">Kata Sandi</label>
        <div class="input-control-wrap" :class="{ 'has-error': form.errors.password }">
          <span class="input-icon"><i class="fas fa-key"></i></span>
          <input
            id="adminPassword"
            :type="showPassword ? 'text' : 'password'"
            class="input-native"
            v-model="form.password"
            required
            autocomplete="current-password"
            placeholder="Masukkan kata sandi Anda"
          />
          <button
            type="button"
            class="input-toggle-btn"
            @click="togglePassword"
            :title="showPassword ? 'Sembunyikan password' : 'Lihat password'"
          >
            <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
          </button>
        </div>
        <InputError class="field-error-msg" :message="form.errors.password" />
      </div>

      <!-- Remember Me -->
      <div class="remember-row">
        <label class="checkbox-label">
          <input type="checkbox" v-model="form.remember" class="checkbox-native" />
          <span>Ingat sesi saya di perangkat ini</span>
        </label>
      </div>

      <!-- Submit Button -->
      <button
        type="submit"
        class="btn-submit-admin"
        :disabled="form.processing"
      >
        <i class="fas" :class="form.processing ? 'fa-spinner fa-pulse' : 'fa-shield-alt'"></i>
        <span>{{ form.processing ? 'Memverifikasi Kredensial...' : 'Masuk Panel Admin' }}</span>
      </button>
    </form>

    <!-- Switch to Mahasiswa Login -->
    <div class="switch-portal-box">
      <span>Mahasiswa yang ingin melakukan pembayaran?</span>
      <Link :href="route('login')" class="switch-portal-link">
        <i class="fas fa-graduation-cap"></i> Masuk Portal Mahasiswa (SIAKAD)
      </Link>
    </div>
  </GuestLayout>
</template>

<style scoped>
/* Header */
.auth-header {
  text-align: center;
  margin-bottom: 1.25rem;
}

.role-badge-admin {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.25rem 0.625rem;
  background: #0f172a;
  border: 1px solid #1e293b;
  color: #f8fafc;
  font-size: 0.6875rem;
  font-weight: 800;
  border-radius: 9999px;
  letter-spacing: 0.5px;
  margin-bottom: 0.75rem;
}

.role-badge-admin i {
  color: #38bdf8;
}

.auth-title {
  font-size: 1.375rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 0.375rem;
  letter-spacing: -0.02em;
}

.auth-subtitle {
  font-size: 0.8125rem;
  color: #64748b;
  margin: 0;
  line-height: 1.4;
}

/* Alert Box */
.alert-status-box {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1d4ed8;
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  font-size: 0.8125rem;
  font-weight: 600;
  margin-bottom: 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Notice Box Restricted */
.restricted-notice-box {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.625rem;
  padding: 0.75rem 0.875rem;
  margin-bottom: 1.5rem;
}

.notice-icon {
  width: 32px;
  height: 32px;
  background: #0f172a;
  border: 1px solid #1e293b;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #38bdf8;
  font-size: 0.875rem;
  flex-shrink: 0;
  margin-top: 0.125rem;
}

.notice-text strong {
  display: block;
  font-size: 0.75rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 0.125rem;
}

.notice-text p {
  margin: 0;
  font-size: 0.6875rem;
  color: #64748b;
  line-height: 1.35;
}

/* Form Fields */
.auth-form-body {
  display: flex;
  flex-direction: column;
  gap: 1.125rem;
}

.form-field-solid label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #334155;
  margin-bottom: 0.375rem;
}

.input-control-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 0.875rem;
  color: #94a3b8;
  font-size: 0.875rem;
  pointer-events: none;
}

.input-native {
  width: 100%;
  padding: 0.625rem 2.5rem 0.625rem 2.375rem;
  border: 1px solid #cbd5e1;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  color: #0f172a;
  background: #ffffff;
  outline: none;
  transition: all 0.15s ease-in-out;
}

.input-native:focus {
  border-color: #0f172a;
  box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.12);
}

.input-control-wrap.has-error .input-native {
  border-color: #dc2626;
}

.input-toggle-btn {
  position: absolute;
  right: 0.75rem;
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.25rem;
  font-size: 0.875rem;
}

.input-toggle-btn:hover {
  color: #475569;
}

.field-error-msg {
  font-size: 0.75rem;
  color: #dc2626;
  margin-top: 0.25rem;
}

/* Remember Row */
.remember-row {
  display: flex;
  align-items: center;
}

.checkbox-label {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8125rem;
  color: #475569;
  cursor: pointer;
  user-select: none;
}

.checkbox-native {
  width: 16px;
  height: 16px;
  accent-color: #0f172a;
  cursor: pointer;
}

/* Submit Button */
.btn-submit-admin {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.6875rem 1rem;
  background: #0f172a;
  color: #ffffff;
  border: 1px solid #0f172a;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.15s ease-in-out;
  margin-top: 0.5rem;
}

.btn-submit-admin:hover:not(:disabled) {
  background: #1e293b;
  border-color: #1e293b;
  box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.2);
}

.btn-submit-admin:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

/* Switch Portal Box */
.switch-portal-box {
  margin-top: 1.5rem;
  padding-top: 1.25rem;
  border-top: 1px dashed #e2e8f0;
  text-align: center;
  font-size: 0.8125rem;
  color: #64748b;
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.switch-portal-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  color: #2563eb;
  font-weight: 700;
  text-decoration: none;
  padding: 0.25rem 0.5rem;
  border-radius: 0.375rem;
  transition: background 0.15s;
}

.switch-portal-link:hover {
  background: #eff6ff;
  color: #1d4ed8;
}
</style>
