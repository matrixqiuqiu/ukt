<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { formatRupiah, formatDate } from '@/utils';
import { useToast } from '@/composables/useToast';

const { success, error: toastError } = useToast();

const props = defineProps({
    pembayaran: Object,
    vaExpiredAt: String,
    beasiswa: Object,
});

const isVA = computed(() => !!props.pembayaran.va_number);
const checking = ref(false);
const lastCheck = ref(null);
const bankStatus = ref(null);

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
    // Countdown hanya relevan selama status masih pending
    if (props.pembayaran.status !== 'pending') {
        countdown.value = { days: 0, hours: 0, minutes: 0, seconds: 0 };
        return;
    }

    const now = Date.now();

    // Sumber tunggal: va_expired_at yang tersimpan (expiry VA yang sebenarnya).
    // Jangan pakai fallback env di halaman ini, agar countdown tidak restart.
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

// Stop countdown & auto-refresh begitu status berubah (dikonfirmasi/expired/ditolak)
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

    // Auto-refresh status every 5 seconds (only if still pending & belum expired)
    autoRefreshTimer = setInterval(() => {
        if (props.pembayaran.status === 'pending' && !isExpired.value) {
            checkStatus();
        }
    }, 5000);
});

onUnmounted(() => {
    stopTimers();
});

const statusLabel = computed(() => {
    if (props.pembayaran.status === 'dikonfirmasi') return 'PEMBAYARAN BERHASIL';
    if (props.pembayaran.status === 'ditolak') return 'PEMBAYARAN DITOLAK';
    if (props.pembayaran.status === 'expired' || isExpired.value) return 'VA EXPIRED';
    return 'MENUNGGU PEMBAYARAN';
});

const statusClass = computed(() => {
    if (props.pembayaran.status === 'dikonfirmasi') return 'status-success';
    if (props.pembayaran.status === 'ditolak') return 'status-danger';
    if (props.pembayaran.status === 'expired' || isExpired.value) return 'status-danger';
    return 'status-pending';
});

const displayNominal = computed(() => {
    if (props.beasiswa && Number(props.pembayaran.jumlah_bayar) === 0 && Number(props.beasiswa.diskon) > 0) {
        return Number(props.beasiswa.diskon);
    }
    if (props.beasiswa && props.beasiswa.tipe === 'full' && Number(props.pembayaran.jumlah_bayar) === 0) {
        return Number(props.beasiswa.diskon) || props.pembayaran.tagihan?.nominal || 0;
    }
    return props.pembayaran.jumlah_bayar;
});
const copyVA = () => {
    navigator.clipboard.writeText(props.pembayaran.va_number);
    success('Nomor VA berhasil disalin');
};

const checkStatus = () => {
    checking.value = true;
    axios.post(route('mahasiswa.pembayaran.check-status', props.pembayaran.id))
        .then((response) => {
            lastCheck.value = new Date().toLocaleTimeString('id-ID');
            if (response.data?.success) {
                bankStatus.value = response.data;
                if (response.data.status === 'expired') {
                    props.pembayaran.status = 'expired';
                    toastError('VA sudah expired. Silakan buat pembayaran baru.');
                } else if (response.data.status === 'paid' || response.data.status === 'lunas') {
                    success('Pembayaran sudah terkonfirmasi!');
                    // Update status langsung di frontend (no page reload)
                    props.pembayaran.status = 'dikonfirmasi';
                    props.pembayaran.verified_at = new Date().toISOString();
                } else {
                    // Status pending - not an error, just inform user
                    const msg = response.data.message || 'Belum ada pembayaran terdeteksi';
                    // Use info toast instead of error for pending status
                    if (msg.includes('Mode Testing')) {
                        // Testing mode - show as info, not error
                        console.log(msg);
                    } else {
                        toastError(msg);
                    }
                }
            } else {
                toastError(response.data?.message || 'Gagal mengecek status');
            }
        })
        .catch(() => {
            toastError('Gagal mengecek status pembayaran');
        })
        .finally(() => {
            checking.value = false;
        });
};
</script>

<template>
    <Head title="Detail Pembayaran VA" />
    <AuthenticatedLayout>
        <template #header>
            <div class="page-heading">
                <Link :href="route('mahasiswa.tagihan.index')" class="m-btn m-btn--secondary m-btn--sm">&larr; Kembali</Link>
                <span>Pembayaran Virtual Account</span>
            </div>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <div class="va-show-card">
                    <!-- Header -->
                    <div class="va-show-header">
                        <div class="va-show-header-left">
                            <div class="va-show-bank-name">
                                <i class="fas fa-university"></i>
                                {{ pembayaran.metode_pembayaran?.nama_metode || 'Bank' }} — PEMBAYARAN UKT
                            </div>
                            <h1 class="va-show-title">Selesaikan Pembayaran UKT</h1>
                            <p class="va-show-subtitle">Status transaksi akan diperbarui secara otomatis.</p>
                        </div>
                        <div class="va-show-header-right">
                            <span class="va-show-status-badge" :class="statusClass">
                                <i :class="pembayaran.status === 'dikonfirmasi' ? 'fas fa-check-circle' : (pembayaran.status === 'ditolak' || statusClass === 'status-danger' && (pembayaran.status === 'expired' || isExpired)) ? 'fas fa-hourglass-end' : 'fas fa-clock'"></i>
                                {{ statusLabel }}
                            </span>
                        </div>
                    </div>

                    <!-- Beasiswa Banner -->
                    <div v-if="beasiswa" class="beasiswa-banner">
                        <div class="beasiswa-banner__icon"><i class="fas fa-graduation-cap"></i></div>
                        <div class="beasiswa-banner__info">
                            <div class="beasiswa-banner__title">Beasiswa: {{ beasiswa.nama }} ({{ beasiswa.kode }}) — {{ beasiswa.jenis }}</div>

                        </div>
                    </div>

                    <!-- VA Number Section -->
                    <div v-if="isVA" class="va-show-body">
                        <div class="va-show-number-box">
                            <div class="va-show-number-label">NOMOR VIRTUAL ACCOUNT</div>
                            <div class="va-show-number-row">
                                <div class="va-show-number-value">{{ pembayaran.va_number }}</div>
                                <button class="va-show-copy-btn" @click="copyVA">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                        </div>

                        <!-- Info Cards -->
                        <div class="va-show-info-grid">
                            <div class="va-show-info-card">
                                <div class="va-show-info-label">No. Pembayaran</div>
                                <div class="va-show-info-value font-mono">{{ pembayaran.id }}</div>
                            </div>
                            <div class="va-show-info-card">
                                <div class="va-show-info-label">Nominal</div>
                                <div class="va-show-info-value va-show-info-amount">{{ formatRupiah(displayNominal) }}<div v-if="beasiswa && beasiswa.tipe==='full'" style="font-size:0.65rem;color:#059669;font-weight:700;">Ditanggung Beasiswa</div></div>
                            </div>
                            <div class="va-show-info-card">
                                <div class="va-show-info-label">Batas Bayar</div>
                                <div class="va-show-info-value">{{ formatDate(pembayaran.va_expired_at) }}</div>
                            </div>
                            <div class="va-show-info-card">
                                <div class="va-show-info-label">Sisa Waktu</div>
                                <div class="va-show-info-value va-show-countdown">
                                    <template v-if="pembayaran.status === 'dikonfirmasi'">
                                        <span class="va-show-countdown-done"><i class="fas fa-check-circle"></i> Selesai</span>
                                    </template>
                                    <template v-else>
                                        {{ countdown.days }}d {{ countdown.hours }}j {{ countdown.minutes }}m {{ countdown.seconds }}d
                                    </template>
                                </div>
                            </div>
                            <div class="va-show-info-card">
                                <div class="va-show-info-label">Update Terakhir</div>
                                <div class="va-show-info-value">{{ lastCheck || '-' }}</div>
                            </div>
                        </div>

                        <!-- Status Panel -->
                        <div class="va-show-status-panel" :class="{ 'panel-success': pembayaran.status === 'dikonfirmasi', 'panel-expired': pembayaran.status === 'expired' || isExpired }">
                            <div class="va-show-status-header">
                                <i class="fas fa-hourglass-half" :class="{ 'fa-check-circle': pembayaran.status === 'dikonfirmasi', 'fa-hourglass-end': pembayaran.status === 'expired' || isExpired }"></i>
                                <div>
                                    <strong>{{ statusLabel }}</strong>
                                    <p v-if="isExpired || pembayaran.status === 'expired'">VA sudah melewati batas waktu pembayaran (expired). Silakan buat pembayaran baru untuk mendapatkan VA baru.</p>
                                    <p v-else-if="pembayaran.status === 'pending'">Belum ada feedback dari bank, silakan klik Refresh Status. Jika sudah bayar via ATM / Mobile Banking, status akan segera terupdate.</p>
                                    <p v-else-if="pembayaran.status === 'dikonfirmasi'">Pembayaran Anda sudah terkonfirmasi. Terima kasih.</p>
                                    <p v-else>Pembayaran Anda ditolak. Silakan hubungi admin.</p>
                                </div>
                            </div>
                            <button
                                v-if="pembayaran.status === 'pending' && !isExpired"
                                class="va-show-refresh-btn"
                                :disabled="checking"
                                @click="checkStatus"
                            >
                                <i class="fas fa-sync-alt" :class="{ 'fa-spin': checking }"></i>
                                {{ checking ? 'Mengecek...' : 'Refresh Status' }}
                            </button>
                        </div>

                        <!-- Actions -->
                        <div class="va-show-actions">
                            <Link :href="route('mahasiswa.tagihan.invoice', pembayaran.tagihan_id)" class="va-show-btn va-show-btn-outline">
                                <i class="fas fa-file-invoice"></i> Cetak Invoice
                            </Link>
                            <Link :href="route('mahasiswa.tagihan.index')" class="va-show-btn va-show-btn-outline">
                                <i class="fas fa-arrow-left"></i> Kembali ke Tagihan
                            </Link>
                        </div>
                    </div>

                    <!-- Transfer Mode -->
                    <div v-else class="va-show-body">
                        <div class="va-show-transfer-panel">
                            <div class="va-show-info-grid">
                                <div class="va-show-info-card">
                                    <div class="va-show-info-label">Status</div>
                                    <div class="va-show-info-value">
                                        <span class="va-show-status-badge" :class="statusClass">{{ statusLabel }}</span>
                                    </div>
                                </div>
                                <div class="va-show-info-card">
                                    <div class="va-show-info-label">Tanggal</div>
                                    <div class="va-show-info-value">{{ formatDate(pembayaran.created_at) }}</div>
                                </div>
                                <div class="va-show-info-card">
                                    <div class="va-show-info-label">Jumlah Bayar</div>
                                    <div class="va-show-info-value va-show-info-amount">{{ formatRupiah(displayNominal) }}<div v-if="beasiswa && beasiswa.tipe==='full'" style="font-size:0.65rem;color:#059669;font-weight:700;">Ditanggung Beasiswa</div></div>
                                </div>
                                <div class="va-show-info-card">
                                    <div class="va-show-info-label">Metode</div>
                                    <div class="va-show-info-value">{{ pembayaran.metode_pembayaran?.nama_metode }}</div>
                                </div>
                                <div class="va-show-info-card">
                                    <div class="va-show-info-label">Pengirim</div>
                                    <div class="va-show-info-value">{{ pembayaran.nama_pengirim }}</div>
                                </div>
                                <div class="va-show-info-card">
                                    <div class="va-show-info-label">Tagihan</div>
                                    <div class="va-show-info-value">Semester {{ pembayaran.tagihan?.semester }} — {{ pembayaran.tagihan?.tahun_akademik }}</div>
                                </div>
                            </div>

                            <div v-if="pembayaran.bukti_pembayaran" class="va-show-bukti">
                                <div class="va-show-bukti-label">Bukti Pembayaran</div>
                                <img :src="'/storage/' + pembayaran.bukti_pembayaran" class="va-show-bukti-img" alt="Bukti" />
                            </div>

                            <div v-if="pembayaran.catatan_admin && !pembayaran.catatan_admin.toLowerCase().includes('beasiswa')" class="va-show-alert va-show-alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                <div><strong>Catatan Admin:</strong> {{ pembayaran.catatan_admin }}</div>
                            </div>

                            <div class="va-show-actions">
                                <Link :href="route('mahasiswa.tagihan.index')" class="va-show-btn va-show-btn-outline">
                                    <i class="fas fa-arrow-left"></i> Kembali
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
/* =============================================
   VA PAYMENT SHOW - MATCHING REFERENCE
   ============================================= */

.va-show-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

/* --- Header --- */
.va-show-header {
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
    color: white;
    padding: 1.75rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 1rem;
}
.va-show-bank-name {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    opacity: 0.8;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.va-show-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 0.25rem;
}
.va-show-subtitle {
    font-size: 0.875rem;
    opacity: 0.85;
    margin: 0;
}

/* --- Status Badge --- */
.va-show-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    white-space: nowrap;
}
.va-show-status-badge.status-pending {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}
.va-show-status-badge.status-success {
    background: #d1fae5;
    color: #065f46;
}
.va-show-status-badge.status-danger {
    background: #fee2e2;
    color: #991b1b;
}

/* --- Body --- */
.va-show-body {
    padding: 2rem;
}

/* --- VA Number Box --- */
.va-show-number-box {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}
.va-show-number-label {
    font-size: 0.6875rem;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 0.75rem;
}
.va-show-number-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}
.va-show-number-value {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    font-family: 'SF Mono', 'Fira Code', 'Consolas', monospace;
    letter-spacing: 3px;
}
.va-show-copy-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 1rem;
    background: white;
    border: 1.5px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s;
}
.va-show-copy-btn:hover {
    border-color: #4f46e5;
    color: #4f46e5;
    background: #f5f3ff;
}

/* --- Info Grid --- */
.va-show-info-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}
.va-show-info-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 0.875rem;
    text-align: center;
}
.va-show-info-label {
    font-size: 0.6875rem;
    font-weight: 600;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin-bottom: 0.375rem;
}
.va-show-info-value {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1f2937;
}
.va-show-info-amount {
    color: #4f46e5;
    font-size: 1rem;
}
.va-show-countdown {
    color: #dc2626;
    font-family: 'SF Mono', 'Fira Code', monospace;
    letter-spacing: 1px;
}
.va-show-countdown-done {
    color: #16a34a;
    font-family: inherit;
    letter-spacing: normal;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    font-weight: 600;
}
.font-mono { font-family: 'SF Mono', 'Fira Code', monospace; }

/* --- Status Panel --- */
.va-show-status-panel {
    background: #fffbeb;
    border: 1px solid #fcd34d;
    border-radius: 0.75rem;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}
.va-show-status-panel.panel-expired {
    background: #fee2e2;
    border-color: #fca5a5;
}
.va-show-status-panel.panel-expired .va-show-status-header > i {
    color: #dc2626;
}
.va-show-status-panel.panel-expired .va-show-status-header strong {
    color: #991b1b;
}
.va-show-status-panel.panel-expired .va-show-status-header p {
    color: #7f1d1d;
}
.va-show-status-panel.panel-success {
    background: #ecfdf5;
    border-color: #6ee7b7;
}
.va-show-status-panel.panel-success .va-show-status-header > i {
    color: #059669;
}
.va-show-status-panel.panel-success .va-show-status-header strong {
    color: #065f46;
}
.va-show-status-panel.panel-success .va-show-status-header p {
    color: #047857;
}
.va-show-status-header {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}
.va-show-status-header > i {
    font-size: 1.25rem;
    color: #d97706;
    margin-top: 0.125rem;
}
.va-show-status-header strong {
    display: block;
    color: #92400e;
    margin-bottom: 0.25rem;
}
.va-show-status-header p {
    margin: 0;
    font-size: 0.8125rem;
    color: #a16207;
    line-height: 1.5;
}
.va-show-refresh-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 1rem;
    background: white;
    border: 1.5px solid #d97706;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #92400e;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
    flex-shrink: 0;
}
.va-show-refresh-btn:hover:not(:disabled) {
    background: #fef3c7;
}
.va-show-refresh-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

/* --- Actions --- */
.va-show-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-start;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}
.va-show-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    border: none;
}
.va-show-btn-outline {
    background: white;
    color: #374151;
    border: 1.5px solid #d1d5db;
}
.va-show-btn-outline:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

/* --- Transfer Panel --- */
.va-show-transfer-panel {
    animation: vaFadeIn 0.2s ease;
}
.va-show-bukti {
    margin-top: 1.5rem;
    padding: 1rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
}
.va-show-bukti-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    margin-bottom: 0.75rem;
}
.va-show-bukti-img {
    max-width: 100%;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
}
.beasiswa-banner {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    background: linear-gradient(135deg, #ecfdf5 0%, #f0fdfa 100%);
    border: 1.5px solid #6ee7b7;
    border-radius: 0.75rem;
    padding: 0.875rem 1rem;
    margin-bottom: 1.25rem;
}
.beasiswa-banner__icon {
    width: 2.5rem; height: 2.5rem; border-radius: 0.75rem;
    background: #10b981; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;
}
.beasiswa-banner__title { font-weight: 800; color: #065f46; font-size: 0.9375rem; }
.beasiswa-banner__desc { font-size: 0.8125rem; color: #047857; margin-top: 0.125rem; }
.va-show-alert {
    display: flex;
    align-items: flex-start;
    gap: 0.625rem;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-top: 1rem;
    font-size: 0.875rem;
}
.va-show-alert i { margin-top: 0.125rem; }
.va-show-alert-danger {
    background: #fee2e2;
    border: 1px solid #fca5a5;
    color: #991b1b;
}

@keyframes vaFadeIn {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}

/* --- Responsive --- */
@media (max-width: 992px) {
    .va-show-info-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
@media (max-width: 768px) {
    .va-show-header { padding: 1rem; flex-direction: column; gap:0.75rem; }
    .va-show-body { padding: 1rem; }
    .va-show-title { font-size: 1.125rem; line-height:1.3; }
    .va-show-subtitle { font-size:0.8125rem; }
    .va-show-number-box { padding:1rem; }
    .va-show-number-value { font-size: 1.1rem; letter-spacing: 1px; word-break:break-all; }
    .va-show-number-row { flex-direction: column; align-items:stretch; }
    .va-show-copy-btn { width:100%; justify-content:center; }
    .va-show-info-grid { grid-template-columns: 1fr 1fr; gap:0.5rem; }
    .va-show-info-card { padding:0.625rem; }
    .va-show-info-value { font-size:0.8125rem; }
    .beasiswa-banner { flex-direction: column; align-items:flex-start; }
    .va-show-status-panel { flex-direction: column; padding:1rem; }
    .va-show-actions { flex-direction: column; }
    .va-show-btn { width: 100%; justify-content: center; }
    .page-heading { flex-direction: column; align-items:flex-start; gap:0.5rem; font-size:0.9375rem; }
}
@media (max-width: 380px) {
    .va-show-info-grid { grid-template-columns: 1fr; }
}
</style>
