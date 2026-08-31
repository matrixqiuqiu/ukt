<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { formatRupiah, formatDate } from '@/utils';

const props = defineProps({
    tagihan: Object,
    metodePembayarans: Array,
    mahasiswa: Object,
    beasiswa: Object,
    vaExpiredAt: String,
});

const selectedBank = ref(null);
const vaConfirmed = ref(false);

const form = useForm({
    tagihan_id: props.tagihan.id,
    metode_pembayaran_id: '',
    jumlah_bayar: props.tagihan.nominal,
    nama_pengirim: '',
    bukti_pembayaran: null,
    payment_type: 'transfer',
});

const bankColors = {
    'BNI': { bg: '#003399', text: 'BNI' },
    'BTN': { bg: '#006633', text: 'BTN' },
    'Mandiri': { bg: '#0033A0', text: 'MDR' },
    'BRI': { bg: '#008C4A', text: 'BRI' },
    'BCA': { bg: '#003399', text: 'BCA' },
    'Bank NTB': { bg: '#1e40af', text: 'NTB' },
    'NTB Syariah': { bg: '#1e40af', text: 'NTB' },
};

const getBankStyle = (nama) => {
    const key = Object.keys(bankColors).find(k => nama.toLowerCase().includes(k.toLowerCase()));
    return key ? bankColors[key] : { bg: '#6b7280', text: nama.substring(0, 3).toUpperCase() };
};

const isPaid = computed(() => {
    return props.tagihan.status === 'sudah_dibayar' || props.tagihan.pembayarans?.some(p => p.status === 'dikonfirmasi');
});

const isDispen = computed(() => props.tagihan.status === 'dispen');

const statusBadge = computed(() => {
    if (isPaid.value) return { label: 'Lunas', icon: 'fas fa-check-circle', cls: 'paid' };
    if (isDispen.value) return { label: 'Dispen', icon: 'fas fa-file-signature', cls: 'dispen' };
    return { label: 'Belum Dibayar', icon: 'fas fa-clock', cls: 'unpaid' };
});

const isVA = computed(() => selectedBank.value?.kategori === 'virtual_account');

const vaNumber = computed(() => {
    if (!selectedBank.value) return '';
    const prefix = selectedBank.value.no_rekening || '';
    const nim = props.mahasiswa.nim || '';
    const suffix = String(nim).slice(0, 6) + Math.floor(10000 + Math.random() * 90000);
    return prefix + suffix;
});

const vaAmount = computed(() => formatRupiah(props.tagihan.nominal));

// Countdown to VA expiry / jatuh_tempo
const countdown = ref({ days: 0, hours: 0, minutes: 0, seconds: 0 });
let timer = null;

const paymentDeadline = computed(() => {
    const now = Date.now();

    const vaTs = props.vaExpiredAt ? new Date(props.vaExpiredAt).getTime() : NaN;
    const jatuhTs = props.tagihan.jatuh_tempo ? new Date(props.tagihan.jatuh_tempo).getTime() : NaN;

    // Only consider deadlines that are still in the future
    const va = !isNaN(vaTs) && vaTs > now ? vaTs : NaN;
    const jatuh = !isNaN(jatuhTs) && jatuhTs > now ? jatuhTs : NaN;

    if (!isNaN(va) && !isNaN(jatuh)) return Math.min(va, jatuh);
    if (!isNaN(va)) return va;
    if (!isNaN(jatuh)) return jatuh;
    return NaN;
});

const updateCountdown = () => {
    const target = paymentDeadline.value;
    const now = Date.now();

    if (isNaN(target) || target <= now) {
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

onMounted(() => {
    updateCountdown();
    timer = setInterval(updateCountdown, 1000);
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});

const selectBank = (bank) => {
    selectedBank.value = bank;
    form.metode_pembayaran_id = bank.id;
    form.payment_type = bank.kategori === 'virtual_account' ? 'virtual_account' : 'transfer';
    vaConfirmed.value = false;
};

const handleFileChange = (e) => {
    form.bukti_pembayaran = e.target.files[0];
};

const submitVA = () => {
    form.post(route('mahasiswa.pembayaran.store'), {
        forceFormData: true,
    });
};

const submitTransfer = () => {
    form.post(route('mahasiswa.pembayaran.store'), {
        forceFormData: true,
    });
};

const copyVANumber = () => {
    navigator.clipboard.writeText(vaNumber.value);
};
</script>

<template>
    <Head title="Pembayaran UKT" />
    <AuthenticatedLayout>
        <template #header>
            <div class="page-heading">
                <Link :href="route('mahasiswa.tagihan.index')" class="m-btn m-btn-secondary m-btn-sm">&larr; Kembali</Link>
                <span>Pembayaran UKT</span>
            </div>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <!-- Payment Card -->
                <div class="va-card">
                    <div class="va-card-header">
                        <div class="va-header-content">
                            <h2 class="va-header-title">Pembayaran UKT</h2>
                            <p class="va-header-sub">Tagihan semester {{ tagihan.semester }} — {{ tagihan.tahun_akademik }}</p>
                        </div>
                        <div class="va-header-badge" :class="statusBadge.cls">
                            <i :class="statusBadge.icon"></i>
                            {{ statusBadge.label }}
                        </div>
                    </div>

                    <div class="va-card-body">
                        <!-- Alert Dispen -->
                        <div class="va-alert va-alert-dispen" v-if="isDispen && !isPaid">
                            <i class="fas fa-file-signature"></i>
                            <div>
                                <strong>Dispensasi Disetujui</strong>
                                <p>Tagihan ini mendapat dispensasi dari bagian keuangan. Batas pembayaran diperpanjang sampai <strong>{{ formatDate(tagihan.jatuh_tempo) }}</strong>.</p>
                            </div>
                        </div>

                        <!-- Alert -->
                        <div class="va-alert va-alert-warning" v-if="!isPaid && !isDispen">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <strong>Batas Waktu Pembayaran</strong>
                                <p>Bayar sebelum <strong>{{ formatDate(tagihan.jatuh_tempo) }}</strong> untuk menghindari sanksi akademik.</p>
                            </div>
                        </div>

                        <!-- Info Mahasiswa -->
                        <div class="va-info-section">
                            <div class="va-info-grid">
                                <div class="va-info-item">
                                    <span class="va-info-label">Nama</span>
                                    <span class="va-info-value">{{ mahasiswa.nama_lengkap }}</span>
                                </div>
                                <div class="va-info-item">
                                    <span class="va-info-label">NIM</span>
                                    <span class="va-info-value font-mono">{{ mahasiswa.nim }}</span>
                                </div>
                                <div class="va-info-item">
                                    <span class="va-info-label">Prodi</span>
                                    <span class="va-info-value">{{ mahasiswa.jurusan }}</span>
                                </div>
                                <div class="va-info-item">
                                    <span class="va-info-label">Semester</span>
                                    <span class="va-info-value">{{ tagihan.semester }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Beasiswa Info -->
                        <div v-if="beasiswa" class="beasiswa-tagihan-box">
                            <div class="beasiswa-icon"><i class="fas fa-graduation-cap"></i></div>
                            <div>
                                <div style="font-weight:800;color:#065f46;">Beasiswa: {{ beasiswa.nama }} ({{ beasiswa.kode }})</div>
                                <div style="font-size:0.8125rem;color:#047857;">{{ beasiswa.jenis }} · Potongan <b>Rp {{ Number(beasiswa.diskon).toLocaleString('id-ID') }}</b> ({{ beasiswa.tipe==='persen' ? beasiswa.nilai+'%' : beasiswa.tipe==='full' ? 'Gratis' : formatRupiah(beasiswa.nilai) }}) · Status {{ beasiswa.status }}</div>
                            </div>
                        </div>

                        <!-- Amount Box -->
                        <div class="va-amount-box">
                            <div class="va-amount-label">Total yang harus dibayar</div>
                            <div class="va-amount-value">{{ formatRupiah(tagihan.nominal) }}</div>
                            <div v-if="beasiswa" style="font-size:0.75rem;color:#059669;margin-top:0.25rem;"><i class="fas fa-tag"></i> Sudah termasuk potongan beasiswa</div>
                            <div class="va-amount-countdown" v-if="!isPaid">
                                <i class="fas fa-hourglass-half"></i>
                                Sisa waktu:
                                <strong>{{ countdown.days }}d {{ countdown.hours }}j {{ countdown.minutes }}m {{ countdown.seconds }}d</strong>
                            </div>
                        </div>

                        <!-- Bank Selection -->
                        <template v-if="!isPaid">
                            <div class="va-section">
                                <h3 class="va-section-title">
                                    <i class="fas fa-university"></i>
                                    Pilih Metode Pembayaran
                                </h3>
                                <div class="va-bank-grid">
                                    <div
                                        v-for="bank in metodePembayarans"
                                        :key="bank.id"
                                        class="va-bank-card"
                                        :class="{ selected: selectedBank?.id === bank.id }"
                                        @click="selectBank(bank)"
                                    >
                                        <div class="va-bank-logo" :style="{ background: getBankStyle(bank.nama_metode).bg }">
                                            {{ getBankStyle(bank.nama_metode).text }}
                                        </div>
                                        <div class="va-bank-name">{{ bank.nama_metode }}</div>
                                        <div class="va-bank-tag" :class="bank.kategori === 'virtual_account' ? 'tag-va' : 'tag-rek'">
                                            {{ bank.kategori === 'virtual_account' ? 'Virtual Account' : 'Transfer Bank' }}
                                        </div>
                                        <div class="va-bank-check" v-if="selectedBank?.id === bank.id">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- VA Payment Flow -->
                            <div v-if="selectedBank && isVA" class="va-payment-panel">
                                <div class="va-panel-header">
                                    <div class="va-panel-icon">
                                        <i class="fas fa-qrcode"></i>
                                    </div>
                                    <div>
                                        <h3 class="va-panel-title">Virtual Account</h3>
                                        <p class="va-panel-sub">Bayar melalui ATM, Mobile Banking, atau Internet Banking</p>
                                    </div>
                                </div>

                                <div class="va-number-box">
                                    <div class="va-number-label">Nomor Virtual Account</div>
                                    <div class="va-number-pending">
                                        <i class="fas fa-lock"></i> Akan diterbitkan setelah konfirmasi
                                    </div>
                                </div>

                                <div class="va-detail-grid">
                                    <div class="va-detail-item">
                                        <span class="va-detail-label">Nama</span>
                                        <span class="va-detail-value">{{ mahasiswa.nama_lengkap }}</span>
                                    </div>
                                    <div class="va-detail-item">
                                        <span class="va-detail-label">NIM</span>
                                        <span class="va-detail-value font-mono">{{ mahasiswa.nim }}</span>
                                    </div>
                                    <div class="va-detail-item">
                                        <span class="va-detail-label">Nominal</span>
                                        <span class="va-detail-value va-detail-amount">{{ vaAmount }}</span>
                                    </div>
                                    <div class="va-detail-item">
                                        <span class="va-detail-label">Berlaku Hingga</span>
                                        <span class="va-detail-value">{{ formatDate(tagihan.jatuh_tempo) }}</span>
                                    </div>
                                </div>

                                <div class="va-steps">
                                    <h4 class="va-steps-title">Cara Pembayaran</h4>
                                    <div class="va-step">
                                        <div class="va-step-num">1</div>
                                        <div class="va-step-text">Klik <strong>Konfirmasi Pembayaran</strong> di bawah untuk mendapatkan nomor VA</div>
                                    </div>
                                    <div class="va-step">
                                        <div class="va-step-num">2</div>
                                        <div class="va-step-text">Buka aplikasi <strong>Mobile Banking</strong> atau kunjungi <strong>ATM</strong> terdekat</div>
                                    </div>
                                    <div class="va-step">
                                        <div class="va-step-num">3</div>
                                        <div class="va-step-text">Pilih menu <strong>Virtual Account</strong> atau <strong>Pembayaran</strong>, masukkan nomor VA</div>
                                    </div>
                                    <div class="va-step">
                                        <div class="va-step-num">4</div>
                                        <div class="va-step-text">Pastikan data benar, lalu <strong>konfirmasi pembayaran</strong></div>
                                    </div>
                                </div>

                                <div class="va-actions">
                                    <Link :href="route('mahasiswa.tagihan.index')" class="va-btn va-btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Kembali
                                    </Link>
                                    <button
                                        class="va-btn va-btn-primary"
                                        :disabled="form.processing"
                                        @click="submitVA"
                                    >
                                        <i class="fas fa-check-circle"></i>
                                        {{ form.processing ? 'Memproses...' : 'Konfirmasi Pembayaran' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Transfer Payment Flow -->
                            <div v-if="selectedBank && !isVA" class="va-payment-panel">
                                <div class="va-panel-header">
                                    <div class="va-panel-icon transfer-icon">
                                        <i class="fas fa-exchange-alt"></i>
                                    </div>
                                    <div>
                                        <h3 class="va-panel-title">Transfer Bank</h3>
                                        <p class="va-panel-sub">Transfer ke rekening universitas dan upload bukti</p>
                                    </div>
                                </div>

                                <div class="va-rek-box" v-if="selectedBank.no_rekening">
                                    <div class="va-rek-label">Nomor Rekening</div>
                                    <div class="va-rek-value font-mono">{{ selectedBank.no_rekening }}</div>
                                    <div class="va-rek-bank">{{ selectedBank.nama_metode }}</div>
                                </div>

                                <div class="va-form-group">
                                    <label class="va-form-label">Nama Pengirim</label>
                                    <input
                                        v-model="form.nama_pengirim"
                                        type="text"
                                        class="va-form-control"
                                        placeholder="Masukkan nama yang melakukan transfer"
                                    />
                                </div>

                                <div class="va-form-group">
                                    <label class="va-form-label">Bukti Pembayaran</label>
                                    <div class="va-upload-area" @click="$refs.fileInput.click()">
                                        <input
                                            ref="fileInput"
                                            type="file"
                                            @change="handleFileChange"
                                            accept="image/*"
                                            class="va-file-input"
                                        />
                                        <div v-if="!form.bukti_pembayaran" class="va-upload-placeholder">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <span>Klik untuk upload bukti transfer</span>
                                            <small>JPG, PNG (Maks. 5MB)</small>
                                        </div>
                                        <div v-else class="va-upload-preview">
                                            <i class="fas fa-file-image"></i>
                                            <span>{{ form.bukti_pembayaran.name }}</span>
                                            <small>Klik untuk mengganti</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="va-alert va-alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <div>
                                        <strong>Catatan</strong>
                                        <p>Pembayaran akan diverifikasi oleh admin dalam 1×24 jam.</p>
                                    </div>
                                </div>

                                <div class="va-actions">
                                    <Link :href="route('mahasiswa.tagihan.index')" class="va-btn va-btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Kembali
                                    </Link>
                                    <button
                                        class="va-btn va-btn-primary"
                                        :disabled="!form.nama_pengirim || !form.bukti_pembayaran || form.processing"
                                        @click="submitTransfer"
                                    >
                                        <i class="fas fa-paper-plane"></i>
                                        {{ form.processing ? 'Mengirim...' : 'Kirim Pembayaran' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Errors -->
                            <div v-if="Object.keys(form.errors).length" class="va-alert va-alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                <div>
                                    <strong>Terjadi Kesalahan</strong>
                                    <p v-for="(err, key) in form.errors" :key="key">{{ err }}</p>
                                </div>
                            </div>
                        </template>

                        <!-- Already Paid -->
                        <div v-else class="va-paid-panel">
                            <div class="va-paid-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3>Pembayaran Sudah Dilakukan</h3>
                            <p>Tagihan ini sudah dibayar. Silakan cek riwayat pembayaran untuk detailnya.</p>
                            <Link :href="route('mahasiswa.riwayat.index')" class="va-btn va-btn-primary">
                                <i class="fas fa-history"></i> Lihat Riwayat
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* =============================================
   VA PAYMENT PAGE - CLEAN UI/UX
   ============================================= */

/* --- Card --- */
.va-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    border: 1px solid #e5e7eb;
}

/* --- Header --- */
.va-card-header {
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
    color: white;
    padding: 1.75rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}
.va-header-title {
    font-size: 1.375rem;
    font-weight: 700;
    margin: 0;
}
.va-header-sub {
    font-size: 0.875rem;
    opacity: 0.85;
    margin: 0.25rem 0 0;
}
.va-header-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.875rem;
    border-radius: 2rem;
    font-size: 0.8125rem;
    font-weight: 600;
}
.va-header-badge.paid {
    background: #d1fae5;
    color: #065f46;
}
.va-header-badge.unpaid {
    background: #fee2e2;
    color: #991b1b;
}
.va-header-badge.dispen {
    background: #dbeafe;
    color: #1d4ed8;
}

/* --- Body --- */
.va-card-body {
    padding: 2rem;
}

/* --- Alert --- */
.va-alert {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-radius: 0.75rem;
    margin-bottom: 1.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
}
.va-alert i {
    font-size: 1.125rem;
    margin-top: 0.125rem;
    flex-shrink: 0;
}
.va-alert strong {
    display: block;
    margin-bottom: 0.125rem;
}
.va-alert p {
    margin: 0;
    opacity: 0.9;
}
.va-alert-warning {
    background: #fef3c7;
    border: 1px solid #fcd34d;
    color: #92400e;
}
.va-alert-info {
    background: #e0f2fe;
    border: 1px solid #7dd3fc;
    color: #0c4a6e;
}
.va-alert-danger {
    background: #fee2e2;
    border: 1px solid #fca5a5;
    color: #991b1b;
}
.va-alert-dispen {
    background: #dbeafe;
    border: 1px solid #93c5fd;
    color: #1e40af;
}

/* --- Info Section --- */
.va-info-section {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}
.va-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}
.va-info-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f3f4f6;
}
.va-info-item:last-child { border-bottom: none; }
.va-info-label {
    color: #6b7280;
    font-size: 0.8125rem;
}
.va-info-value {
    font-weight: 600;
    color: #1f2937;
    font-size: 0.8125rem;
}
.font-mono { font-family: 'SF Mono', 'Fira Code', monospace; }

/* --- Amount Box --- */
.va-amount-box {
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
    border: 2px solid #818cf8;
    border-radius: 0.75rem;
    padding: 1.5rem;
    text-align: center;
    margin-bottom: 2rem;
}
.va-amount-label {
    font-size: 0.8125rem;
    color: #6366f1;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.va-amount-value {
    font-size: 2.25rem;
    font-weight: 700;
    color: #4f46e5;
    margin: 0.375rem 0;
}
.va-amount-countdown {
    font-size: 0.8125rem;
    color: #6366f1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
}
.va-amount-countdown i { font-size: 0.875rem; }

/* --- Section --- */
.va-section { margin-bottom: 1.5rem; }
.va-section-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.va-section-title i { color: #4f46e5; }

/* --- Bank Grid --- */
.va-bank-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 0.75rem;
}
.va-bank-card {
    border: 2px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.25rem;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
    position: relative;
}
.va-bank-card:hover {
    border-color: #818cf8;
    background: #f9fafb;
}
.va-bank-card.selected {
    border-color: #4f46e5;
    background: #eef2ff;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
}
.va-bank-logo {
    width: 56px;
    height: 56px;
    margin: 0 auto 0.75rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.125rem;
    font-weight: 700;
    color: white;
}
.va-bank-name {
    font-weight: 600;
    color: #1f2937;
    font-size: 0.8125rem;
    margin-bottom: 0.25rem;
}
.va-bank-tag {
    display: inline-block;
    padding: 0.125rem 0.5rem;
    border-radius: 2rem;
    font-size: 0.625rem;
    font-weight: 600;
}
.va-bank-tag.tag-va { background: #ede9fe; color: #6d28d9; }
.va-bank-tag.tag-rek { background: #dbeafe; color: #1e40af; }
.va-bank-check {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    color: #4f46e5;
    font-size: 1.25rem;
}

/* --- Payment Panel --- */
.va-payment-panel {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    padding: 1.75rem;
    margin-top: 1.5rem;
    animation: vaSlideIn 0.25s ease;
}
@keyframes vaSlideIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

.va-panel-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}
.va-panel-icon {
    width: 48px;
    height: 48px;
    background: #4f46e5;
    color: white;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}
.va-panel-icon.transfer-icon { background: #0891b2; }
.va-panel-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}
.va-panel-sub {
    font-size: 0.8125rem;
    color: #6b7280;
    margin: 0.125rem 0 0;
}

/* --- VA Number Box --- */
.va-number-box {
    background: #f9fafb;
    border: 2px dashed #d1d5db;
    border-radius: 0.75rem;
    padding: 2rem;
    text-align: center;
    margin-bottom: 1.5rem;
}
.va-number-label {
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.04em;
    margin-bottom: 0.5rem;
}
.va-number-pending {
    color: #9ca3af;
    font-size: 0.9rem;
}
.va-number-pending i {
    margin-right: 0.5rem;
}
.va-number-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #4f46e5;
    font-family: 'SF Mono', 'Fira Code', monospace;
    letter-spacing: 2px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    transition: color 0.2s;
}
.va-number-value:hover { color: #4338ca; }
.va-copy-icon {
    font-size: 1rem;
    opacity: 0.5;
}
.va-number-bank {
    font-size: 0.8125rem;
    color: #6b7280;
    margin-top: 0.375rem;
}

/* --- VA Detail Grid --- */
.va-detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}
.va-detail-item {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 0.75rem;
}
.va-detail-label {
    display: block;
    font-size: 0.6875rem;
    color: #9ca3af;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 0.25rem;
}
.va-detail-value {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #1f2937;
}
.va-detail-amount { color: #4f46e5; }

/* --- Steps --- */
.va-steps {
    margin-bottom: 1.5rem;
}
.va-steps-title {
    font-size: 0.875rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.75rem;
}
.va-step {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.625rem 0;
}
.va-step-num {
    width: 28px;
    height: 28px;
    background: #4f46e5;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    flex-shrink: 0;
}
.va-step-text {
    font-size: 0.875rem;
    color: #374151;
    line-height: 1.5;
    padding-top: 0.25rem;
}
.va-step-number {
    font-family: 'SF Mono', 'Fira Code', monospace;
    color: #4f46e5;
    letter-spacing: 1px;
}

/* --- Confirm Checkbox --- */
.va-confirm-section {
    margin-bottom: 1.5rem;
}
.va-checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    font-size: 0.875rem;
    color: #374151;
    cursor: pointer;
}
.va-checkbox {
    width: 18px;
    height: 18px;
    accent-color: #4f46e5;
    cursor: pointer;
}

/* --- Rekening Box --- */
.va-rek-box {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.25rem;
    text-align: center;
    margin-bottom: 1.5rem;
}
.va-rek-label {
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 0.375rem;
}
.va-rek-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    letter-spacing: 1px;
}
.va-rek-bank {
    font-size: 0.8125rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

/* --- Form --- */
.va-form-group {
    margin-bottom: 1.25rem;
}
.va-form-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.375rem;
}
.va-form-control {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 1.5px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-family: inherit;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.va-form-control:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
}

/* --- Upload Area --- */
.va-upload-area {
    border: 2px dashed #d1d5db;
    border-radius: 0.75rem;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}
.va-upload-area:hover {
    border-color: #818cf8;
    background: #f5f3ff;
}
.va-file-input { display: none; }
.va-upload-placeholder {
    color: #9ca3af;
}
.va-upload-placeholder i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    display: block;
    color: #c4b5fd;
}
.va-upload-placeholder span {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 0.25rem;
}
.va-upload-placeholder small {
    font-size: 0.75rem;
    color: #9ca3af;
}
.va-upload-preview {
    color: #4f46e5;
}
.va-upload-preview i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    display: block;
}
.va-upload-preview span {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}
.va-upload-preview small {
    font-size: 0.75rem;
    color: #9ca3af;
}

/* --- Buttons --- */
.va-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}
.va-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    text-decoration: none;
}
.va-btn-primary {
    background: #4f46e5;
    color: white;
    box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
}
.va-btn-primary:hover:not(:disabled) {
    background: #4338ca;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
}
.va-btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}
.va-btn-secondary {
    background: white;
    color: #374151;
    border: 1.5px solid #d1d5db;
}
.va-btn-secondary:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

/* --- Paid Panel --- */
.va-paid-panel {
    text-align: center;
    padding: 3rem 1rem;
}
.va-paid-icon {
    font-size: 4rem;
    color: #10b981;
    margin-bottom: 1rem;
}
.va-paid-panel h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.5rem;
}
.va-paid-panel p {
    color: #6b7280;
    margin-bottom: 1.5rem;
}

.beasiswa-tagihan-box {
    display:flex; gap:0.75rem; align-items:center;
    background: linear-gradient(135deg,#ecfdf5 0%,#f0fdfa 100%);
    border:1.5px solid #6ee7b7; border-radius:0.75rem; padding:0.875rem 1rem; margin-bottom:1.25rem;
}
.beasiswa-tagihan-box .beasiswa-icon {
    width:2.5rem; height:2.5rem; border-radius:0.75rem; background:#10b981; color:#fff;
    display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0;
}

/* --- Responsive --- */
@media (max-width: 768px) {
    .va-card-header {
        padding: 1.25rem;
        flex-direction: column;
        align-items: flex-start;
    }
    .va-card-body { padding: 1.25rem; }
    .va-header-title { font-size: 1.125rem; }
    .va-info-grid { grid-template-columns: 1fr; }
    .va-amount-value { font-size: 1.75rem; }
    .va-bank-grid { grid-template-columns: 1fr; }
    .va-detail-grid { grid-template-columns: 1fr; }
    .va-number-value { font-size: 1.25rem; letter-spacing: 1px; }
    .va-actions { flex-direction: column; }
    .va-btn { width: 100%; justify-content: center; }
}
</style>
