<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { formatRupiah, formatDate } from '@/utils';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    tagihan: {
        type: Object,
        default: () => null,
    },
    metodePembayarans: {
        type: Array,
        default: () => [],
    },
    mahasiswa: {
        type: Object,
        default: () => ({}),
    },
    vaExpiredAt: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:show', 'payment-success', 'status-changed']);

const { success: toastSuccess, error: toastError, info: toastInfo } = useToast();

const selectedCategory = ref('virtual_account'); // 'virtual_account' | 'transfer'
const selectedMetode = ref(null);
const activeTab = ref('mbanking'); // 'mbanking' | 'atm' | 'ibanking' | 'teller'
const isCopied = ref(false);
const isCheckingStatus = ref(false);
const isCreatingVa = ref(false);
const currentPendingPayment = ref(null);
const paymentConfirmed = ref(false);
const paymentExpired = ref(false);

// Form for transfer payment
const transferForm = useForm({
    tagihan_id: null,
    metode_pembayaran_id: null,
    jumlah_bayar: 0,
    nama_pengirim: '',
    bukti_pembayaran: null,
    payment_type: 'transfer',
});

const filePreview = ref(null);

// Bank styling - Pure solid colors only
const bankStyles = {
    'NTB': { bg: '#0284c7', text: 'NTB' },
    'Bank NTB': { bg: '#0284c7', text: 'NTB' },
    'NTB Syariah': { bg: '#0f766e', text: 'NTB' },
    'BNI': { bg: '#003399', text: 'BNI' },
    'BTN': { bg: '#006633', text: 'BTN' },
    'Mandiri': { bg: '#0033a0', text: 'MDR' },
    'BRI': { bg: '#008c4a', text: 'BRI' },
    'BCA': { bg: '#003399', text: 'BCA' },
};

const getBankStyle = (nama) => {
    if (!nama) return { bg: '#475569', text: 'BANK' };
    const key = Object.keys(bankStyles).find(k => nama.toLowerCase().includes(k.toLowerCase()));
    return key ? bankStyles[key] : { bg: '#475569', text: nama.substring(0, 4).toUpperCase() };
};

// Categorized payment methods
const vaMethods = computed(() => {
    return props.metodePembayarans.filter(m => 
        m.kategori === 'virtual_account' || 
        m.nama_metode.toLowerCase().includes('virtual account') || 
        m.nama_metode.toLowerCase().includes('va')
    );
});

const transferMethods = computed(() => {
    return props.metodePembayarans.filter(m => 
        m.kategori !== 'virtual_account' && 
        !m.nama_metode.toLowerCase().includes('virtual account') && 
        !m.nama_metode.toLowerCase().includes('va')
    );
});

const currentCategoryMethods = computed(() => {
    return selectedCategory.value === 'virtual_account' ? vaMethods.value : transferMethods.value;
});

const switchCategory = (category) => {
    selectedCategory.value = category;
    if (category === 'virtual_account') {
        selectedMetode.value = vaMethods.value[0] || null;
    } else {
        selectedMetode.value = transferMethods.value[0] || null;
    }
    if (selectedMetode.value) {
        selectMetode(selectedMetode.value);
    }
};

// Countdown Timer logic
const countdown = ref({ days: 0, hours: 0, minutes: 0, seconds: 0 });
let timerInterval = null;
let pollStatusInterval = null;

const updateCountdown = () => {
    const activeVa = currentPendingPayment.value;
    if (!activeVa || !activeVa.va_expired_at) {
        countdown.value = { days: 0, hours: 0, minutes: 0, seconds: 0 };
        return;
    }

    const now = Date.now();
    const expiryTime = new Date(activeVa.va_expired_at).getTime();

    if (isNaN(expiryTime) || expiryTime <= now) {
        countdown.value = { days: 0, hours: 0, minutes: 0, seconds: 0 };
        if (currentPendingPayment.value && !paymentConfirmed.value) {
            paymentExpired.value = true;
        }
        return;
    }

    const diff = expiryTime - now;
    const totalSec = Math.floor(diff / 1000);
    countdown.value = {
        days: Math.floor(totalSec / 86400),
        hours: Math.floor((totalSec % 86400) / 3600),
        minutes: Math.floor((totalSec % 3600) / 60),
        seconds: totalSec % 60,
    };
};

const stopTimers = () => {
    if (timerInterval) clearInterval(timerInterval);
    if (pollStatusInterval) clearInterval(pollStatusInterval);
    timerInterval = null;
    pollStatusInterval = null;
};

// Check Status of Payment
const checkPaymentStatus = async (isAuto = false) => {
    if (!currentPendingPayment.value || isCheckingStatus.value) return;

    if (!isAuto) isCheckingStatus.value = true;

    try {
        const res = await axios.post(route('mahasiswa.pembayaran.check-status', currentPendingPayment.value.id));
        if (res.data?.success) {
            const status = res.data.status;
            if (status === 'paid' || status === 'lunas') {
                paymentConfirmed.value = true;
                stopTimers();
                toastSuccess('Pembayaran UKT Anda telah berhasil dikonfirmasi!');
                emit('status-changed', 'paid');
                // Refresh background data
                router.reload({ only: ['tagihans', 'activeTagihan', 'stats'] });
            } else if (status === 'expired') {
                paymentExpired.value = true;
                stopTimers();
                if (!isAuto) toastError('Virtual Account telah kedaluwarsa. Silakan terbitkan VA baru.');
            } else {
                if (!isAuto) toastInfo(res.data.message || 'Pembayaran belum terdeteksi. Silakan selesaikan pembayaran.');
            }
        }
    } catch (err) {
        if (!isAuto) toastError('Gagal memeriksa status pembayaran.');
    } finally {
        if (!isAuto) isCheckingStatus.value = false;
    }
};

const startTimers = () => {
    stopTimers();
    updateCountdown();
    timerInterval = setInterval(updateCountdown, 1000);

    // Auto-poll status if active pending VA
    if (currentPendingPayment.value && !paymentConfirmed.value && !paymentExpired.value) {
        pollStatusInterval = setInterval(() => {
            if (props.show && currentPendingPayment.value && !paymentConfirmed.value && !paymentExpired.value) {
                checkPaymentStatus(true);
            }
        }, 5000);
    }
};

// Sync state whenever drawer is opened or tagihan changes
watch([() => props.show, () => props.tagihan], ([isOpen, newTagihan]) => {
    if (isOpen && newTagihan) {
        paymentConfirmed.value = newTagihan.status === 'sudah_dibayar';
        paymentExpired.value = false;

        // Check if there is an active pending payment
        if (newTagihan.pending_pembayaran) {
            currentPendingPayment.value = { ...newTagihan.pending_pembayaran };
            // Check if already expired
            if (currentPendingPayment.value.va_expired_at && new Date(currentPendingPayment.value.va_expired_at).getTime() <= Date.now()) {
                paymentExpired.value = true;
            }
        } else {
            currentPendingPayment.value = null;
        }

        // Prioritize Virtual Account by default
        selectedCategory.value = 'virtual_account';
        const defaultVa = vaMethods.value[0] || props.metodePembayarans[0] || null;
        selectedMetode.value = defaultVa;

        // Reset transfer form
        transferForm.tagihan_id = newTagihan.id;
        transferForm.metode_pembayaran_id = selectedMetode.value?.id || null;
        transferForm.jumlah_bayar = newTagihan.nominal;
        transferForm.nama_pengirim = props.mahasiswa?.nama_lengkap || '';
        transferForm.bukti_pembayaran = null;
        filePreview.value = null;

        startTimers();
    } else {
        stopTimers();
    }
}, { immediate: true });

onUnmounted(() => {
    stopTimers();
});

const closeDrawer = () => {
    emit('update:show', false);
};

const selectMetode = (metode) => {
    selectedMetode.value = metode;
    transferForm.metode_pembayaran_id = metode.id;
    transferForm.payment_type = metode.kategori === 'virtual_account' ? 'virtual_account' : 'transfer';
};

const copyVaNumber = (vaNum) => {
    if (!vaNum) return;
    navigator.clipboard.writeText(vaNum);
    isCopied.value = true;
    toastSuccess('Nomor Virtual Account berhasil disalin!');
    setTimeout(() => {
        isCopied.value = false;
    }, 3000);
};

const copyRekeningNumber = (rekNum) => {
    if (!rekNum) return;
    navigator.clipboard.writeText(rekNum);
    toastSuccess('Nomor rekening berhasil disalin!');
};

// 1-Click Instant Virtual Account generation
const createInstantVA = async () => {
    if (!props.tagihan || !selectedMetode.value) return;

    isCreatingVa.value = true;
    try {
        const payload = {
            tagihan_id: props.tagihan.id,
            metode_pembayaran_id: selectedMetode.value.id,
            jumlah_bayar: props.tagihan.nominal,
            payment_type: 'virtual_account',
        };

        const res = await axios.post(route('mahasiswa.pembayaran.store'), payload, {
            headers: { 'Accept': 'text/html, application/xhtml+xml' },
        });

        toastSuccess('Nomor Virtual Account berhasil diterbitkan!');
        
        router.reload({
            only: ['tagihans', 'activeTagihan', 'stats'],
            onSuccess: (page) => {
                const updatedList = page.props.tagihans?.data || page.props.tagihans || [];
                const updated = updatedList.find(t => t.id === props.tagihan.id);
                if (updated && updated.pending_pembayaran) {
                    currentPendingPayment.value = updated.pending_pembayaran;
                    paymentExpired.value = false;
                    startTimers();
                } else if (page.props.activeTagihan && page.props.activeTagihan.id === props.tagihan.id && page.props.activeTagihan.pending_pembayaran) {
                    currentPendingPayment.value = page.props.activeTagihan.pending_pembayaran;
                    paymentExpired.value = false;
                    startTimers();
                } else {
                    router.visit(window.location.href, { preserveScroll: true });
                }
            }
        });
    } catch (err) {
        const errorMsg = err.response?.data?.message || err.response?.data?.errors?.payment || 'Gagal membuat Virtual Account. Silakan coba lagi.';
        toastError(errorMsg);
    } finally {
        isCreatingVa.value = false;
    }
};

// Submit Transfer manual
const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    // Client-side file size validation (5MB max)
    if (file.size > 5 * 1024 * 1024) {
        toastError('Ukuran file bukti pembayaran terlalu besar (Maksimal 5MB). Silakan pilih foto dengan resolusi lebih kecil.');
        e.target.value = '';
        transferForm.bukti_pembayaran = null;
        filePreview.value = null;
        return;
    }

    transferForm.bukti_pembayaran = file;
    filePreview.value = URL.createObjectURL(file);
};

const submitTransfer = () => {
    if (!transferForm.nama_pengirim || !transferForm.bukti_pembayaran) {
        toastError('Lengkapi nama pengirim dan upload bukti transfer');
        return;
    }

    transferForm.post(route('mahasiswa.pembayaran.store'), {
        forceFormData: true,
        onSuccess: () => {
            toastSuccess('Bukti pembayaran berhasil dikirim!');
            closeDrawer();
            emit('payment-success');
        },
        onError: (errs) => {
            const firstErr = Object.values(errs)[0];
            toastError(firstErr || 'Gagal mengirim pembayaran transfer');
        },
    });
};

const resetAndPayAgain = () => {
    currentPendingPayment.value = null;
    paymentExpired.value = false;
    paymentConfirmed.value = false;
    selectedCategory.value = 'virtual_account';
    selectedMetode.value = vaMethods.value[0] || props.metodePembayarans[0] || null;
};
</script>

<template>
    <!-- Slide-over Drawer Wrapper -->
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="show"
                class="quickpay-backdrop"
                @click="closeDrawer"
                aria-hidden="true"
            ></div>
        </Transition>

        <Transition name="slide">
            <div
                v-if="show && tagihan"
                class="quickpay-drawer"
                role="dialog"
                aria-modal="true"
                @keydown.esc="closeDrawer"
            >
                <!-- Drawer Header (Clean Solid Slate) -->
                <div class="drawer-header">
                    <div class="header-main">
                        <div class="header-icon-box">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div>
                            <div class="header-tag">PEMBAYARAN UKT</div>
                            <h3 class="header-title">Semester {{ tagihan.semester }} · {{ tagihan.tahun_akademik }}</h3>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="close-btn"
                        @click="closeDrawer"
                        title="Tutup (Esc)"
                    >
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Drawer Content -->
                <div class="drawer-body">
                    <!-- ============================================== -->
                    <!-- 1. STATE: PAYMENT CONFIRMED / LUNAS            -->
                    <!-- ============================================== -->
                    <div v-if="paymentConfirmed" class="success-screen">
                        <div class="success-icon-box">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h4 class="success-title">Pembayaran Berhasil</h4>
                        <p class="success-desc">
                            Tagihan UKT Semester <strong>{{ tagihan.semester }}</strong> ({{ tagihan.tahun_akademik }}) telah lunas terkonfirmasi.
                        </p>

                        <div class="clean-box" style="margin-bottom:1.5rem;">
                            <div class="bill-row">
                                <span class="label">Total Pembayaran</span>
                                <span class="value font-bold" style="color:#059669;font-size:1.125rem;">{{ formatRupiah(tagihan.nominal) }}</span>
                            </div>
                            <div class="bill-row">
                                <span class="label">Nama Mahasiswa</span>
                                <span class="value">{{ mahasiswa?.nama_lengkap }}</span>
                            </div>
                            <div class="bill-row">
                                <span class="label">NIM</span>
                                <span class="value font-mono">{{ mahasiswa?.nim }}</span>
                            </div>
                            <div class="bill-row">
                                <span class="label">Status</span>
                                <span class="value">
                                    <span class="m-badge m-badge-success">LUNAS</span>
                                </span>
                            </div>
                        </div>

                        <div class="action-stack">
                            <Link
                                v-if="tagihan.last_pembayaran_id || currentPendingPayment?.id"
                                :href="route('mahasiswa.pembayaran.show', tagihan.last_pembayaran_id || currentPendingPayment?.id)"
                                class="btn-solid btn-primary"
                            >
                                <i class="fas fa-receipt"></i> Lihat Bukti Pembayaran
                            </Link>
                            <a
                                :href="route('mahasiswa.tagihan.invoice', tagihan.id)"
                                target="_blank"
                                class="btn-solid btn-secondary"
                            >
                                <i class="fas fa-file-invoice"></i> Unduh Invoice PDF
                            </a>
                            <button
                                type="button"
                                class="btn-solid btn-outline"
                                @click="closeDrawer"
                            >
                                Tutup
                            </button>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- 2. STATE: ACTIVE VIRTUAL ACCOUNT DISPLAY       -->
                    <!-- ============================================== -->
                    <div v-else-if="currentPendingPayment && currentPendingPayment.va_number" class="va-active-container">
                        <!-- Summary Tagihan -->
                        <div class="clean-box" style="margin-bottom:1.25rem;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
                                <span class="m-badge m-badge-secondary">Semester {{ tagihan.semester }} · {{ tagihan.tahun_akademik }}</span>
                                <span v-if="paymentExpired" class="m-badge m-badge-danger">VA Expired</span>
                                <span v-else class="m-badge m-badge-warning">Menunggu Pembayaran</span>
                            </div>
                            <div style="display:flex;justify-content:space-between;align-items:baseline;">
                                <span style="font-size:0.8125rem;color:#475569;">Total Tagihan:</span>
                                <span style="font-size:1.375rem;font-weight:800;color:#0f172a;">{{ formatRupiah(currentPendingPayment.jumlah_bayar || tagihan.nominal) }}</span>
                            </div>
                            <div v-if="tagihan.beasiswa" style="font-size:0.75rem;color:#059669;margin-top:0.5rem;padding-top:0.5rem;border-top:1px dashed #e2e8f0;">
                                <i class="fas fa-tag"></i> Beasiswa {{ tagihan.beasiswa.nama }} (Diskon Rp {{ Number(tagihan.beasiswa.diskon).toLocaleString('id-ID') }})
                            </div>
                        </div>

                        <!-- Solid VA Card (Clean & Solid) -->
                        <div class="va-display-card" :class="{ 'va-card-expired': paymentExpired }">
                            <div class="va-card-header">
                                <div class="va-bank-title">
                                    <div class="va-bank-icon" :style="{ background: getBankStyle(currentPendingPayment.metode_pembayaran_nama).bg }">
                                        {{ getBankStyle(currentPendingPayment.metode_pembayaran_nama).text }}
                                    </div>
                                    <div>
                                        <div class="bank-name">{{ currentPendingPayment.metode_pembayaran_nama || 'Bank NTB Syariah' }}</div>
                                        <div class="bank-sub">Virtual Account Resmi UKT</div>
                                    </div>
                                </div>
                                <span v-if="!paymentExpired" class="va-status-pill">Aktif</span>
                            </div>

                            <div class="va-num-container">
                                <div class="va-num-label">NOMOR VIRTUAL ACCOUNT</div>
                                <div class="va-num-row">
                                    <span class="va-num-digits font-mono">{{ currentPendingPayment.va_number }}</span>
                                    <button
                                        type="button"
                                        class="btn-copy"
                                        :class="{ 'copied': isCopied }"
                                        @click="copyVaNumber(currentPendingPayment.va_number)"
                                    >
                                        <i :class="isCopied ? 'fas fa-check' : 'fas fa-copy'"></i>
                                        <span>{{ isCopied ? 'Tersalin' : 'Salin' }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Countdown Expiry Timer (Clean Solid) -->
                            <div class="va-timer-row" v-if="!paymentExpired">
                                <span class="timer-label"><i class="fas fa-clock"></i> Sisa Waktu Bayar:</span>
                                <span class="timer-digits font-mono">
                                    <template v-if="countdown.days > 0">
                                        {{ countdown.days }} hari
                                    </template>
                                    {{ String(countdown.hours).padStart(2, '0') }}:{{ String(countdown.minutes).padStart(2, '0') }}:{{ String(countdown.seconds).padStart(2, '0') }}
                                </span>
                            </div>

                            <div v-else class="va-expired-msg">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Nomor VA ini telah kedaluwarsa. Silakan terbitkan nomor VA baru.</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="action-stack" style="margin-bottom:1.5rem;">
                            <button
                                v-if="!paymentExpired"
                                type="button"
                                class="btn-solid btn-primary"
                                :disabled="isCheckingStatus"
                                @click="checkPaymentStatus(false)"
                            >
                                <i class="fas" :class="isCheckingStatus ? 'fa-spinner fa-spin' : 'fa-sync-alt'"></i>
                                <span>{{ isCheckingStatus ? 'Mengecek Pembayaran...' : 'Cek Status Pembayaran' }}</span>
                            </button>

                            <button
                                v-else
                                type="button"
                                class="btn-solid btn-warning"
                                @click="resetAndPayAgain"
                            >
                                <i class="fas fa-redo"></i> Terbitkan VA Baru
                            </button>

                            <Link
                                :href="route('mahasiswa.pembayaran.show', currentPendingPayment.id)"
                                class="btn-solid btn-outline"
                            >
                                <i class="fas fa-external-link-alt"></i> Detail Pembayaran Lengkap
                            </Link>
                        </div>

                        <!-- Payment Instructions Tabs -->
                        <div class="clean-box">
                            <div style="font-weight:700;font-size:0.9375rem;color:#0f172a;margin-bottom:0.75rem;">
                                <i class="fas fa-info-circle text-primary" style="margin-right:0.375rem;"></i>
                                Petunjuk Pembayaran
                            </div>

                            <div class="solid-tabs">
                                <button
                                    type="button"
                                    class="solid-tab"
                                    :class="{ active: activeTab === 'mbanking' }"
                                    @click="activeTab = 'mbanking'"
                                >
                                    M-Banking
                                </button>
                                <button
                                    type="button"
                                    class="solid-tab"
                                    :class="{ active: activeTab === 'atm' }"
                                    @click="activeTab = 'atm'"
                                >
                                    ATM
                                </button>
                                <button
                                    type="button"
                                    class="solid-tab"
                                    :class="{ active: activeTab === 'ibanking' }"
                                    @click="activeTab = 'ibanking'"
                                >
                                    I-Banking
                                </button>
                                <button
                                    type="button"
                                    class="solid-tab"
                                    :class="{ active: activeTab === 'teller' }"
                                    @click="activeTab = 'teller'"
                                >
                                    Teller
                                </button>
                            </div>

                            <!-- Guide Details -->
                            <div style="font-size:0.8125rem;color:#334155;line-height:1.5;">
                                <div v-if="activeTab === 'mbanking'" class="guide-list">
                                    <div class="guide-step"><span class="step-no">1</span><span>Buka aplikasi Mobile Banking dan login.</span></div>
                                    <div class="guide-step"><span class="step-no">2</span><span>Pilih menu <strong>Transfer / Pembayaran</strong> &rarr; <strong>Virtual Account</strong>.</span></div>
                                    <div class="guide-step"><span class="step-no">3</span><span>Masukkan nomor VA: <strong class="font-mono text-primary">{{ currentPendingPayment.va_number }}</strong>.</span></div>
                                    <div class="guide-step"><span class="step-no">4</span><span>Pastikan nominal <strong>{{ formatRupiah(currentPendingPayment.jumlah_bayar || tagihan.nominal) }}</strong> & nama <strong>{{ mahasiswa?.nama_lengkap }}</strong> sesuai, lalu konfirmasi dengan PIN.</span></div>
                                </div>

                                <div v-else-if="activeTab === 'atm'" class="guide-list">
                                    <div class="guide-step"><span class="step-no">1</span><span>Masukkan kartu ATM dan PIN Anda di mesin ATM.</span></div>
                                    <div class="guide-step"><span class="step-no">2</span><span>Pilih menu <strong>Transaksi Lainnya</strong> &rarr; <strong>Pembayaran</strong> &rarr; <strong>Virtual Account</strong>.</span></div>
                                    <div class="guide-step"><span class="step-no">3</span><span>Ketikkan nomor VA: <strong class="font-mono text-primary">{{ currentPendingPayment.va_number }}</strong>.</span></div>
                                    <div class="guide-step"><span class="step-no">4</span><span>Periksa rincian data tagihan UKT, lalu pilih <strong>Ya / Benar</strong> untuk menyelesaikan pembayaran.</span></div>
                                </div>

                                <div v-else-if="activeTab === 'ibanking'" class="guide-list">
                                    <div class="guide-step"><span class="step-no">1</span><span>Buka website Internet Banking dan login ke akun Anda.</span></div>
                                    <div class="guide-step"><span class="step-no">2</span><span>Pilih menu <strong>Bayar Tagihan</strong> &rarr; <strong>Virtual Account</strong>.</span></div>
                                    <div class="guide-step"><span class="step-no">3</span><span>Masukkan nomor VA: <strong class="font-mono text-primary">{{ currentPendingPayment.va_number }}</strong>.</span></div>
                                    <div class="guide-step"><span class="step-no">4</span><span>Otorisasi transaksi menggunakan Token / OTP.</span></div>
                                </div>

                                <div v-else-if="activeTab === 'teller'" class="guide-list">
                                    <div class="guide-step"><span class="step-no">1</span><span>Kunjungi kantor cabang Bank terdekat.</span></div>
                                    <div class="guide-step"><span class="step-no">2</span><span>Ambil slip setoran dan sampaikan ke Teller ingin membayar <strong>Virtual Account UKT</strong>.</span></div>
                                    <div class="guide-step"><span class="step-no">3</span><span>Tunjukkan nomor VA: <strong class="font-mono text-primary">{{ currentPendingPayment.va_number }}</strong>.</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- 3. STATE: SELECT CATEGORY & METHOD (CLEAR TABS)-->
                    <!-- ============================================== -->
                    <div v-else class="new-payment-container">
                        <!-- Tagihan Overview -->
                        <div class="clean-box" style="margin-bottom:1.25rem;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;padding-bottom:0.75rem;border-bottom:1px solid #f1f5f9;">
                                <div>
                                    <div style="font-size:0.75rem;color:#64748b;">Tagihan Semester {{ tagihan.semester }}</div>
                                    <div style="font-size:1.125rem;font-weight:800;color:#0f172a;">{{ tagihan.tahun_akademik }}</div>
                                </div>
                                <span class="m-badge m-badge-warning">Belum Lunas</span>
                            </div>

                            <div style="display:flex;flex-direction:column;gap:0.375rem;font-size:0.8125rem;color:#475569;">
                                <div style="display:flex;justify-content:space-between;">
                                    <span>Mahasiswa:</span>
                                    <span style="font-weight:600;color:#0f172a;">{{ mahasiswa?.nama_lengkap }} ({{ mahasiswa?.nim }})</span>
                                </div>
                                <div style="display:flex;justify-content:space-between;">
                                    <span>Program Studi:</span>
                                    <span style="font-weight:600;color:#0f172a;">{{ mahasiswa?.jurusan || '-' }}</span>
                                </div>
                                <div style="display:flex;justify-content:space-between;">
                                    <span>Jatuh Tempo:</span>
                                    <span style="font-weight:600;color:#0f172a;">{{ tagihan.jatuh_tempo }}</span>
                                </div>
                                <div v-if="tagihan.beasiswa" style="display:flex;justify-content:space-between;color:#059669;">
                                    <span>Potongan Beasiswa:</span>
                                    <span>- Rp {{ Number(tagihan.beasiswa.diskon).toLocaleString('id-ID') }}</span>
                                </div>
                                <div style="display:flex;justify-content:space-between;align-items:baseline;padding-top:0.5rem;margin-top:0.25rem;border-top:1px solid #e2e8f0;">
                                    <span style="font-weight:700;color:#0f172a;">Total Tagihan:</span>
                                    <span style="font-size:1.375rem;font-weight:800;color:#4f46e5;">{{ formatRupiah(tagihan.nominal) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- 1. CATEGORY SELECTOR (VIRTUAL ACCOUNT vs TRANSFER MANUAL) -->
                        <div class="category-selection-container">
                            <label class="section-label">PILIH JALUR / KATEGORI PEMBAYARAN</label>

                            <div class="category-tabs">
                                <button
                                    type="button"
                                    class="category-btn"
                                    :class="{ active: selectedCategory === 'virtual_account' }"
                                    @click="switchCategory('virtual_account')"
                                >
                                    <div class="cat-header-row">
                                        <div class="cat-icon-box cat-va">
                                            <i class="fas fa-bolt"></i>
                                        </div>
                                        <span class="cat-badge-rec">Rekomendasi</span>
                                    </div>
                                    <div class="cat-text">
                                        <div class="cat-title">Virtual Account</div>
                                        <div class="cat-sub">Otomatis & Konfirmasi Instan</div>
                                    </div>
                                </button>

                                <button
                                    type="button"
                                    class="category-btn"
                                    :class="{ active: selectedCategory === 'transfer' }"
                                    @click="switchCategory('transfer')"
                                >
                                    <div class="cat-header-row">
                                        <div class="cat-icon-box cat-tf">
                                            <i class="fas fa-money-bill-transfer"></i>
                                        </div>
                                        <span class="cat-badge-tf">Manual</span>
                                    </div>
                                    <div class="cat-text">
                                        <div class="cat-title">Transfer Manual</div>
                                        <div class="cat-sub">Transfer Rekening & Upload Bukti</div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- ============================================== -->
                        <!-- 3A. VIRTUAL ACCOUNT SECTION                    -->
                        <!-- ============================================== -->
                        <div v-if="selectedCategory === 'virtual_account'" class="category-panel">
                            <!-- If multiple VA methods, allow choosing -->
                            <div v-if="vaMethods.length > 1" style="margin-bottom:1rem;">
                                <label class="section-sublabel">PILIH BANK VIRTUAL ACCOUNT</label>
                                <div class="method-list">
                                    <div
                                        v-for="metode in vaMethods"
                                        :key="metode.id"
                                        class="method-box"
                                        :class="{ selected: selectedMetode?.id === metode.id }"
                                        @click="selectMetode(metode)"
                                    >
                                        <div style="display:flex;align-items:center;gap:0.75rem;">
                                            <div class="bank-pill" :style="{ background: getBankStyle(metode.nama_metode).bg }">
                                                {{ getBankStyle(metode.nama_metode).text }}
                                            </div>
                                            <div>
                                                <div style="font-size:0.875rem;font-weight:700;color:#0f172a;">{{ metode.nama_metode }}</div>
                                                <div style="font-size:0.6875rem;color:#166534;font-weight:600;"><i class="fas fa-check-circle"></i> Otomatis & Instan</div>
                                            </div>
                                        </div>
                                        <div>
                                            <i v-if="selectedMetode?.id === metode.id" class="fas fa-check-circle" style="color:#4f46e5;font-size:1.125rem;"></i>
                                            <span v-else class="radio-dot"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Active VA Action Panel -->
                            <div class="clean-box" style="margin-bottom:1rem;">
                                <div v-if="selectedMetode" style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.875rem;padding-bottom:0.75rem;border-bottom:1px solid #f1f5f9;">
                                    <div class="bank-pill" :style="{ background: getBankStyle(selectedMetode.nama_metode).bg }">
                                        {{ getBankStyle(selectedMetode.nama_metode).text }}
                                    </div>
                                    <div>
                                        <div style="font-size:0.875rem;font-weight:700;color:#0f172a;">{{ selectedMetode.nama_metode }}</div>
                                        <div style="font-size:0.6875rem;color:#166534;font-weight:600;"><i class="fas fa-bolt"></i> Layanan Virtual Account Aktif</div>
                                    </div>
                                </div>

                                <div class="va-benefit-box">
                                    <div class="benefit-title"><i class="fas fa-shield-alt"></i> Keunggulan Virtual Account:</div>
                                    <ul class="benefit-list">
                                        <li><i class="fas fa-check text-success"></i> Status pembayaran diverifikasi otomatis detik itu juga.</li>
                                        <li><i class="fas fa-check text-success"></i> Tidak perlu upload atau foto bukti struk transfer.</li>
                                        <li><i class="fas fa-check text-success"></i> Bebas bayar lewat M-Banking atau ATM dari bank apa saja 24/7.</li>
                                    </ul>
                                </div>

                                <button
                                    type="button"
                                    class="btn-solid btn-primary"
                                    style="width:100%;font-size:0.9375rem;padding:0.75rem 1rem;"
                                    :disabled="isCreatingVa"
                                    @click="createInstantVA"
                                >
                                    <i class="fas" :class="isCreatingVa ? 'fa-spinner fa-spin' : 'fa-bolt'"></i>
                                    <span>{{ isCreatingVa ? 'Menerbitkan VA...' : 'Terbitkan Virtual Account & Bayar' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- ============================================== -->
                        <!-- 3B. MANUAL TRANSFER SECTION                    -->
                        <!-- ============================================== -->
                        <div v-else-if="selectedCategory === 'transfer'" class="category-panel">
                            <!-- University Bank Destination Selection -->
                            <div style="margin-bottom:1rem;" v-if="transferMethods.length > 0">
                                <label class="section-sublabel">PILIH REKENING BANK TUJUAN</label>
                                <div class="method-list">
                                    <div
                                        v-for="metode in transferMethods"
                                        :key="metode.id"
                                        class="method-box"
                                        :class="{ selected: selectedMetode?.id === metode.id }"
                                        @click="selectMetode(metode)"
                                    >
                                        <div style="display:flex;align-items:center;gap:0.75rem;">
                                            <div class="bank-pill" :style="{ background: getBankStyle(metode.nama_metode).bg }">
                                                {{ getBankStyle(metode.nama_metode).text }}
                                            </div>
                                            <div>
                                                <div style="font-size:0.875rem;font-weight:700;color:#0f172a;">{{ metode.nama_metode }}</div>
                                                <div style="font-size:0.6875rem;color:#64748b;" class="font-mono">
                                                    No. Rek: {{ metode.no_rekening || '-' }}
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <i v-if="selectedMetode?.id === metode.id" class="fas fa-check-circle" style="color:#4f46e5;font-size:1.125rem;"></i>
                                            <span v-else class="radio-dot"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Selected Bank Details & Upload Form -->
                            <div v-if="selectedMetode" class="clean-box" style="margin-bottom:1rem;">
                                <!-- Rekening Destination Highlight -->
                                <div class="rekening-highlight-box">
                                    <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                                        <div>
                                            <div class="rek-header-label">NOMOR REKENING TUJUAN</div>
                                            <div class="rek-number-text font-mono">{{ selectedMetode.no_rekening || '-' }}</div>
                                            <div class="rek-holder-text">{{ selectedMetode.nama_metode }} a.n Universitas Bumigora</div>
                                        </div>
                                        <button
                                            v-if="selectedMetode.no_rekening"
                                            type="button"
                                            class="btn-copy"
                                            @click="copyRekeningNumber(selectedMetode.no_rekening)"
                                            title="Salin No. Rekening"
                                        >
                                            <i class="fas fa-copy"></i>
                                            <span>Salin</span>
                                        </button>
                                    </div>
                                    <div class="rek-amount-row">
                                        <span>Nominal yang Harus Ditransfer:</span>
                                        <strong class="font-mono text-primary">{{ formatRupiah(tagihan.nominal) }}</strong>
                                    </div>
                                </div>

                                <!-- Form Inputs -->
                                <div style="margin-bottom:0.875rem;">
                                    <label class="form-field-label">Nama Pemilik Rekening Pengirim <span class="text-danger">*</span></label>
                                    <input
                                        v-model="transferForm.nama_pengirim"
                                        type="text"
                                        class="form-control"
                                        placeholder="Contoh: Budi Santoso (sesuai rekening/m-banking)"
                                    />
                                </div>

                                <div style="margin-bottom:1.25rem;">
                                    <label class="form-field-label">Upload Foto / Struk Bukti Transfer <span class="text-danger">*</span></label>
                                    <label class="file-dropzone">
                                        <input
                                            type="file"
                                            accept="image/png,image/jpeg,image/jpg"
                                            style="display:none;"
                                            @change="handleFileChange"
                                        />
                                        <div v-if="!filePreview">
                                            <i class="fas fa-cloud-upload-alt file-icon"></i>
                                            <span class="file-prompt">Pilih file bukti pembayaran</span>
                                            <div class="file-sub">JPG, JPEG, PNG (Maksimal 5MB)</div>
                                        </div>
                                        <div v-else>
                                            <img :src="filePreview" alt="Bukti Transfer" class="preview-img" />
                                            <span class="btn-change-photo"><i class="fas fa-sync-alt"></i> Ganti Foto Bukti</span>
                                        </div>
                                    </label>
                                </div>

                                <div class="transfer-notice-box">
                                    <i class="fas fa-info-circle text-info" style="margin-top:0.125rem;"></i>
                                    <div>
                                        <strong>Verifikasi Manual Admin</strong>
                                        <p>Bukti pembayaran akan dicek dan diverifikasi oleh staf keuangan kampus dalam 1×24 jam kerja.</p>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="btn-solid btn-primary"
                                    style="width:100%;"
                                    :disabled="transferForm.processing || !transferForm.nama_pengirim || !transferForm.bukti_pembayaran"
                                    @click="submitTransfer"
                                >
                                    <i class="fas" :class="transferForm.processing ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i>
                                    <span>{{ transferForm.processing ? 'Mengirim Bukti...' : 'Kirim Bukti Pembayaran' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
/* Backdrop */
.quickpay-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.5);
    z-index: 99998;
}

/* Slide-over Drawer */
.quickpay-drawer {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    max-width: 480px;
    background: #ffffff;
    box-shadow: -4px 0 24px rgba(0, 0, 0, 0.15);
    z-index: 99999;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Header (Solid Navy Slate) */
.drawer-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.125rem 1.25rem;
    background: #1e293b;
    color: #ffffff;
}

.header-main {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.header-icon-box {
    width: 36px;
    height: 36px;
    background: #334155;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: #ffffff;
}

.header-tag {
    font-size: 0.625rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    color: #94a3b8;
}

.header-title {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 700;
    color: #ffffff;
}

.close-btn {
    width: 32px;
    height: 32px;
    background: #334155;
    border: none;
    border-radius: 50%;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    cursor: pointer;
    transition: background 0.15s;
}

.close-btn:hover { background: #475569; }

/* Body */
.drawer-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.25rem;
    background: #f8fafc;
}

/* Animations */
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.slide-enter-active, .slide-leave-active { transition: transform 0.25s ease-out; }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }

/* Clean Solid Box */
.clean-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.125rem;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
}

/* Labels */
.section-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    color: #475569;
    margin-bottom: 0.5rem;
}

.section-sublabel {
    display: block;
    font-size: 0.6875rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    color: #64748b;
    margin-bottom: 0.375rem;
}

.form-field-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 0.25rem;
}

/* Category Selection Container & Tabs */
.category-selection-container {
    margin-bottom: 1.25rem;
}

.category-tabs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.625rem;
}

.category-btn {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    padding: 0.875rem;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 0.625rem;
    cursor: pointer;
    text-align: left;
    transition: all 0.15s;
}

.category-btn:hover {
    border-color: #94a3b8;
    background: #f8fafc;
}

.category-btn.active {
    border-color: #4f46e5;
    background: #f5f3ff;
    box-shadow: 0 0 0 1px #4f46e5;
}

.cat-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    margin-bottom: 0.5rem;
}

.cat-icon-box {
    width: 32px;
    height: 32px;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
}

.cat-va { background: #eef2ff; color: #4f46e5; }
.cat-tf { background: #e0f2fe; color: #0284c7; }

.cat-badge-rec {
    background: #dcfce7;
    color: #166534;
    font-size: 0.5625rem;
    font-weight: 700;
    padding: 0.125rem 0.375rem;
    border-radius: 1rem;
}

.cat-badge-tf {
    background: #f1f5f9;
    color: #475569;
    font-size: 0.5625rem;
    font-weight: 700;
    padding: 0.125rem 0.375rem;
    border-radius: 1rem;
}

.cat-title {
    font-size: 0.8125rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.125rem;
}

.cat-sub {
    font-size: 0.6875rem;
    color: #64748b;
    line-height: 1.3;
}

/* Method List */
.method-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.method-box {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: border-color 0.15s, background 0.15s;
}

.method-box:hover {
    border-color: #94a3b8;
    background: #f8fafc;
}

.method-box.selected {
    border-color: #4f46e5;
    background: #f5f3ff;
}

.bank-pill {
    width: 38px;
    height: 38px;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-weight: 800;
    font-size: 0.6875rem;
    flex-shrink: 0;
}

.radio-dot {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 1.5px solid #cbd5e1;
    border-radius: 50%;
}

/* VA Benefits Box */
.va-benefit-box {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    font-size: 0.8125rem;
    color: #166534;
}

.benefit-title {
    font-weight: 700;
    margin-bottom: 0.375rem;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.benefit-list {
    margin: 0;
    padding: 0;
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.75rem;
    line-height: 1.4;
}

.benefit-list li {
    display: flex;
    align-items: flex-start;
    gap: 0.375rem;
}

.benefit-list li i {
    margin-top: 0.15rem;
    font-size: 0.6875rem;
}

/* Rekening Destination Highlight Box */
.rekening-highlight-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 0.875rem 1rem;
    margin-bottom: 1rem;
}

.rek-header-label {
    font-size: 0.6875rem;
    color: #64748b;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.rek-number-text {
    font-size: 1.25rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0.25rem 0;
}

.rek-holder-text {
    font-size: 0.75rem;
    color: #475569;
}

.rek-amount-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.625rem;
    padding-top: 0.625rem;
    border-top: 1px dashed #cbd5e1;
    font-size: 0.75rem;
    color: #475569;
}

/* Transfer Notice Box */
.transfer-notice-box {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 0.5rem;
    padding: 0.625rem 0.75rem;
    font-size: 0.75rem;
    color: #0369a1;
    line-height: 1.4;
    margin-bottom: 1rem;
}

.transfer-notice-box strong {
    display: block;
    margin-bottom: 0.1rem;
}

.transfer-notice-box p {
    margin: 0;
}

/* VA Display Card */
.va-display-card {
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 0.75rem;
    padding: 1.125rem;
    margin-bottom: 1rem;
}

.va-card-expired {
    border-color: #fca5a5;
    background: #fff5f5;
}

.va-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.875rem;
}

.va-bank-title {
    display: flex;
    align-items: center;
    gap: 0.625rem;
}

.va-bank-icon {
    width: 36px;
    height: 36px;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-weight: 800;
    font-size: 0.6875rem;
}

.bank-name { font-size: 0.875rem; font-weight: 700; color: #0f172a; }
.bank-sub { font-size: 0.6875rem; color: #64748b; }

.va-status-pill {
    background: #dcfce7;
    color: #166534;
    font-size: 0.6875rem;
    font-weight: 700;
    padding: 0.2rem 0.5rem;
    border-radius: 1rem;
}

.va-num-container {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    margin-bottom: 0.875rem;
}

.va-num-label {
    font-size: 0.625rem;
    font-weight: 700;
    color: #64748b;
    letter-spacing: 0.04em;
    margin-bottom: 0.25rem;
}

.va-num-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.5rem;
}

.va-num-digits {
    font-size: 1.25rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: 1px;
}

.btn-copy {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    background: #4f46e5;
    border: none;
    color: #ffffff;
    padding: 0.35rem 0.625rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s;
}

.btn-copy:hover { background: #4338ca; }
.btn-copy.copied { background: #16a34a; }

.va-timer-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f1f5f9;
    padding: 0.5rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
}

.timer-label { color: #475569; display: flex; align-items: center; gap: 0.25rem; }
.timer-digits { font-weight: 700; color: #b45309; }

.va-expired-msg {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    background: #fee2e2;
    color: #991b1b;
    padding: 0.5rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
}

/* Solid Buttons */
.action-stack {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.btn-solid {
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: background 0.15s;
}

.btn-solid.btn-primary {
    background: #4f46e5;
    color: #ffffff;
}
.btn-solid.btn-primary:hover:not(:disabled) { background: #4338ca; }
.btn-solid.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-solid.btn-secondary {
    background: #f1f5f9;
    color: #334155;
}
.btn-solid.btn-secondary:hover { background: #e2e8f0; }

.btn-solid.btn-outline {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #334155;
}
.btn-solid.btn-outline:hover { background: #f8fafc; }

.btn-solid.btn-warning {
    background: #d97706;
    color: #ffffff;
}
.btn-solid.btn-warning:hover { background: #b45309; }

/* Tabs */
.solid-tabs {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.25rem;
    background: #f1f5f9;
    padding: 0.2rem;
    border-radius: 0.375rem;
    margin-bottom: 0.875rem;
}

.solid-tab {
    border: none;
    background: transparent;
    padding: 0.35rem 0.25rem;
    font-size: 0.6875rem;
    font-weight: 600;
    color: #64748b;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.solid-tab.active {
    background: #ffffff;
    color: #0f172a;
    font-weight: 700;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.guide-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.guide-step {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

.step-no {
    width: 20px;
    height: 20px;
    background: #e2e8f0;
    color: #334155;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.625rem;
    font-weight: 700;
    flex-shrink: 0;
    margin-top: 0.1rem;
}

/* Success Screen */
.success-screen {
    text-align: center;
    padding: 1.5rem 0.5rem;
}

.success-icon-box {
    font-size: 3rem;
    color: #16a34a;
    margin-bottom: 0.75rem;
}

.success-title {
    font-size: 1.25rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 0.25rem;
}

.success-desc {
    color: #64748b;
    font-size: 0.875rem;
    margin: 0 0 1.25rem;
}

.bill-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.375rem 0;
    font-size: 0.8125rem;
    border-bottom: 1px solid #f1f5f9;
}
.bill-row:last-child { border-bottom: none; }
.bill-row .label { color: #64748b; }
.bill-row .value { color: #0f172a; font-weight: 600; }

/* Form Control & File Dropzone */
.form-control {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.375rem;
    font-size: 0.8125rem;
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.15);
}

.file-dropzone {
    display: block;
    border: 1.5px dashed #cbd5e1;
    border-radius: 0.5rem;
    padding: 1rem;
    text-align: center;
    background: #f8fafc;
    cursor: pointer;
    transition: border-color 0.15s;
}

.file-dropzone:hover {
    border-color: #4f46e5;
    background: #f5f3ff;
}

.file-icon {
    font-size: 1.5rem;
    color: #64748b;
    margin-bottom: 0.375rem;
    display: block;
}

.file-prompt {
    font-size: 0.8125rem;
    color: #0f172a;
    font-weight: 600;
}

.file-sub {
    font-size: 0.6875rem;
    color: #94a3b8;
    margin-top: 0.125rem;
}

.preview-img {
    max-height: 100px;
    border-radius: 0.375rem;
    margin: 0 auto 0.375rem;
    display: block;
}

.btn-change-photo {
    font-size: 0.75rem;
    color: #4f46e5;
    font-weight: 600;
}

.m-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.2rem 0.5rem;
    border-radius: 1rem;
    font-size: 0.6875rem;
    font-weight: 700;
}
.m-badge-success { background: #dcfce7; color: #166534; }
.m-badge-warning { background: #fef3c7; color: #b45309; }
.m-badge-danger { background: #fee2e2; color: #991b1b; }
.m-badge-secondary { background: #f1f5f9; color: #334155; }

.text-primary { color: #4f46e5; }
.text-success { color: #16a34a; }
.text-danger { color: #dc2626; }
.text-info { color: #0284c7; }

@media (max-width: 540px) {
    .quickpay-drawer { max-width: 100%; }
    .category-tabs { grid-template-columns: 1fr; }
}
</style>
