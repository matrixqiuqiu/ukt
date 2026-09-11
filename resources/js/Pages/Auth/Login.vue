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
const institutionPhone = computed(() => page.props.theme?.invoice_institution_phone || '(0370) 634498');
const institutionEmail = computed(() => page.props.theme?.invoice_institution_email || 'info@universitasbumigora.ac.id');

const form = useForm({
  nim: '',
  password: '',
});

const showPassword = ref(false);
const showHelpModal = ref(false);

const togglePassword = () => {
  showPassword.value = !showPassword.value;
};

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="Login Mahasiswa - Portal UKT" />

    <!-- Status Message Alert -->
    <div v-if="status" class="alert-status-box">
      <i class="fas fa-info-circle"></i> {{ status }}
    </div>

    <!-- Login Header -->
    <div class="auth-header">
      <div v-if="page.props.theme?.logo_url || page.props.theme?.invoice_logo" class="auth-brand-logo-wrap">
        <img :src="page.props.theme?.logo_url || page.props.theme?.invoice_logo" alt="Logo Institusi" class="auth-brand-logo-img" />
      </div>
      <div class="role-badge">
        <span class="badge-dot"></span> PORTAL MAHASISWA
      </div>
      <h2 class="auth-title">Masuk Akun Mahasiswa</h2>
      <p class="auth-subtitle">Gunakan Nomor Induk Mahasiswa (NIM) dan Password SIAKAD Anda</p>
    </div>

    <!-- Info Box SIAKAD Integration -->
    <div class="siakad-notice-box">
      <div class="notice-icon"><i class="fas fa-university"></i></div>
      <div class="notice-text">
        <strong>Terintegrasi dengan SIAKAD</strong>
        <p>Login menggunakan akun portal akademik resmi kampus.</p>
      </div>
    </div>

    <!-- Form -->
    <form @submit.prevent="submit" class="auth-form-body">
      <!-- NIM Field -->
      <div class="form-field-solid">
        <label for="loginNim">NIM (Nomor Induk Mahasiswa)</label>
        <div class="input-control-wrap" :class="{ 'has-error': form.errors.nim }">
          <span class="input-icon"><i class="fas fa-id-card"></i></span>
          <input
            id="loginNim"
            type="text"
            class="input-native"
            v-model="form.nim"
            required
            autofocus
            autocomplete="username"
            placeholder="Masukkan NIM Anda"
          />
        </div>
        <InputError class="field-error-msg" :message="form.errors.nim" />
      </div>

      <!-- Password Field -->
      <div class="form-field-solid">
        <div class="field-label-row">
          <label for="loginPassword">Password SIAKAD</label>
          <button type="button" class="link-help-btn" @click="showHelpModal = true">
            <i class="fas fa-question-circle"></i> Bantuan?
          </button>
        </div>
        <div class="input-control-wrap" :class="{ 'has-error': form.errors.password }">
          <span class="input-icon"><i class="fas fa-lock"></i></span>
          <input
            id="loginPassword"
            :type="showPassword ? 'text' : 'password'"
            class="input-native"
            v-model="form.password"
            required
            autocomplete="current-password"
            placeholder="Masukkan Password SIAKAD"
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

      <!-- Submit Button -->
      <button
        type="submit"
        class="btn-submit-solid"
        :disabled="form.processing"
      >
        <i class="fas" :class="form.processing ? 'fa-spinner fa-pulse' : 'fa-sign-in-alt'"></i>
        <span>{{ form.processing ? 'Memverifikasi ke SIAKAD...' : 'Masuk Sekarang' }}</span>
      </button>
    </form>

    <!-- Modal Bantuan Login (Teleported) -->
    <Teleport to="body">
      <div v-if="showHelpModal" class="modal-overlay" @click.self="showHelpModal = false">
        <div class="modal-box">
          <div class="modal-header">
            <h3><i class="fas fa-headset text-primary"></i> Bantuan Login Mahasiswa</h3>
            <button type="button" class="modal-close" @click="showHelpModal = false"><i class="fas fa-times"></i></button>
          </div>
          <div class="modal-body">
            <div class="help-info-card">
              <h4><i class="fas fa-key"></i> Lupa Password SIAKAD?</h4>
              <p>Password yang digunakan pada portal UKT sama persis dengan password login <strong>SIAKAD</strong> Anda. Jika Anda lupa password, silakan lakukan reset melalui portal SIAKAD resmi atau hubungi Pusat Teknologi Informasi &amp; Komunikasi (PTIK) kampus.</p>
            </div>

            <div class="help-info-card" style="margin-top:0.75rem;">
              <h4><i class="fas fa-phone-alt"></i> Layanan BAAK &amp; Keuangan:</h4>
              <ul class="help-contact-list">
                <li><i class="fas fa-phone"></i> Telepon: <strong>{{ institutionPhone }}</strong></li>
                <li><i class="fas fa-envelope"></i> Email: <strong>{{ institutionEmail }}</strong></li>
                <li><i class="fas fa-clock"></i> Jam Pelayanan: <strong>Senin - Jumat (08.00 - 16.00 WITA)</strong></li>
              </ul>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-modal-close" @click="showHelpModal = false">Tutup</button>
          </div>
        </div>
      </div>
    </Teleport>
  </GuestLayout>
</template>

<style scoped>
/* Header */
.auth-header {
  text-align: center;
  margin-bottom: 1.25rem;
}

.auth-brand-logo-wrap {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: 0.875rem;
}

.auth-brand-logo-img {
  max-height: 56px;
  max-width: 180px;
  object-fit: contain;
}

.role-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.25rem 0.625rem;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1d4ed8;
  font-size: 0.6875rem;
  font-weight: 800;
  border-radius: 9999px;
  letter-spacing: 0.5px;
  margin-bottom: 0.75rem;
}

.badge-dot {
  width: 6px;
  height: 6px;
  background: #2563eb;
  border-radius: 50%;
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

/* SIAKAD Notice Box */
.siakad-notice-box {
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
  background: #eff6ff;
  border: 1px solid #dbeafe;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #2563eb;
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

.field-label-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.375rem;
}

.field-label-row label {
  margin-bottom: 0;
}

.link-help-btn {
  background: none;
  border: none;
  color: #2563eb;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.link-help-btn:hover {
  text-decoration: underline;
  color: #1d4ed8;
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
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
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

/* Submit Button */
.btn-submit-solid {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.6875rem 1rem;
  background: #2563eb;
  color: #ffffff;
  border: 1px solid #2563eb;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.15s ease-in-out;
  margin-top: 0.5rem;
}

.btn-submit-solid:hover:not(:disabled) {
  background: #1d4ed8;
  border-color: #1d4ed8;
  box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
}

.btn-submit-solid:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
  padding: 1rem;
}

.modal-box {
  background: #ffffff;
  border-radius: 0.75rem;
  width: 100%;
  max-width: 460px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.modal-close {
  background: none;
  border: none;
  font-size: 1rem;
  color: #94a3b8;
  cursor: pointer;
}

.modal-body {
  padding: 1.25rem;
}

.help-info-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  padding: 0.875rem 1rem;
}

.help-info-card h4 {
  margin: 0 0 0.375rem;
  font-size: 0.8125rem;
  font-weight: 700;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: 0.375rem;
}

.help-info-card p {
  margin: 0;
  font-size: 0.75rem;
  color: #475569;
  line-height: 1.45;
}

.help-contact-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  font-size: 0.75rem;
  color: #334155;
}

.help-contact-list li {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.help-contact-list i {
  color: #2563eb;
  width: 14px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  padding: 0.75rem 1.25rem;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
  border-bottom-left-radius: 0.75rem;
  border-bottom-right-radius: 0.75rem;
}

.btn-modal-close {
  padding: 0.4375rem 0.875rem;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 0.375rem;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
}

.btn-modal-close:hover {
  background: #f1f5f9;
}
</style>
