<script setup>
import { Link, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const theme = computed(() => page.props.theme);
const logoUrl = computed(() => theme.value?.invoice_logo || '');
const websiteName = computed(() => theme.value?.website_name || 'UKT System');
const websiteShortName = computed(() => theme.value?.website_short_name || websiteName.value);
const websiteTagline = computed(() => theme.value?.website_tagline || 'Kelola pembayaran uang kuliah tunggal Anda secara mudah dan cepat melalui platform daring kami.');
const websiteFooterText = computed(() => theme.value?.website_footer_text || ('&copy; ' + new Date().getFullYear() + ' ' + (theme.value?.invoice_institution_name || 'Institusi') + '. All rights reserved.'));
const institutionName = computed(() => theme.value?.invoice_institution_name || 'Institusi');
</script>

<template>
    <Head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    </Head>

    <div class="auth">
        <!-- LEFT: Form Panel -->
        <section class="auth__panel">
            <div class="auth__card">
                <div class="auth__form">
                    <slot />
                    <div class="auth__footer" v-html="websiteFooterText"></div>
                </div>
            </div>
        </section>

        <!-- RIGHT: Brand Panel -->
        <aside class="auth__aside">
            <Link href="/" class="auth__brand">
                <span class="auth__brand-mark" :class="{ 'has-logo': logoUrl }">
                    <img v-if="logoUrl" :src="logoUrl" alt="Logo" class="auth__brand-logo" />
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 1.5l3.4 7.1 7.1 3.4-7.1 3.4-3.4 7.1-3.4-7.1L1.5 12l7.1-3.4z" opacity=".45"/>
                        <path d="M12 1.5l3.4 7.1L12 12 8.6 8.6z"/>
                    </svg>
                </span>
                <span class="auth__brand-text">
                    <span class="auth__brand-name">{{ websiteName }}</span>
                </span>
            </Link>

            <div class="auth__pitch">
                <h2 class="auth__pitch-title">
                    Sistem Pembayaran
                    <span>{{ websiteShortName }} Online</span>
                </h2>
                <p class="auth__pitch-lede">
                    {{ websiteTagline }}
                </p>
                <ul class="auth__features">
                    <li>
                        <span class="auth__feature-icon"><i class="fas fa-shield-halved"></i></span>
                        <span>
                            <strong>Aman &amp; Terpercaya</strong>
                            <small>Transaksi diproses melalui gateway bank resmi</small>
                        </span>
                    </li>
                    <li>
                        <span class="auth__feature-icon"><i class="fas fa-bolt"></i></span>
                        <span>
                            <strong>Cepat &amp; Praktis</strong>
                            <small>Bayar kapan saja via Virtual Account atau transfer</small>
                        </span>
                    </li>
                    <li>
                        <span class="auth__feature-icon"><i class="fas fa-file-invoice"></i></span>
                        <span>
                            <strong>Bukti Digital</strong>
                            <small>Riwayat dan bukti pembayaran tersimpan otomatis</small>
                        </span>
                    </li>
                </ul>
            </div>

            <div class="auth__aside-footer" v-html="websiteFooterText"></div>
        </aside>
    </div>
</template>

<style scoped>
/* ============================================
   AUTH LAYOUT - ELEGANT
   ============================================ */
.auth {
    display: flex;
    min-height: 100vh;
    background: linear-gradient(135deg, #f5f6fb 0%, #edf0f9 55%, #e7ebf7 100%);
    position: relative;
    overflow: hidden;
}

/* Decorative background orbs */
.auth::before,
.auth::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
}
.auth::before {
    width: 480px;
    height: 480px;
    top: -180px;
    left: -160px;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.14) 0%, transparent 65%);
}
.auth::after {
    width: 520px;
    height: 520px;
    bottom: -220px;
    left: 32%;
    background: radial-gradient(circle, rgba(129, 140, 248, 0.12) 0%, transparent 65%);
}

/* --- Left panel --- */
.auth__panel {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    background: transparent;
    position: relative;
    z-index: 1;
}

/* Floating form card */
.auth__card {
    width: 100%;
    max-width: 460px;
    background: #ffffff;
    border-radius: 1.5rem;
    border: 1px solid rgba(226, 232, 240, 0.7);
    box-shadow: 0 24px 60px -12px rgba(30, 27, 75, 0.18);
    padding: 2.75rem 2.5rem 2rem;
    position: relative;
    animation: authCardIn 0.45s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes authCardIn {
    from {
        opacity: 0;
        transform: translateY(16px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.auth__form {
    width: 100%;
    max-width: none;
    gap: 1.5rem;
}

/* --- Right brand panel --- */
.auth__aside {
    width: min(46%, 560px);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 3rem 2.75rem;
    margin: 1.25rem;
    background: linear-gradient(160deg, #101a3c 0%, #1e1b4b 55%, #312e81 100%);
    color: white;
    position: relative;
    overflow: hidden;
    border-radius: 1.75rem;
    z-index: 1;
}

/* Glow orbs on brand panel */
.auth__aside::before {
    content: '';
    position: absolute;
    width: 340px;
    height: 340px;
    top: -120px;
    right: -120px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(129, 140, 248, 0.35) 0%, transparent 65%);
    pointer-events: none;
}
.auth__aside::after {
    content: '';
    position: absolute;
    width: 300px;
    height: 300px;
    bottom: -100px;
    left: -100px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.28) 0%, transparent 65%);
    pointer-events: none;
}

.auth__brand {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: white;
    position: relative;
    z-index: 1;
}
.auth__brand-mark {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.22), rgba(255, 255, 255, 0.08));
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 0.75rem;
    font-size: 1.3rem;
    backdrop-filter: blur(8px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}
.auth__brand-mark.has-logo {
    background: transparent;
    border: none;
    box-shadow: none;
    backdrop-filter: none;
}
.auth__brand-logo {
    width: 36px;
    height: 36px;
    object-fit: contain;
}
.auth__brand-text {
    display: flex;
    flex-direction: column;
    line-height: 1.1;
}
.auth__brand-name {
    font-size: 1.125rem;
    font-weight: 700;
    letter-spacing: -0.01em;
}

.auth__pitch {
    position: relative;
    z-index: 1;
}
.auth__pitch-title {
    font-size: 2.25rem;
    font-weight: 800;
    line-height: 1.15;
    margin: 0 0 1rem;
    letter-spacing: -0.03em;
}
.auth__pitch-title span {
    display: block;
    background: linear-gradient(135deg, #a5b4fc 0%, #c7d2fe 60%, #e0e7ff 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.auth__pitch-lede {
    font-size: 0.9375rem;
    line-height: 1.65;
    color: rgba(255, 255, 255, 0.72);
    margin: 0 0 2rem;
    max-width: 340px;
}

.auth__features {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 1.125rem;
}
.auth__features li {
    display: flex;
    align-items: center;
    gap: 0.875rem;
}
.auth__feature-icon {
    width: 40px;
    height: 40px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.75rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.14);
    color: #a5b4fc;
    font-size: 0.9375rem;
}
.auth__features li strong {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.95);
}
.auth__features li small {
    display: block;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.55);
    margin-top: 0.125rem;
}

.auth__aside-footer {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.4);
    position: relative;
    z-index: 1;
}

/* Footer text under form */
.auth__footer {
    text-align: center;
    font-size: 0.8125rem;
    color: var(--gray-500, #6b7280);
    margin-top: 1.5rem;
    padding-top: 1.25rem;
    border-top: 1px solid var(--gray-100, #f3f4f6);
}

/* --- Responsive --- */
@media (max-width: 992px) {
    .auth__aside {
        display: none;
    }
    .auth__card {
        max-width: 440px;
    }
}
@media (max-width: 576px) {
    .auth__card {
        padding: 2rem 1.5rem 1.5rem;
        border-radius: 1.25rem;
    }
    .auth__panel {
        padding: 1rem;
    }
}
</style>
