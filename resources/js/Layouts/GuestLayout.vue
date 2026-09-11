<script setup>
import { Link, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const theme = computed(() => page.props.theme);
const logoUrl = computed(() => theme.value?.logo_url || theme.value?.invoice_logo || '');
const websiteName = computed(() => theme.value?.website_name || 'Sistem Informasi UKT');
const websiteShortName = computed(() => theme.value?.website_short_name || 'UKT UBG');
const websiteTagline = computed(() => theme.value?.website_tagline || 'Sistem Pengelolaan & Pembayaran UKT Online Mahasiswa');
const websiteFooterText = computed(() => theme.value?.website_footer_text || ('© ' + new Date().getFullYear() + ' ' + (theme.value?.invoice_institution_name || 'Universitas Bumigora') + '. All rights reserved.'));
const institutionName = computed(() => theme.value?.invoice_institution_name || 'Universitas Bumigora');
</script>

<template>
  <Head>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  </Head>

  <div class="auth-wrapper">
    <!-- LEFT: Form Card Panel -->
    <section class="auth-panel-left">
      <div class="auth-form-card">
        <slot />
        <div class="auth-card-footer" v-html="websiteFooterText"></div>
      </div>
    </section>

    <!-- RIGHT: Brand & Feature Panel -->
    <aside class="auth-panel-right">
      <div class="brand-header">
        <Link href="/" class="brand-link">
          <div class="brand-logo-wrap" :class="{ 'has-img': logoUrl }">
            <img v-if="logoUrl" :src="logoUrl" alt="Logo" class="brand-logo-img" />
            <i v-else class="fas fa-university brand-logo-icon"></i>
          </div>
          <div class="brand-text">
            <span class="brand-title">{{ websiteShortName }}</span>
            <span class="brand-subtitle">{{ institutionName }}</span>
          </div>
        </Link>
      </div>

      <div class="brand-body">
        <div class="brand-pill">
          <span class="pill-dot"></span> Portal Resmi Pembayaran Biaya Kuliah
        </div>
        <h2 class="brand-heading">
          Sistem Pembayaran
          <span class="heading-accent">{{ websiteShortName }} Terpadu</span>
        </h2>
        <p class="brand-description">
          {{ websiteTagline }}
        </p>

        <div class="features-list">
          <div class="feature-item">
            <div class="feature-icon">
              <i class="fas fa-shield-alt"></i>
            </div>
            <div class="feature-content">
              <h4>Bank NTB Syariah Virtual Account</h4>
              <p>Nomor VA resmi unik terbit otomatis dengan jatuh tempo akurat</p>
            </div>
          </div>

          <div class="feature-item">
            <div class="feature-icon">
              <i class="fas fa-bolt"></i>
            </div>
            <div class="feature-content">
              <h4>Verifikasi & Sinkronisasi Instan</h4>
              <p>Status lunas otomatis tersinkronisasi dengan portal SIAKAD</p>
            </div>
          </div>

          <div class="feature-item">
            <div class="feature-icon">
              <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="feature-content">
              <h4>Kwitansi & Bukti Sah Digital</h4>
              <p>Unduh invoice resmi berstempel dan cetak kapan saja</p>
            </div>
          </div>
        </div>
      </div>

      <div class="brand-footer">
        <div class="footer-security">
          <i class="fas fa-lock"></i> Koneksi Terenkripsi &amp; Terintegrasi API SIAKAD
        </div>
      </div>
    </aside>
  </div>
</template>

<style scoped>
.auth-wrapper {
  display: flex;
  min-height: 100vh;
  background: #f8fafc;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

/* Left Panel (Form) */
.auth-panel-left {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2.5rem 1.5rem;
}

.auth-form-card {
  width: 100%;
  max-width: 460px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 1rem;
  box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.06), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
  padding: 2.5rem 2.25rem 2rem;
}

.auth-card-footer {
  text-align: center;
  font-size: 0.75rem;
  color: #94a3b8;
  margin-top: 1.75rem;
  padding-top: 1.25rem;
  border-top: 1px solid #f1f5f9;
  line-height: 1.5;
}

/* Right Panel (Brand) */
.auth-panel-right {
  width: min(45%, 540px);
  background: #0f172a;
  color: #ffffff;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 3rem 2.75rem;
  margin: 1rem;
  border-radius: 1.25rem;
  border: 1px solid #1e293b;
  position: relative;
  overflow: hidden;
}

/* Accent Top Line */
.auth-panel-right::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: #2563eb;
}

.brand-header {
  margin-bottom: 2rem;
}

.brand-link {
  display: inline-flex;
  align-items: center;
  gap: 0.875rem;
  text-decoration: none;
  color: #ffffff;
}

.brand-logo-wrap {
  width: 44px;
  height: 44px;
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.375rem;
}

.brand-logo-wrap.has-img {
  background: #ffffff;
  border-color: #e2e8f0;
}

.brand-logo-img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.brand-logo-icon {
  font-size: 1.25rem;
  color: #38bdf8;
}

.brand-text {
  display: flex;
  flex-direction: column;
}

.brand-title {
  font-size: 1.125rem;
  font-weight: 800;
  letter-spacing: -0.01em;
}

.brand-subtitle {
  font-size: 0.75rem;
  color: #94a3b8;
  font-weight: 500;
}

/* Brand Body */
.brand-body {
  margin: auto 0;
  padding: 1.5rem 0;
}

.brand-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.3125rem 0.75rem;
  background: rgba(37, 99, 235, 0.15);
  border: 1px solid rgba(59, 130, 246, 0.3);
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  color: #93c5fd;
  margin-bottom: 1.25rem;
}

.pill-dot {
  width: 6px;
  height: 6px;
  background: #38bdf8;
  border-radius: 50%;
}

.brand-heading {
  font-size: 2rem;
  font-weight: 800;
  line-height: 1.2;
  margin: 0 0 0.875rem;
  letter-spacing: -0.02em;
}

.heading-accent {
  display: block;
  color: #60a5fa;
}

.brand-description {
  font-size: 0.9375rem;
  line-height: 1.6;
  color: #94a3b8;
  margin: 0 0 2rem;
  max-width: 400px;
}

/* Features List */
.features-list {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.feature-item {
  display: flex;
  align-items: flex-start;
  gap: 0.875rem;
}

.feature-icon {
  width: 38px;
  height: 38px;
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 0.625rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #60a5fa;
  font-size: 0.9375rem;
  flex-shrink: 0;
  margin-top: 0.125rem;
}

.feature-content h4 {
  margin: 0 0 0.1875rem;
  font-size: 0.875rem;
  font-weight: 700;
  color: #f8fafc;
}

.feature-content p {
  margin: 0;
  font-size: 0.75rem;
  color: #94a3b8;
  line-height: 1.4;
}

/* Brand Footer */
.brand-footer {
  margin-top: 2rem;
  padding-top: 1.25rem;
  border-top: 1px solid #1e293b;
}

.footer-security {
  font-size: 0.75rem;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.footer-security i {
  color: #10b981;
}

/* Responsive */
@media (max-width: 991px) {
  .auth-panel-right {
    display: none;
  }
  .auth-panel-left {
    padding: 1.5rem 1rem;
  }
  .auth-form-card {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  }
}

@media (max-width: 480px) {
  .auth-form-card {
    padding: 1.75rem 1.25rem 1.5rem;
    border-radius: 0.75rem;
  }
}
</style>
