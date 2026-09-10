<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { formatRupiah, formatDate, formatDateTime } from '@/utils';
import { useToast } from '@/composables/useToast';
import axios from 'axios';

const { success, error: toastError } = useToast();

const props = defineProps({
    pembayaran: {
        type: Object,
        required: true,
    },
    vaExpiredAt: String,
    beasiswa: Object,
});

const isVA = computed(() => !!props.pembayaran.va_number);
const checking = ref(false);
const lastCheck = ref(null);
const bankStatus = ref(null);
const copiedVa = ref(false);
const copiedAmount = ref(false);
const activeGuideTab = ref('mbanking');

// VA dianggap expired jika masih pending dan batas waktunya sudah lewat
const isExpired = computed(() => {
    if (props.pembayaran.status !== 'pending') return false;
    const ts = props.pembayaran.va_expired_at ? new Date(props.pembayaran.va_expired_at).getTime() : NaN;
    return !isNaN(ts) && ts <= Date.now();
});

// Countdown
const countdown = ref({ days: 0, hours: 0, minutes: 0, seconds: 0 });
let timer = null;
let autoRefreshTimer = null;

const updateCountdown = () => {
    if (props.pembayaran.status !== 'pending') {
        countdown.value = { days: 0, hours: 0, minutes: 0, seconds: 0 };
        return;
    }

    const now = Date.now();
    const storedTs = props.pembayaran.va_expired_at ? new Date(props.pembayaran.va_expired_at).getTime() : NaN;
    const target = (!isNaN(storedTs) && storedTs > now) ? storedTs : NaN;

    if (isNaN(target)) {
        countdown.value = { days: 0, hours: 0, minutes: 0, seconds: 0 };
        return;
    }

    const diff = target - now;
    const totalSec = Math.floor(diff / 1000);
    countdown.value = {
        days: Math.floor(totalSec / 86400),
        hours: Math.floor((totalSec % 86400) / 3600),
        minutes: Math.floor((totalSec % 3600) / 60),
        seconds: totalSec % 60,
    };
};

const stopTimers = () => {
    if (timer) clearInterval(timer);
    timer = null;
    if (autoRefreshTimer) clearInterval(autoRefreshTimer);
    autoRefreshTimer = null;
    countdown.value = { days: 0, hours: 0, minutes: 0, seconds: 0 };
};

watch(() => props.pembayaran.status, (newStatus) => {
    if (newStatus !== 'pending') {
        stopTimers();
    }
});

onMounted(() => {
    updateCountdown();
    timer = setInterval(updateCountdown, 1000);

    // Auto-refresh status every 6 seconds (only if pending & not expired)
    autoRefreshTimer = setInterval(() => {
        if (props.pembayaran.status === 'pending' && !isExpired.value) {
            checkStatus(false);
        }
    }, 6000);
});

onUnmounted(() => {
    stopTimers();
});

const statusLabel = computed(() => {
    if (props.pembayaran.status === 'dikonfirmasi') return 'LUNAS TERKONFIRMASI';
    if (props.pembayaran.status === 'ditolak') return 'PEMBAYARAN DITOLAK';
    if (props.pembayaran.status === 'expired' || isExpired.value) return 'VA KEDALUWARSA';
    return 'MENUNGGU PEMBAYARAN';
});

const statusClass = computed(() => {
    if (props.pembayaran.status === 'dikonfirmasi') return 'badge-solid-success';
    if (props.pembayaran.status === 'ditolak') return 'badge-solid-danger';
    if (props.pembayaran.status === 'expired' || isExpired.value) return 'badge-solid-danger';
    return 'badge-solid-warning';
});

const displayNominal = computed(() => {
    if (props.beasiswa && Number(props.pembayaran.jumlah_bayar) === 0 && Number(props.beasiswa.diskon) > 0) {
        return Number(props.beasiswa.diskon);
    }
    if (props.beasiswa && props.beasiswa.tipe === 'full' && Number(props.pembayaran.jumlah_bayar) === 0) {
        return Number(props.beasiswa.diskon) || props.pembayaran.tagihan?.nominal || 0;
    }
    return Number(props.pembayaran.jumlah_bayar || 0);
});

const formattedVaNumber = computed(() => {
    const raw = String(props.pembayaran.va_number || '');
    if (!raw) return '-';
    // Format in groups of 4 digits for enhanced readability
    return raw.replace(/(\d{4})(?=\d)/g, '$1 ');
});

const copyVA = () => {
    if (!props.pembayaran.va_number) return;
    navigator.clipboard.writeText(props.pembayaran.va_number);
    copiedVa.value = true;
    success('Nomor Virtual Account berhasil disalin!');
    setTimeout(() => { copiedVa.value = false; }, 2000);
};

const copyAmount = () => {
    const amountStr = String(displayNominal.value);
    navigator.clipboard.writeText(amountStr);
    copiedAmount.value = true;
    success('Nominal pembayaran berhasil disalin!');
    setTimeout(() => { copiedAmount.value = false; }, 2000);
};

const checkStatus = (isManual = true) => {
    checking.value = true;
    axios.post(route('mahasiswa.pembayaran.check-status', props.pembayaran.id))
        .then((response) => {
            lastCheck.value = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            if (response.data?.success) {
                bankStatus.value = response.data;
                if (response.data.status === 'expired') {
                    props.pembayaran.status = 'expired';
                    if (isManual) toastError('VA sudah kedaluwarsa. Silakan buat pembayaran baru.');
                } else if (response.data.status === 'paid' || response.data.status === 'lunas') {
                    success('Pembayaran Anda sudah berhasil terkonfirmasi!');
                    props.pembayaran.status = 'dikonfirmasi';
                    props.pembayaran.verified_at = new Date().toISOString();
                } else {
                    const msg = response.data.message || 'Belum ada pembayaran terdeteksi';
                    if (isManual && !msg.includes('Mode Testing')) {
                        toastError(msg);
                    }
                }
            } else if (isManual) {
                toastError(response.data?.message || 'Gagal memeriksa status ke bank.');
            }
        })
        .catch(() => {
            if (isManual) toastError('Gagal menghubungkan ke server bank.');
        })
        .finally(() => {
            checking.value = false;
        });
};

const bankName = computed(() => {
    return props.pembayaran.metode_pembayaran?.nama_metode || 'Bank NTB Syariah';
});
</script>

<template>
    <Head :title="'Detail Pembayaran #' + pembayaran.id" />

    <AuthenticatedLayout>
        <template #header>
            <div class="pay-detail-header">
                <div class="header-nav-left">
                    <Link :href="route('mahasiswa.tagihan.index')" class="back-link-btn">
                        <i class="fas fa-arrow-left"></i>
                        <span>Tagihan</span>
                    </Link>
                    <span class="header-divider">/</span>
                    <Link :href="route('mahasiswa.riwayat.index')" class="breadcrumb-item">
                        Riwayat Transaksi
                    </Link>
                    <span class="header-divider">/</span>
                    <span class="breadcrumb-current">Transaksi #{{ pembayaran.id }}</span>
                </div>

                <div class="header-nav-right">
                    <span class="badge-solid" :class="statusClass">
                        <span class="pulse-dot" :class="{ 'pulse-active': pembayaran.status === 'pending' }"></span>
                        <i :class="pembayaran.status === 'dikonfirmasi' ? 'fas fa-check-circle' : (pembayaran.status === 'ditolak' || isExpired ? 'fas fa-times-circle' : 'fas fa-clock')" style="margin-right:0.3rem;"></i>
                        {{ statusLabel }}
                    </span>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">

                <!-- 1. PAYMENT LIFECYCLE STEPPER -->
                <div class="lifecycle-tracker-card">
                    <div class="stepper-track">
                        <!-- Step 1: Dibuat -->
                        <div class="stepper-step is-completed">
                            <div class="step-indicator">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="step-details">
                                <div class="step-title">1. VA Diterbitkan</div>
                                <div class="step-meta">{{ formatDateTime(pembayaran.created_at) }}</div>
                            </div>
                        </div>

                        <div class="step-line" :class="{ 'is-active': pembayaran.status === 'pending' || pembayaran.status === 'dikonfirmasi', 'is-completed': pembayaran.status === 'dikonfirmasi' }"></div>

                        <!-- Step 2: Menunggu Bayar -->
                        <div class="stepper-step" :class="{ 'is-completed': pembayaran.status === 'dikonfirmasi', 'is-active': pembayaran.status === 'pending' && !isExpired, 'is-danger': pembayaran.status === 'expired' || isExpired || pembayaran.status === 'ditolak' }">
                            <div class="step-indicator">
                                <i v-if="pembayaran.status === 'dikonfirmasi'" class="fas fa-check"></i>
                                <i v-else-if="pembayaran.status === 'expired' || isExpired" class="fas fa-hourglass-end"></i>
                                <i v-else-if="pembayaran.status === 'ditolak'" class="fas fa-times"></i>
                                <i v-else class="fas fa-clock"></i>
                            </div>
                            <div class="step-details">
                                <div class="step-title">2. Proses Pembayaran</div>
                                <div class="step-meta">
                                    <span v-if="pembayaran.status === 'dikonfirmasi'" class="text-emerald">Lunas Terbayar</span>
                                    <span v-else-if="isExpired || pembayaran.status === 'expired'" class="text-danger">Waktu Habis</span>
                                    <span v-else-if="pembayaran.status === 'ditolak'" class="text-danger">Ditolak</span>
                                    <span v-else class="text-amber">Menunggu Transfer</span>
                                </div>
                            </div>
                        </div>

                        <div class="step-line" :class="{ 'is-completed': pembayaran.status === 'dikonfirmasi' }"></div>

                        <!-- Step 3: Verifikasi Lunas -->
                        <div class="stepper-step" :class="{ 'is-completed': pembayaran.status === 'dikonfirmasi' }">
                            <div class="step-indicator">
                                <i :class="pembayaran.status === 'dikonfirmasi' ? 'fas fa-check-double' : 'fas fa-receipt'"></i>
                            </div>
                            <div class="step-details">
                                <div class="step-title">3. Pelunasan & Invoice</div>
                                <div class="step-meta">
                                    <span v-if="pembayaran.status === 'dikonfirmasi'" class="text-emerald">{{ formatDateTime(pembayaran.verified_at) }}</span>
                                    <span v-else>Invoice Resmi Terbit</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. BEASISWA BANNER (IF APPLICABLE) -->
                <div v-if="beasiswa" class="beasiswa-notification-bar">
                    <div class="beasiswa-icon-badge">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="beasiswa-info-text">
                        <div class="beasiswa-title">
                            Penerima Beasiswa: {{ beasiswa.nama }} ({{ beasiswa.kode }})
                        </div>
                        <div class="beasiswa-desc">
                            Tagihan mendapatkan potongan program beasiswa kategori <strong>{{ beasiswa.jenis }}</strong>.
                        </div>
                    </div>
                    <div class="beasiswa-badge-pill">
                        <i class="fas fa-check-circle"></i> {{ beasiswa.status }}
                    </div>
                </div>

                <!-- 3. STATUS HERO STATES -->

                <!-- STATE A: PEMBAYARAN SUKSES / LUNAS -->
                <div v-if="pembayaran.status === 'dikonfirmasi'" class="success-hero-card">
                    <div class="success-hero-body">
                        <div class="success-icon-badge">
                            <i class="fas fa-shield-check"></i>
                        </div>
                        <div class="success-content">
                            <div class="success-pretitle">TERVERIFIKASI RESMI</div>
                            <h2 class="success-title">Pembayaran UKT Berhasil Dilunasi</h2>
                            <p class="success-text">
                                Dana pembayaran telah berhasil diterima dan divalidasi ke dalam sistem perbankan universitas pada
                                <strong>{{ formatDateTime(pembayaran.verified_at) }}</strong>. Tagihan semester ini dinyatakan <strong>LUNAS</strong>.
                            </p>

                            <div class="success-actions-row">
                                <a
                                    v-if="pembayaran.tagihan_id"
                                    :href="route('mahasiswa.tagihan.invoice', pembayaran.tagihan_id)"
                                    target="_blank"
                                    class="solid-btn btn-white-emerald"
                                    title="Buka Faktur Pembayaran Resmi (PDF Stream)"
                                >
                                    <i class="fas fa-file-invoice"></i> Cetak / Unduh Invoice PDF Resmi
                                </a>

                                <Link
                                    :href="route('mahasiswa.riwayat.index')"
                                    class="solid-btn btn-ghost-white"
                                >
                                    <i class="fas fa-history"></i> Buka Riwayat Transaksi
                                </Link>
                            </div>
                        </div>

                        <div class="official-paid-stamp">
                            <div class="stamp-inner">
                                <div class="stamp-org">UNIVERSITAS</div>
                                <div class="stamp-main">LUNAS</div>
                                <div class="stamp-sub">VERIFIED</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STATE B: PEMBAYARAN KEDALUWARSA / DITOLAK -->
                <div v-else-if="isExpired || pembayaran.status === 'expired' || pembayaran.status === 'ditolak'" class="danger-hero-card">
                    <div class="danger-hero-body">
                        <div class="danger-icon-badge">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="danger-content">
                            <div class="danger-pretitle">TRANSAKSI TIDAK DAPAT DILANJUTKAN</div>
                            <h2 class="danger-title">
                                {{ isExpired || pembayaran.status === 'expired' ? 'Batas Waktu Pembayaran Telah Kedaluwarsa' : 'Pembayaran Tidak Dapat Diverifikasi' }}
                            </h2>
                            <p class="danger-text">
                                {{ isExpired || pembayaran.status === 'expired'
                                    ? 'Nomor Virtual Account ini sudah melewati batas toleransi pembayaran. Silakan buat transaksi baru untuk mendapatkan nomor VA aktif.'
                                    : (pembayaran.catatan_admin || 'Pembayaran ditolak oleh bagian administrasi keuangan. Silakan ajukan ulang atau hubungi bagian keuangan.')
                                }}
                            </p>

                            <div class="danger-actions-row">
                                <Link
                                    :href="route('mahasiswa.tagihan.index')"
                                    class="solid-btn btn-danger-fill"
                                >
                                    <i class="fas fa-redo"></i> Buat Pembayaran Baru
                                </Link>
                                <Link
                                    :href="route('mahasiswa.riwayat.index')"
                                    class="solid-btn btn-white-border"
                                >
                                    <i class="fas fa-history"></i> Lihat Riwayat
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. TWO-COLUMN MAIN CONTENT GRID -->
                <div class="pay-main-layout-grid">

                    <!-- LEFT COLUMN: PAYMENT CHANNEL & VA DETAILS & GUIDES -->
                    <div class="left-action-column">

                        <!-- VA Payment Box (Only when isVA) -->
                        <div v-if="isVA" class="panel-box va-box-container">
                            <div class="panel-box-header">
                                <div class="channel-info-brand">
                                    <div class="channel-logo-icon">
                                        <i class="fas fa-university"></i>
                                    </div>
                                    <div>
                                        <div class="channel-type-label">Kanal Pembayaran Resmi</div>
                                        <h3 class="channel-name-title">{{ bankName }} Virtual Account</h3>
                                    </div>
                                </div>

                                <div v-if="pembayaran.status === 'pending' && !isExpired" class="auto-refresh-pill">
                                    <span class="live-blink-dot"></span>
                                    <span>Auto-cek aktif</span>
                                </div>
                            </div>

                            <div class="panel-box-body">
                                <!-- Big Monospace VA Number Card -->
                                <div class="va-display-card">
                                    <div class="va-card-top-row">
                                        <span class="va-code-title">NOMOR VIRTUAL ACCOUNT (VA)</span>
                                        <span class="va-bank-badge">{{ bankName }}</span>
                                    </div>

                                    <div class="va-number-display-row">
                                        <div class="va-digits-formatted font-mono">{{ formattedVaNumber }}</div>
                                        <button
                                            type="button"
                                            class="va-copy-action-btn"
                                            :class="{ 'btn-copied': copiedVa }"
                                            @click="copyVA"
                                        >
                                            <i :class="copiedVa ? 'fas fa-check text-emerald' : 'far fa-copy'"></i>
                                            <span>{{ copiedVa ? 'Tersalin!' : 'Salin Nomor' }}</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Countdown Box (Only if Pending) -->
                                <div v-if="pembayaran.status === 'pending' && !isExpired" class="countdown-panel">
                                    <div class="countdown-panel-header">
                                        <div class="countdown-label-left">
                                            <i class="fas fa-hourglass-half text-amber"></i>
                                            <span>Sisa Waktu Pembayaran:</span>
                                        </div>
                                        <div class="countdown-expiry-date">
                                            Batas: {{ formatDateTime(pembayaran.va_expired_at) }}
                                        </div>
                                    </div>

                                    <!-- Digital Time Digit Cards -->
                                    <div class="countdown-cards-row">
                                        <div class="time-digit-card">
                                            <div class="digit-val">{{ countdown.days }}</div>
                                            <div class="digit-lbl">Hari</div>
                                        </div>
                                        <span class="digit-sep">:</span>
                                        <div class="time-digit-card">
                                            <div class="digit-val">{{ String(countdown.hours).padStart(2, '0') }}</div>
                                            <div class="digit-lbl">Jam</div>
                                        </div>
                                        <span class="digit-sep">:</span>
                                        <div class="time-digit-card">
                                            <div class="digit-val">{{ String(countdown.minutes).padStart(2, '0') }}</div>
                                            <div class="digit-lbl">Menit</div>
                                        </div>
                                        <span class="digit-sep">:</span>
                                        <div class="time-digit-card highlight-card">
                                            <div class="digit-val">{{ String(countdown.seconds).padStart(2, '0') }}</div>
                                            <div class="digit-lbl">Detik</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status Checking Box -->
                                <div v-if="pembayaran.status === 'pending' && !isExpired" class="check-status-strip">
                                    <div class="check-status-info">
                                        <i class="fas fa-info-circle text-indigo"></i>
                                        <div>
                                            <div class="check-status-title">Status Transaksi Real-time</div>
                                            <div class="check-status-desc">
                                                Jika sudah melakukan transfer, klik tombol refresh untuk sinkronisasi seketika.
                                                <span v-if="lastCheck" class="last-check-text">(Terakhir dicek: {{ lastCheck }})</span>
                                            </div>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="solid-btn btn-indigo refresh-btn"
                                        :disabled="checking"
                                        @click="checkStatus(true)"
                                    >
                                        <i class="fas fa-sync-alt" :class="{ 'fa-spin': checking }"></i>
                                        <span>{{ checking ? 'Memeriksa...' : 'Cek Status Pembayaran' }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Manual Transfer / Bukti Box (If not VA) -->
                        <div v-else class="panel-box">
                            <div class="panel-box-header">
                                <div class="channel-info-brand">
                                    <div class="channel-logo-icon">
                                        <i class="fas fa-money-check-alt"></i>
                                    </div>
                                    <div>
                                        <div class="channel-type-label">Metode Pembayaran</div>
                                        <h3 class="channel-name-title">{{ bankName }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="panel-box-body">
                                <div v-if="pembayaran.bukti_pembayaran" class="bukti-preview-wrapper">
                                    <div class="bukti-label">Berkas Bukti Transfer Terlampir:</div>
                                    <img :src="'/storage/' + pembayaran.bukti_pembayaran" class="bukti-image" alt="Bukti Transfer" />
                                </div>
                            </div>
                        </div>

                        <!-- INTERACTIVE PAYMENT GUIDES (ACCORDIONS / TABS) -->
                        <div class="panel-box guides-container">
                            <div class="panel-box-header">
                                <div class="guide-header-title">
                                    <i class="fas fa-book-open text-indigo"></i>
                                    <span>Panduan Tata Cara Pembayaran</span>
                                </div>
                                <span class="guide-header-badge">{{ bankName }}</span>
                            </div>

                            <!-- Tabs Selector -->
                            <div class="guide-tabs-bar">
                                <button
                                    type="button"
                                    class="guide-tab-btn"
                                    :class="{ active: activeGuideTab === 'mbanking' }"
                                    @click="activeGuideTab = 'mbanking'"
                                >
                                    <i class="fas fa-mobile-alt"></i> Mobile Banking
                                </button>
                                <button
                                    type="button"
                                    class="guide-tab-btn"
                                    :class="{ active: activeGuideTab === 'atm' }"
                                    @click="activeGuideTab = 'atm'"
                                >
                                    <i class="fas fa-credit-card"></i> Mesin ATM
                                </button>
                                <button
                                    type="button"
                                    class="guide-tab-btn"
                                    :class="{ active: activeGuideTab === 'ibanking' }"
                                    @click="activeGuideTab = 'ibanking'"
                                >
                                    <i class="fas fa-laptop"></i> Internet Banking
                                </button>
                                <button
                                    type="button"
                                    class="guide-tab-btn"
                                    :class="{ active: activeGuideTab === 'teller' }"
                                    @click="activeGuideTab = 'teller'"
                                >
                                    <i class="fas fa-user-tie"></i> Teller Bank
                                </button>
                                <button
                                    type="button"
                                    class="guide-tab-btn"
                                    :class="{ active: activeGuideTab === 'interbank' }"
                                    @click="activeGuideTab = 'interbank'"
                                >
                                    <i class="fas fa-exchange-alt"></i> Bank Lain (BI-FAST)
                                </button>
                            </div>

                            <!-- Tab Content -->
                            <div class="guide-tab-content">
                                <!-- 1. M-Banking -->
                                <div v-if="activeGuideTab === 'mbanking'" class="guide-steps-list">
                                    <div class="guide-step-item">
                                        <div class="step-num">1</div>
                                        <div class="step-text">Buka dan masuk ke aplikasi Mobile Banking ({{ bankName }} Mobile / Bank Anda).</div>
                                    </div>
                                    <div class="guide-step-item">
                                        <div class="step-num">2</div>
                                        <div class="step-text">Pilih menu <strong>Pembayaran / Bayar</strong> &rarr; lalu pilih <strong>Virtual Account / Pendidikan</strong>.</div>
                                    </div>
                                    <div class="guide-step-item">
                                        <div class="step-num">3</div>
                                        <div class="step-text">Masukkan Nomor Virtual Account: <strong class="font-mono text-indigo">{{ pembayaran.va_number || 'Nomor VA Anda' }}</strong>.</div>
                                    </div>
                                    <div class="guide-step-item">
                                        <div class="step-num">4</div>
                                        <div class="step-text">Pastikan rincian tagihan, <strong>Nama Mahasiswa</strong>, dan <strong>Total Nominal ({{ formatRupiah(displayNominal) }})</strong> telah sesuai di layar konfirmasi.</div>
                                    </div>
                                    <div class="guide-step-item">
                                        <div class="step-num">5</div>
                                        <div class="step-text">Masukkan PIN Transaksi Anda dan selesaikan pembayaran. Simpan bukti resi transfer.</div>
                                    </div>
                                </div>

                                <!-- 2. ATM -->
                                <div v-else-if="activeGuideTab === 'atm'" class="guide-steps-list">
                                    <div class="guide-step-item">
                                        <div class="step-num">1</div>
                                        <div class="step-text">Masukkan Kartu ATM dan 6 digit PIN Anda di mesin ATM terdekat.</div>
                                    </div>
                                    <div class="guide-step-item">
                                        <div class="step-num">2</div>
                                        <div class="step-text">Pilih menu <strong>Transaksi Lainnya</strong> &rarr; pilih <strong>Pembayaran / Pembelian</strong> &rarr; pilih <strong>Virtual Account</strong>.</div>
                                    </div>
                                    <div class="guide-step-item">
                                        <div class="step-num">3</div>
                                        <div class="step-text">Ketikkan Nomor Virtual Account: <strong class="font-mono text-indigo">{{ pembayaran.va_number }}</strong> lalu tekan <strong>Benar</strong>.</div>
                                    </div>
                                    <div class="guide-step-item">
                                        <div class="step-num">4</div>
                                        <div class="step-text">Periksa layar konfirmasi apakah nama dan nominal tagihan UKT sudah cocok.</div>
                                    </div>
                                    <div class="guide-step-item">
                                        <div class="step-num">5</div>
                                        <div class="step-text">Tekan <strong>Ya / Bayar</strong> untuk memproses dan ambil struk bukti pembayaran fisik.</div>
                                    </div>
                                </div>

                                <!-- 3. Internet Banking -->
                                <div v-else-if="activeGuideTab === 'ibanking'" class="guide-steps-list">
                                    <div class="guide-step-item">
                                        <div class="step-num">1</div>
                                        <div class="step-text">Login ke portal Internet Banking bank Anda melalui browser.</div>
                                    </div>
                                    <div class="guide-step-item">
                                        <div class="step-num">2</div>
                                        <div class="step-text">Buka menu <strong>Bayar Tagihan</strong> &rarr; pilih sub-menu <strong>Virtual Account</strong>.</div>
                                    </div>
                                    <div class="guide-step-item">
                                        <div class="step-num">3</div>
                                        <div class="step-text">Input Nomor Virtual Account: <strong class="font-mono text-indigo">{{ pembayaran.va_number }}</strong>.</div>
                                    </div>
                                    <div class="guide-step-item">
                                        <div class="step-num">4</div>
                                        <div class="step-text">Cek informasi tagihan pada layar ringkasan dan otentikasi menggunakan Token / OTP.</div>
                                    </div>
                                </div>

                                <!-- 4. Teller Bank -->
                                <div v-else-if="activeGuideTab === 'teller'" class="guide-steps-list">
                                    <div class="guide-step-item">
                                        <div class="step-num">1</div>
                                        <div class="step-text">Kunjungi kantor cabang {{ bankName }} terdekat dan ambil antrean layanan Teller.</div>
                                    </div>
                                    <div class="guide-step-item">
                                        <div class="step-num">2</div>
                                        <div class="step-text">Sampaikan kepada petugas Teller bahwa Anda ingin melakukan <strong>Pembayaran UKT Mahasiswa via Virtual Account</strong>.</div>
                                    </div>
                                    <div class="guide-step-item">
                                        <div class="step-num">3</div>
                                        <div class="step-text">Tunjukkan Nomor VA: <strong class="font-mono text-indigo">{{ pembayaran.va_number }}</strong> dan sebutkan NIM / Nama Mahasiswa.</div>
                                    </div>
                                    <div class="guide-step-item">
                                        <div class="step-num">4</div>
                                        <div class="step-text">Serahkan uang tunai atau debet rekening dan terima slip kuitansi validasi cetak dari Teller.</div>
                                    </div>
                                </div>

                                <!-- 5. Bank Lain (Interbank / BI-FAST) -->
                                <div v-else class="guide-steps-list">
                                    <div class="guide-step-item">
                                        <div class="step-num">1</div>
                                        <div class="step-text">Buka aplikasi mobile banking dari bank mana pun (BCA, Mandiri, BRI, BNI, BSI, dll).</div>
                                    </div>
                                    <div class="guide-step-item">
                                        <div class="step-num">2</div>
                                        <div class="step-text">Pilih menu <strong>Transfer Antar Bank</strong> (Gunakan jalur <strong>BI-FAST / Online Realtime</strong>).</div>
                                    </div>
                                    <div class="guide-step-item">
                                        <div class="step-num">3</div>
                                        <div class="step-text">Pilih Bank Tujuan: <strong>{{ bankName }}</strong> (atau Bank NTB Syariah).</div>
                                    </div>
                                    <div class="guide-step-item">
                                        <div class="step-num">4</div>
                                        <div class="step-text">Masukkan Nomor Rekening Tujuan: <strong class="font-mono text-indigo">{{ pembayaran.va_number }}</strong>.</div>
                                    </div>
                                    <div class="guide-step-item">
                                        <div class="step-num">5</div>
                                        <div class="step-text">Masukkan Nominal Transfer tepat sebesar <strong>{{ formatRupiah(displayNominal) }}</strong> dan konfirmasi PIN.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: DIGITAL RECEIPT SLIP & INVOICE DETAILS -->
                    <div class="right-receipt-column">
                        <div class="digital-receipt-card">
                            <!-- Receipt Perforated Top -->
                            <div class="receipt-header">
                                <div class="receipt-logo-wrap">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                </div>
                                <h4 class="receipt-title">Rincian Slip Tagihan UKT</h4>
                                <div class="receipt-id-tag">ID Transaksi: #{{ pembayaran.id }}</div>
                            </div>

                            <!-- Student Info Section -->
                            <div class="receipt-section">
                                <div class="receipt-row">
                                    <span class="r-label">Nama Mahasiswa</span>
                                    <span class="r-val fw-bold">{{ pembayaran.tagihan?.mahasiswa?.nama_lengkap || pembayaran.nama_pengirim || '-' }}</span>
                                </div>
                                <div class="receipt-row">
                                    <span class="r-label">Nomor Induk (NIM)</span>
                                    <span class="r-val font-mono">{{ pembayaran.tagihan?.mahasiswa?.nim || '-' }}</span>
                                </div>
                                <div class="receipt-row">
                                    <span class="r-label">Program Studi</span>
                                    <span class="r-val">{{ pembayaran.tagihan?.mahasiswa?.jurusan || '-' }}</span>
                                </div>
                                <div class="receipt-row">
                                    <span class="r-label">Tahun Angkatan</span>
                                    <span class="r-val">{{ pembayaran.tagihan?.mahasiswa?.angkatan || '-' }}</span>
                                </div>
                            </div>

                            <div class="receipt-divider"></div>

                            <!-- Academic Semester Section -->
                            <div class="receipt-section">
                                <div class="receipt-row">
                                    <span class="r-label">Semester Akademik</span>
                                    <span class="r-val fw-bold text-indigo">
                                        Semester {{ pembayaran.tagihan?.semester || '-' }}
                                    </span>
                                </div>
                                <div class="receipt-row">
                                    <span class="r-label">Tahun Akademik</span>
                                    <span class="r-val">{{ pembayaran.tagihan?.tahun_akademik || '-' }}</span>
                                </div>
                                <div class="receipt-row">
                                    <span class="r-label">Waktu Dibuat</span>
                                    <span class="r-val">{{ formatDateTime(pembayaran.created_at) }}</span>
                                </div>
                                <div v-if="pembayaran.verified_at" class="receipt-row">
                                    <span class="r-label">Waktu Pelunasan</span>
                                    <span class="r-val text-emerald fw-bold">{{ formatDateTime(pembayaran.verified_at) }}</span>
                                </div>
                            </div>

                            <div class="receipt-divider"></div>

                            <!-- Amount Breakdown Section -->
                            <div class="receipt-section">
                                <div class="receipt-row">
                                    <span class="r-label">Biaya Pokok UKT</span>
                                    <span class="r-val">
                                        {{ formatRupiah(pembayaran.tagihan?.nominal || displayNominal) }}
                                    </span>
                                </div>

                                <div v-if="beasiswa && Number(beasiswa.diskon) > 0" class="receipt-row discount-row">
                                    <span class="r-label text-emerald">
                                        <i class="fas fa-gift"></i> Beasiswa ({{ beasiswa.kode }})
                                    </span>
                                    <span class="r-val text-emerald fw-bold">
                                        - {{ formatRupiah(beasiswa.diskon) }}
                                    </span>
                                </div>

                                <div class="receipt-row">
                                    <span class="r-label">Biaya Administrasi Bank</span>
                                    <span class="r-val text-emerald fw-bold">Rp 0 (Gratis)</span>
                                </div>
                            </div>

                            <!-- Perforated Tear Line -->
                            <div class="receipt-tear-line">
                                <span class="tear-notch notch-left"></span>
                                <span class="tear-dashed"></span>
                                <span class="tear-notch notch-right"></span>
                            </div>

                            <!-- Total Summary Box -->
                            <div class="receipt-total-box">
                                <div class="total-label-row">
                                    <span class="total-heading">TOTAL PEMBAYARAN</span>
                                    <button
                                        type="button"
                                        class="copy-amount-pill"
                                        :title="copiedAmount ? 'Tersalin!' : 'Salin Nominal'"
                                        @click="copyAmount"
                                    >
                                        <i :class="copiedAmount ? 'fas fa-check text-emerald' : 'far fa-copy'"></i>
                                        <span>{{ copiedAmount ? 'Tersalin' : 'Salin' }}</span>
                                    </button>
                                </div>
                                <div class="total-amount-display font-mono">
                                    {{ formatRupiah(displayNominal) }}
                                </div>
                                <div v-if="beasiswa && beasiswa.tipe === 'full'" class="full-covered-tag">
                                    <i class="fas fa-check-double"></i> Ditanggung Penuh oleh Program Beasiswa
                                </div>
                            </div>

                            <!-- Admin Notes if any -->
                            <div v-if="pembayaran.catatan_admin && !pembayaran.catatan_admin.toLowerCase().includes('beasiswa')" class="receipt-admin-note">
                                <div class="admin-note-label">
                                    <i class="fas fa-sticky-note text-amber"></i>
                                    <span>Catatan Administrasi:</span>
                                </div>
                                <div class="admin-note-content">{{ pembayaran.catatan_admin }}</div>
                            </div>

                            <!-- Bottom Action Buttons in Receipt -->
                            <div class="receipt-footer-actions">
                                <a
                                    v-if="pembayaran.status === 'dikonfirmasi' && pembayaran.tagihan_id"
                                    :href="route('mahasiswa.tagihan.invoice', pembayaran.tagihan_id)"
                                    target="_blank"
                                    class="solid-btn btn-indigo"
                                    style="width:100%; justify-content:center;"
                                >
                                    <i class="fas fa-file-invoice"></i> Buka Invoice Resmi (PDF Stream)
                                </a>

                                <Link
                                    :href="route('mahasiswa.tagihan.index')"
                                    class="solid-btn btn-white-border"
                                    style="width:100%; justify-content:center;"
                                >
                                    <i class="fas fa-arrow-left"></i> Kembali ke Tagihan
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* =======================================================
   MAHASISWA PEMBAYARAN SHOW - EXECUTIVE REDESIGN
   ======================================================= */

/* --- Page Header Navigation --- */
.pay-detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.header-nav-left {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.84rem;
    color: #64748b;
}

.back-link-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    padding: 0.35rem 0.75rem;
    border-radius: 0.45rem;
    color: #334155;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.15s ease;
}
.back-link-btn:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
}

.header-divider { color: #cbd5e1; }

.breadcrumb-item {
    color: #64748b;
    text-decoration: none;
    transition: color 0.15s;
}
.breadcrumb-item:hover { color: #4f46e5; }

.breadcrumb-current {
    color: #0f172a;
    font-weight: 700;
}

/* --- Common Solid Buttons --- */
.solid-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.55rem 1.15rem;
    border-radius: 0.55rem;
    font-size: 0.8125rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    border: none;
    line-height: 1.4;
}

.btn-white-border {
    background: #ffffff;
    color: #334155;
    border: 1.5px solid #e2e8f0;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}
.btn-white-border:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
    transform: translateY(-1px);
}

.btn-indigo {
    background: #4f46e5;
    color: #ffffff;
}
.btn-indigo:hover:not(:disabled) {
    background: #4338ca;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
    transform: translateY(-1px);
}

.btn-danger-fill {
    background: #dc2626;
    color: #ffffff;
}
.btn-danger-fill:hover {
    background: #b91c1c;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25);
}

.btn-white-emerald {
    background: #ffffff;
    color: #065f46;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}
.btn-white-emerald:hover {
    background: #f0fdf4;
    transform: translateY(-1px);
}

.btn-ghost-white {
    background: rgba(255, 255, 255, 0.18);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.35);
}
.btn-ghost-white:hover {
    background: rgba(255, 255, 255, 0.28);
}

/* --- Badges & Colors --- */
.badge-solid {
    display: inline-flex;
    align-items: center;
    padding: 0.4rem 0.85rem;
    border-radius: 2rem;
    font-size: 0.75rem;
    font-weight: 700;
    white-space: nowrap;
}
.badge-solid-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.badge-solid-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.badge-solid-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

.pulse-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    margin-right: 0.4rem;
    background-color: currentColor;
}
.pulse-active {
    animation: pulseActive 1.8s infinite ease-in-out;
}
@keyframes pulseActive {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.3; transform: scale(0.85); }
}

.text-indigo { color: #4f46e5 !important; }
.text-emerald { color: #059669 !important; }
.text-amber { color: #d97706 !important; }
.text-danger { color: #dc2626 !important; }
.fw-bold { font-weight: 700; }
.font-mono { font-family: 'SF Mono', 'Cascadia Code', 'Fira Code', 'Consolas', monospace; }

/* --- 1. Stepper / Progress Timeline --- */
.lifecycle-tracker-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.95rem;
    padding: 1.25rem 1.75rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.stepper-track {
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
}

.stepper-step {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    position: relative;
    z-index: 2;
}

.step-indicator {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 50%;
    background: #f1f5f9;
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    font-weight: 700;
    border: 2px solid #e2e8f0;
    transition: all 0.25s ease;
}

.stepper-step.is-completed .step-indicator {
    background: #059669;
    color: #ffffff;
    border-color: #059669;
}

.stepper-step.is-active .step-indicator {
    background: #f59e0b;
    color: #ffffff;
    border-color: #f59e0b;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.18);
}

.stepper-step.is-danger .step-indicator {
    background: #dc2626;
    color: #ffffff;
    border-color: #dc2626;
}

.step-details {
    display: flex;
    flex-direction: column;
}

.step-title {
    font-size: 0.8125rem;
    font-weight: 700;
    color: #0f172a;
}

.step-meta {
    font-size: 0.72rem;
    color: #64748b;
    margin-top: 0.1rem;
}

.step-line {
    flex: 1;
    height: 3px;
    background: #e2e8f0;
    margin: 0 1rem;
    transition: background 0.3s ease;
}
.step-line.is-active { background: #fde68a; }
.step-line.is-completed { background: #059669; }

/* --- 2. Beasiswa Bar --- */
.beasiswa-notification-bar {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    background: #ecfdf5;
    border: 1.5px solid #a7f3d0;
    border-radius: 0.85rem;
    padding: 0.85rem 1.25rem;
    margin-bottom: 1.5rem;
}

.beasiswa-icon-badge {
    width: 2.35rem;
    height: 2.35rem;
    border-radius: 0.6rem;
    background: #059669;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.beasiswa-info-text { flex: 1; }
.beasiswa-title { font-size: 0.875rem; font-weight: 700; color: #065f46; }
.beasiswa-desc { font-size: 0.75rem; color: #047857; margin-top: 0.1rem; }

.beasiswa-badge-pill {
    font-size: 0.72rem;
    font-weight: 700;
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
    padding: 0.25rem 0.65rem;
    border-radius: 1rem;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

/* --- 3. Hero Status States --- */
.success-hero-card {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    color: #ffffff;
    border-radius: 1rem;
    padding: 1.75rem 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 10px 25px rgba(5, 150, 105, 0.25);
    position: relative;
    overflow: hidden;
}

.success-hero-body {
    display: flex;
    align-items: flex-start;
    gap: 1.25rem;
    position: relative;
    z-index: 2;
}

.success-icon-badge {
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 0.85rem;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.65rem;
    flex-shrink: 0;
}

.success-content { flex: 1; }

.success-pretitle {
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    opacity: 0.85;
}

.success-title {
    font-size: 1.45rem;
    font-weight: 800;
    margin: 0.2rem 0 0.5rem;
    letter-spacing: -0.01em;
}

.success-text {
    font-size: 0.84rem;
    line-height: 1.5;
    opacity: 0.92;
    max-width: 600px;
    margin: 0 0 1.25rem;
}

.success-actions-row {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.official-paid-stamp {
    position: absolute;
    right: 2rem;
    top: 50%;
    transform: translateY(-50%) rotate(-12deg);
    border: 3px double rgba(255, 255, 255, 0.4);
    border-radius: 0.5rem;
    padding: 0.4rem 0.85rem;
    text-align: center;
    pointer-events: none;
    opacity: 0.85;
}

.stamp-inner { text-align: center; }
.stamp-org { font-size: 0.6rem; letter-spacing: 0.15em; font-weight: 700; opacity: 0.8; }
.stamp-main { font-size: 1.5rem; font-weight: 900; letter-spacing: 0.12em; line-height: 1; margin: 0.15rem 0; }
.stamp-sub { font-size: 0.6rem; letter-spacing: 0.2em; font-weight: 700; opacity: 0.8; }

/* Danger Hero Card */
.danger-hero-card {
    background: #fff1f2;
    border: 1.5px solid #fecdd3;
    border-radius: 1rem;
    padding: 1.5rem 1.75rem;
    margin-bottom: 1.5rem;
}

.danger-hero-body {
    display: flex;
    align-items: flex-start;
    gap: 1.15rem;
}

.danger-icon-badge {
    width: 3.25rem;
    height: 3.25rem;
    border-radius: 0.75rem;
    background: #ffe4e6;
    color: #e11d48;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.danger-content { flex: 1; }
.danger-pretitle { font-size: 0.7rem; font-weight: 800; color: #be123c; letter-spacing: 0.06em; }
.danger-title { font-size: 1.25rem; font-weight: 800; color: #9f1239; margin: 0.2rem 0 0.4rem; }
.danger-text { font-size: 0.84rem; color: #881337; line-height: 1.5; margin: 0 0 1rem; }

.danger-actions-row {
    display: flex;
    gap: 0.65rem;
    flex-wrap: wrap;
}

/* --- 4. Main 2-Column Grid --- */
.pay-main-layout-grid {
    display: grid;
    grid-template-columns: 1.45fr 1fr;
    gap: 1.5rem;
    align-items: start;
}

.pay-main-layout-grid > * {
    min-width: 0;
}

.left-action-column {
    min-width: 0;
}

/* Panel Box Container */
.panel-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.95rem;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.panel-box-header {
    padding: 1.15rem 1.35rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.channel-info-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.channel-logo-icon {
    width: 2.35rem;
    height: 2.35rem;
    border-radius: 0.6rem;
    background: #eef2ff;
    color: #4f46e5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.channel-type-label {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    letter-spacing: 0.04em;
}

.channel-name-title {
    font-size: 0.95rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0.1rem 0 0;
}

.auto-refresh-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.25rem 0.55rem;
    border-radius: 1rem;
}

.live-blink-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background-color: #16a34a;
    animation: blinkLive 1.5s infinite ease-in-out;
}
@keyframes blinkLive {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.2; transform: scale(0.8); }
}

.panel-box-body {
    padding: 1.35rem;
}

/* Big Monospace VA Card */
.va-display-card {
    background: #0f172a;
    color: #ffffff;
    border-radius: 0.85rem;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.12);
    margin-bottom: 1.25rem;
}

.va-card-top-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.va-code-title {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    color: #94a3b8;
}

.va-bank-badge {
    font-size: 0.7rem;
    font-weight: 700;
    background: rgba(255, 255, 255, 0.15);
    padding: 0.15rem 0.5rem;
    border-radius: 0.35rem;
}

.va-number-display-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.va-digits-formatted {
    font-size: 1.65rem;
    font-weight: 800;
    letter-spacing: 2.5px;
    color: #38bdf8;
    word-break: break-all;
}

.va-copy-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.5rem 0.95rem;
    border-radius: 0.5rem;
    font-size: 0.78rem;
    font-weight: 700;
    background: #ffffff;
    color: #0f172a;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}
.va-copy-action-btn:hover {
    background: #e2e8f0;
    transform: translateY(-1px);
}
.va-copy-action-btn.btn-copied {
    background: #dcfce7;
    color: #166534;
}

/* Countdown Panel */
.countdown-panel {
    background: #fffbeb;
    border: 1.5px solid #fde68a;
    border-radius: 0.85rem;
    padding: 1.15rem 1.25rem;
    margin-bottom: 1.25rem;
}

.countdown-panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.85rem;
    flex-wrap: wrap;
    gap: 0.4rem;
}

.countdown-label-left {
    font-size: 0.78rem;
    font-weight: 700;
    color: #92400e;
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.countdown-expiry-date {
    font-size: 0.72rem;
    color: #b45309;
}

.countdown-cards-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.time-digit-card {
    background: #ffffff;
    border: 1.5px solid #fcd34d;
    border-radius: 0.65rem;
    padding: 0.5rem 0.85rem;
    text-align: center;
    min-width: 3.5rem;
    box-shadow: 0 2px 4px rgba(245, 158, 11, 0.08);
}

.time-digit-card.highlight-card {
    background: #fef3c7;
    border-color: #f59e0b;
}

.digit-val {
    font-size: 1.35rem;
    font-weight: 900;
    font-family: 'SF Mono', monospace;
    color: #b45309;
    line-height: 1.1;
}

.digit-lbl {
    font-size: 0.65rem;
    font-weight: 700;
    color: #92400e;
    text-transform: uppercase;
    margin-top: 0.2rem;
}

.digit-sep {
    font-size: 1.35rem;
    font-weight: 900;
    color: #d97706;
}

/* Status Checking Strip */
.check-status-strip {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 0.85rem 1.15rem;
    gap: 1rem;
    flex-wrap: wrap;
}

.check-status-info {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    flex: 1;
}

.check-status-title {
    font-size: 0.8125rem;
    font-weight: 700;
    color: #0f172a;
}

.check-status-desc {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 0.15rem;
    line-height: 1.4;
}

.last-check-text {
    display: block;
    color: #94a3b8;
    font-size: 0.7rem;
    margin-top: 0.1rem;
}

.refresh-btn {
    white-space: nowrap;
    flex-shrink: 0;
}

/* --- Payment Guides --- */
.guide-header-title {
    font-size: 0.875rem;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.guide-header-badge {
    font-size: 0.7rem;
    font-weight: 700;
    background: #eef2ff;
    color: #4f46e5;
    padding: 0.2rem 0.55rem;
    border-radius: 0.35rem;
}

.guide-tabs-bar {
    display: flex;
    background: #f1f5f9;
    padding: 0.35rem;
    gap: 0.25rem;
    overflow-x: auto;
    border-bottom: 1px solid #e2e8f0;
}

.guide-tab-btn {
    border: none;
    background: transparent;
    padding: 0.45rem 0.85rem;
    border-radius: 0.45rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    transition: all 0.15s ease;
}

.guide-tab-btn:hover:not(.active) {
    color: #0f172a;
    background: rgba(255, 255, 255, 0.6);
}

.guide-tab-btn.active {
    background: #ffffff;
    color: #4f46e5;
    font-weight: 700;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.guide-tab-content {
    padding: 1.25rem 1.35rem;
}

.guide-steps-list {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.guide-step-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.guide-step-item .step-text {
    min-width: 0;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.step-num {
    width: 1.65rem;
    height: 1.65rem;
    border-radius: 50%;
    background: #eef2ff;
    color: #4f46e5;
    font-size: 0.75rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 0.1rem;
}

.step-text {
    font-size: 0.8125rem;
    color: #334155;
    line-height: 1.5;
}

/* --- RIGHT COLUMN: DIGITAL RECEIPT SLIP --- */
.digital-receipt-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 1rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.receipt-header {
    text-align: center;
    padding: 1.5rem 1.25rem 1.25rem;
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
}

.receipt-logo-wrap {
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    background: #eef2ff;
    color: #4f46e5;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    margin-bottom: 0.5rem;
}

.receipt-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
}

.receipt-id-tag {
    font-family: 'SF Mono', monospace;
    font-size: 0.72rem;
    color: #64748b;
    margin-top: 0.25rem;
}

.receipt-section {
    padding: 1rem 1.35rem;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

.receipt-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8125rem;
    gap: 0.75rem;
}

.r-label {
    color: #64748b;
}

.r-val {
    color: #1e293b;
    text-align: right;
}

.receipt-divider {
    height: 1px;
    background: #f1f5f9;
    margin: 0 1.35rem;
}

/* Perforated Tear Line */
.receipt-tear-line {
    position: relative;
    height: 1.5rem;
    display: flex;
    align-items: center;
    background: #ffffff;
    margin: 0.25rem 0;
}

.tear-dashed {
    flex: 1;
    border-top: 2px dashed #cbd5e1;
    margin: 0 0.5rem;
}

.tear-notch {
    width: 1rem;
    height: 1rem;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 50%;
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
}

.notch-left { left: -0.55rem; }
.notch-right { right: -0.55rem; }

/* Total Summary Box */
.receipt-total-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.15rem;
    margin: 0 1.35rem 1rem;
}

.total-label-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.35rem;
}

.total-heading {
    font-size: 0.72rem;
    font-weight: 800;
    color: #64748b;
    letter-spacing: 0.05em;
}

.copy-amount-pill {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 0.35rem;
    padding: 0.15rem 0.45rem;
    font-size: 0.7rem;
    font-weight: 700;
    color: #334155;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.total-amount-display {
    font-size: 1.55rem;
    font-weight: 900;
    color: #4f46e5;
    letter-spacing: -0.02em;
}

.full-covered-tag {
    font-size: 0.72rem;
    font-weight: 700;
    color: #059669;
    margin-top: 0.35rem;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

/* Admin Note */
.receipt-admin-note {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 0.65rem;
    padding: 0.75rem 0.95rem;
    margin: 0 1.35rem 1rem;
}

.admin-note-label {
    font-size: 0.72rem;
    font-weight: 700;
    color: #92400e;
    display: flex;
    align-items: center;
    gap: 0.3rem;
    margin-bottom: 0.2rem;
}

.admin-note-content {
    font-size: 0.78rem;
    color: #78350f;
    line-height: 1.4;
}

/* Footer Actions */
.receipt-footer-actions {
    padding: 0 1.35rem 1.35rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

/* Bukti Transfer Image */
.bukti-preview-wrapper {
    margin-top: 0.5rem;
}

.bukti-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #475569;
    margin-bottom: 0.5rem;
}

.bukti-image {
    max-width: 100%;
    border-radius: 0.65rem;
    border: 1px solid #e2e8f0;
}

/* --- Responsive Media Queries --- */
@media (max-width: 992px) {
    .pay-main-layout-grid {
        grid-template-columns: 1fr;
    }

    .official-paid-stamp {
        display: none;
    }
}

@media (max-width: 768px) {
    /* Header / Breadcrumb */
    .pay-detail-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.65rem;
    }

    .header-nav-left {
        flex-wrap: wrap;
        gap: 0.3rem;
        font-size: 0.75rem;
    }

    .back-link-btn {
        padding: 0.35rem 0.6rem;
        font-size: 0.75rem;
    }

    .breadcrumb-current {
        font-size: 0.75rem;
    }

    .header-nav-right {
        width: 100%;
    }

    .header-nav-right .badge-solid {
        width: 100%;
        justify-content: center;
        padding: 0.4rem 0.75rem;
        font-size: 0.72rem;
    }

    /* Lifecycle Stepper */
    .lifecycle-tracker-card {
        padding: 0.85rem;
        border-radius: 0.75rem;
    }

    .stepper-track {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.85rem;
    }

    .step-line {
        display: none;
    }

    .stepper-step {
        flex-direction: row;
        align-items: center;
        gap: 0.65rem;
    }

    .step-indicator {
        width: 2rem;
        height: 2rem;
        font-size: 0.75rem;
        flex-shrink: 0;
    }

    .step-title {
        font-size: 0.78rem;
    }

    .step-meta {
        font-size: 0.68rem;
    }

    /* Beasiswa Banner */
    .beasiswa-notification-bar {
        flex-wrap: wrap;
        gap: 0.65rem;
        padding: 0.85rem;
    }

    .beasiswa-title {
        font-size: 0.82rem;
    }

    .beasiswa-desc {
        font-size: 0.72rem;
    }

    /* Success / Danger Hero */
    .success-hero-card,
    .danger-hero-card {
        padding: 1rem;
        border-radius: 0.75rem;
    }

    .success-hero-body,
    .danger-hero-body {
        flex-direction: column;
        gap: 0.75rem;
    }

    .success-icon-badge,
    .danger-icon-badge {
        width: 3rem;
        height: 3rem;
        font-size: 1.25rem;
    }

    .success-pretitle,
    .danger-pretitle {
        font-size: 0.65rem;
    }

    .success-title,
    .danger-title {
        font-size: 1.05rem;
    }

    .success-text,
    .danger-text {
        font-size: 0.78rem;
    }

    .success-actions-row,
    .danger-actions-row {
        flex-direction: column;
        gap: 0.45rem;
    }

    .success-actions-row .solid-btn,
    .danger-actions-row .solid-btn {
        width: 100%;
        justify-content: center;
        min-height: 2.75rem;
    }

    /* VA Panel Box */
    .pay-main-layout-grid {
        gap: 1rem;
    }

    .left-action-column {
        width: 100%;
    }

    .panel-box {
        border-radius: 0.75rem;
        margin-bottom: 1rem;
    }

    .panel-box-header {
        padding: 0.85rem;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .channel-info-brand {
        gap: 0.5rem;
    }

    .channel-logo-icon {
        width: 2.25rem;
        height: 2.25rem;
        font-size: 0.95rem;
    }

    .channel-type-label {
        font-size: 0.65rem;
    }

    .channel-name-title {
        font-size: 0.92rem;
    }

    .auto-refresh-pill {
        font-size: 0.68rem;
    }

    .panel-box-body {
        padding: 0.85rem;
    }

    /* VA Display */
    .va-display-card {
        padding: 0.85rem;
        border-radius: 0.65rem;
    }

    .va-card-top-row {
        flex-wrap: wrap;
        gap: 0.35rem;
    }

    .va-code-title {
        font-size: 0.62rem;
    }

    .va-bank-badge {
        font-size: 0.62rem;
    }

    .va-number-display-row {
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .va-digits-formatted {
        font-size: 1.15rem;
        letter-spacing: 1px;
        word-break: break-all;
    }

    .va-copy-action-btn {
        width: 100%;
        justify-content: center;
        min-height: 2.5rem;
    }

    /* Countdown */
    .countdown-panel {
        padding: 0.75rem;
        border-radius: 0.65rem;
    }

    .countdown-panel-header {
        flex-direction: column;
        gap: 0.35rem;
        align-items: flex-start;
    }

    .countdown-label-left {
        font-size: 0.72rem;
    }

    .countdown-expiry-date {
        font-size: 0.68rem;
    }

    .countdown-cards-row {
        gap: 0.2rem;
        justify-content: center;
    }

    .time-digit-card {
        min-width: 2.5rem;
        padding: 0.35rem 0.4rem;
    }

    .digit-val {
        font-size: 1rem;
    }

    .digit-lbl {
        font-size: 0.55rem;
    }

    .digit-sep {
        font-size: 1rem;
    }

    /* Status Check Strip */
    .check-status-strip {
        flex-direction: column;
        align-items: stretch;
        gap: 0.65rem;
        padding: 0.75rem;
    }

    .check-status-title {
        font-size: 0.78rem;
    }

    .check-status-desc {
        font-size: 0.72rem;
    }

    .check-status-strip .refresh-btn {
        width: 100%;
        justify-content: center;
        min-height: 2.75rem;
    }

    /* Payment Guide Tabs */
    .guide-tabs-bar {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        gap: 0.25rem;
        padding: 0.5rem 0.75rem;
    }

    .guide-tabs-bar::-webkit-scrollbar {
        display: none;
    }

    .guide-tab-btn {
        white-space: nowrap;
        flex-shrink: 0;
        font-size: 0.72rem;
        padding: 0.4rem 0.65rem;
        min-height: 2.25rem;
    }

    .guide-tab-content {
        padding: 0.75rem;
    }

    .guide-step-item {
        gap: 0.5rem;
    }

    .step-num {
        width: 1.5rem;
        height: 1.5rem;
        font-size: 0.68rem;
        flex-shrink: 0;
    }

    .step-text {
        font-size: 0.78rem;
    }

    /* Digital Receipt Card */
    .digital-receipt-card {
        border-radius: 0.75rem;
    }

    .receipt-header {
        padding: 1rem 0.85rem;
    }

    .receipt-logo-wrap {
        width: 2.25rem;
        height: 2.25rem;
        font-size: 1rem;
    }

    .receipt-title {
        font-size: 0.92rem;
    }

    .receipt-id-tag {
        font-size: 0.68rem;
    }

    .receipt-section {
        padding: 0 0.85rem;
    }

    .receipt-row {
        padding: 0.4rem 0;
        flex-wrap: wrap;
        gap: 0.15rem;
    }

    .r-label {
        font-size: 0.68rem;
    }

    .r-val {
        font-size: 0.78rem;
    }

    .receipt-total-box {
        margin: 0 0.75rem 0.85rem;
        padding: 0.85rem;
    }

    .total-heading {
        font-size: 0.65rem;
    }

    .total-amount-display {
        font-size: 1.25rem;
    }

    .receipt-admin-note {
        margin: 0 0.75rem 0.85rem;
        padding: 0.65rem 0.75rem;
    }

    .receipt-footer-actions {
        padding: 0 0.75rem 1rem;
        gap: 0.4rem;
    }

    .receipt-footer-actions .solid-btn {
        min-height: 2.75rem;
        font-size: 0.78rem;
    }

    /* Bukti Transfer */
    .bukti-image {
        border-radius: 0.5rem;
    }

    /* Bottom nav padding */
    .page-body {
        padding-bottom: calc(76px + env(safe-area-inset-bottom, 0px));
    }
}

@media (max-width: 480px) {
    .va-digits-formatted {
        font-size: 1rem;
        letter-spacing: 0.5px;
    }

    .countdown-cards-row {
        gap: 0.15rem;
    }

    .time-digit-card {
        min-width: 2.15rem;
        padding: 0.3rem 0.35rem;
    }

    .digit-val {
        font-size: 0.9rem;
    }

    .total-amount-display {
        font-size: 1.1rem;
    }
}

</style>
