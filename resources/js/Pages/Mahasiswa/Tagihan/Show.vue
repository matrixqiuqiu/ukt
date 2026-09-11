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

const selectedCategory = ref('virtual_account'); // 'virtual_account' | 'transfer'
const selectedBank = ref(null);
const vaConfirmed = ref(false);

const form = useForm({
    tagihan_id: props.tagihan.id,
    metode_pembayaran_id: '',
    jumlah_bayar: props.tagihan.nominal,
    nama_pengirim: '',
    bukti_pembayaran: null,
    payment_type: 'virtual_account',
});

const bankColors = {
    'BNI': { bg: '#003399', text: 'BNI' },
    'BTN': { bg: '#006633', text: 'BTN' },
    'Mandiri': { bg: '#0033a0', text: 'MDR' },
    'BRI': { bg: '#008c4a', text: 'BRI' },
    'BCA': { bg: '#003399', text: 'BCA' },
    'Bank NTB': { bg: '#0284c7', text: 'NTB' },
    'NTB Syariah': { bg: '#0f766e', text: 'NTB' },
};

const getBankStyle = (nama) => {
    if (!nama) return { bg: '#475569', text: 'BANK' };
    const key = Object.keys(bankColors).find(k => nama.toLowerCase().includes(k.toLowerCase()));
    return key ? bankColors[key] : { bg: '#475569', text: nama.substring(0, 4).toUpperCase() };
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

const vaMethods = computed(() => {
    return (props.metodePembayarans || []).filter(m => 
        m.kategori === 'virtual_account' || 
        m.nama_metode.toLowerCase().includes('virtual account') || 
        m.nama_metode.toLowerCase().includes('va')
    );
});

const transferMethods = computed(() => {
    return (props.metodePembayarans || []).filter(m => 
        m.kategori !== 'virtual_account' && 
        !m.nama_metode.toLowerCase().includes('virtual account') && 
        !m.nama_metode.toLowerCase().includes('va')
    );
});

const switchCategory = (category) => {
    selectedCategory.value = category;
    if (category === 'virtual_account') {
        selectedBank.value = vaMethods.value[0] || props.metodePembayarans?.[0] || null;
    } else {
        selectedBank.value = transferMethods.value[0] || props.metodePembayarans?.[0] || null;
    }
    if (selectedBank.value) {
        selectBank(selectedBank.value);
    }
};

const isVA = computed(() => selectedCategory.value === 'virtual_account' || selectedBank.value?.kategori === 'virtual_account');

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

                        <!-- Bank Selection by Category -->
                        <template v-if="!isPaid">
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

                            <!-- 1. VIRTUAL ACCOUNT FLOW -->
                            <div v-if="selectedCategory === 'virtual_account'" class="va-payment-panel">
                                <div class="va-panel-header">
                                    <div class="va-panel-icon">
                                        <i class="fas fa-bolt"></i>
                                    </div>
                                    <div>
                                        <h3 class="va-panel-title">Virtual Account (Otomatis & Instan)</h3>
                                        <p class="va-panel-sub">Bayar melalui ATM, Mobile Banking, atau Internet Banking 24/7 tanpa upload struk</p>
                                    </div>
                                </div>

                                <!-- If multiple VA banks -->
                                <div v-if="vaMethods.length > 1" style="margin-bottom:1.25rem;">
                                    <label class="section-sublabel">PILIH BANK VIRTUAL ACCOUNT</label>
                                    <div class="va-bank-grid">
                                        <div
                                            v-for="bank in vaMethods"
                                            :key="bank.id"
                                            class="va-bank-card"
                                            :class="{ selected: selectedBank?.id === bank.id }"
                                            @click="selectBank(bank)"
                                        >
                                            <div class="va-bank-logo" :style="{ background: getBankStyle(bank.nama_metode).bg }">
                                                {{ getBankStyle(bank.nama_metode).text }}
                                            </div>
                                            <div class="va-bank-name">{{ bank.nama_metode }}</div>
                                            <div class="va-bank-tag tag-va">Virtual Account</div>
                                            <div class="va-bank-check" v-if="selectedBank?.id === bank.id">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="va-benefit-box">
                                    <div class="benefit-title"><i class="fas fa-shield-alt"></i> Keunggulan Virtual Account:</div>
                                    <ul class="benefit-list">
                                        <li><i class="fas fa-check text-success"></i> Status tagihan otomatis berubah lunas detik itu juga.</li>
                                        <li><i class="fas fa-check text-success"></i> Tidak perlu upload foto bukti pembayaran manual.</li>
                                        <li><i class="fas fa-check text-success"></i> Dapat ditransfer dari mobile banking / ATM bank apa saja (BCA, BRI, Mandiri, BNI, dll).</li>
                                    </ul>
                                </div>

                                <div class="va-detail-grid">
                                    <div class="va-detail-item">
                                        <span class="va-detail-label">Nama Mahasiswa</span>
                                        <span class="va-detail-value">{{ mahasiswa.nama_lengkap }}</span>
                                    </div>
                                    <div class="va-detail-item">
                                        <span class="va-detail-label">NIM</span>
                                        <span class="va-detail-value font-mono">{{ mahasiswa.nim }}</span>
                                    </div>
                                    <div class="va-detail-item">
                                        <span class="va-detail-label">Nominal Pembayaran</span>
                                        <span class="va-detail-value va-detail-amount">{{ vaAmount }}</span>
                                    </div>
                                    <div class="va-detail-item">
                                        <span class="va-detail-label">Batas Waktu Bayar</span>
                                        <span class="va-detail-value">{{ formatDate(tagihan.jatuh_tempo) }}</span>
                                    </div>
                                </div>

                                <div class="va-steps">
                                    <h4 class="va-steps-title">Cara Pembayaran Virtual Account</h4>
                                    <div class="va-step">
                                        <div class="va-step-num">1</div>
                                        <div class="va-step-text">Klik <strong>Terbitkan Virtual Account</strong> di bawah.</div>
                                    </div>
                                    <div class="va-step">
                                        <div class="va-step-num">2</div>
                                        <div class="va-step-text">Buka aplikasi <strong>Mobile Banking</strong> atau kunjungi <strong>ATM</strong> bank mana saja.</div>
                                    </div>
                                    <div class="va-step">
                                        <div class="va-step-num">3</div>
                                        <div class="va-step-text">Pilih menu <strong>Transfer / Pembayaran Virtual Account</strong> dan masukkan nomor VA yang muncul.</div>
                                    </div>
                                    <div class="va-step">
                                        <div class="va-step-num">4</div>
                                        <div class="va-step-text">Periksa nominal & nama, lalu konfirmasi dengan PIN Anda. Tagihan lunas instan!</div>
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
                                        <i class="fas fa-bolt"></i>
                                        {{ form.processing ? 'Menerbitkan VA...' : 'Terbitkan Virtual Account & Bayar' }}
                                    </button>
                                </div>
                            </div>

                            <!-- 2. TRANSFER PAYMENT FLOW -->
                            <div v-else-if="selectedCategory === 'transfer'" class="va-payment-panel">
                                <div class="va-panel-header">
                                    <div class="va-panel-icon transfer-icon">
                                        <i class="fas fa-money-bill-transfer"></i>
                                    </div>
                                    <div>
                                        <h3 class="va-panel-title">Transfer Bank Manual</h3>
                                        <p class="va-panel-sub">Transfer manual ke rekening universitas dan upload bukti transfer</p>
                                    </div>
                                </div>

                                <!-- Bank destination grid -->
                                <div style="margin-bottom:1.25rem;">
                                    <label class="section-sublabel">PILIH REKENING BANK TUJUAN</label>
                                    <div class="va-bank-grid">
                                        <div
                                            v-for="bank in transferMethods"
                                            :key="bank.id"
                                            class="va-bank-card"
                                            :class="{ selected: selectedBank?.id === bank.id }"
                                            @click="selectBank(bank)"
                                        >
                                            <div class="va-bank-logo" :style="{ background: getBankStyle(bank.nama_metode).bg }">
                                                {{ getBankStyle(bank.nama_metode).text }}
                                            </div>
                                            <div class="va-bank-name">{{ bank.nama_metode }}</div>
                                            <div class="va-bank-tag tag-rek font-mono">{{ bank.no_rekening || '-' }}</div>
                                            <div class="va-bank-check" v-if="selectedBank?.id === bank.id">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="va-rek-box" v-if="selectedBank">
                                    <div class="va-rek-label">Nomor Rekening Tujuan</div>
                                    <div class="va-rek-value font-mono">{{ selectedBank.no_rekening || '-' }}</div>
                                    <div class="va-rek-bank">{{ selectedBank.nama_metode }} a.n Universitas Bumigora</div>
                                </div>

                                <div class="va-form-group">
                                    <label class="va-form-label">Nama Pemilik Rekening Pengirim <span class="text-danger">*</span></label>
                                    <input
                                        v-model="form.nama_pengirim"
                                        type="text"
                                        class="va-form-control"
                                        placeholder="Masukkan nama pengirim sesuai rekening/tabungan"
                                    />
                                </div>

                                <div class="va-form-group">
                                    <label class="va-form-label">Upload Bukti Pembayaran <span class="text-danger">*</span></label>
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
                                        <strong>Verifikasi Manual Admin</strong>
                                        <p>Pembayaran transfer manual akan dicek dan diverifikasi oleh admin keuangan dalam 1×24 jam kerja.</p>
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
                                        {{ form.processing ? 'Mengirim Bukti...' : 'Kirim Bukti Pembayaran' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Errors -->
                            <div v-if="Object.keys(form.errors).length" class="va-alert va-alert-danger" style="margin-top:1rem;">
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
    background: #1e293b;
    color: white;
    padding: 1.5rem 2rem;
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
    color: #94a3b8;
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

/* --- Amount Box (Solid White / Slate Box) --- */
.va-amount-box {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.5rem;
    text-align: center;
    margin-bottom: 2rem;
}
.va-amount-label {
    font-size: 0.8125rem;
    color: #64748b;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.va-amount-value {
    font-size: 2.25rem;
    font-weight: 800;
    color: #4f46e5;
    margin: 0.375rem 0;
}
.va-amount-countdown {
    font-size: 0.8125rem;
    color: #b45309;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
}
.va-amount-countdown i { font-size: 0.875rem; }

/* --- Labels & Category Selector --- */
.section-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    color: #334155;
    margin-bottom: 0.625rem;
}

.section-sublabel {
    display: block;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    color: #64748b;
    margin-bottom: 0.5rem;
}

.category-selection-container {
    margin-bottom: 1.5rem;
}

.category-tabs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}

.category-btn {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    padding: 1rem;
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
    width: 36px;
    height: 36px;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.cat-va { background: #eef2ff; color: #4f46e5; }
.cat-tf { background: #e0f2fe; color: #0284c7; }

.cat-badge-rec {
    background: #dcfce7;
    color: #166534;
    font-size: 0.625rem;
    font-weight: 700;
    padding: 0.15rem 0.5rem;
    border-radius: 1rem;
}

.cat-badge-tf {
    background: #f1f5f9;
    color: #475569;
    font-size: 0.625rem;
    font-weight: 700;
    padding: 0.15rem 0.5rem;
    border-radius: 1rem;
}

.cat-title {
    font-size: 0.9375rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.15rem;
}

.cat-sub {
    font-size: 0.75rem;
    color: #64748b;
    line-height: 1.3;
}

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
    border: 1.5px solid #cbd5e1;
    border-radius: 0.75rem;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.15s;
    text-align: center;
    position: relative;
    background: #ffffff;
}
.va-bank-card:hover {
    border-color: #94a3b8;
    background: #f8fafc;
}
.va-bank-card.selected {
    border-color: #4f46e5;
    background: #f5f3ff;
    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2);
}
.va-bank-logo {
    width: 48px;
    height: 48px;
    margin: 0 auto 0.625rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 800;
    color: white;
}
.va-bank-name {
    font-weight: 700;
    color: #0f172a;
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
.va-bank-tag.tag-va { background: #dcfce7; color: #166534; }
.va-bank-tag.tag-rek { background: #f1f5f9; color: #334155; }
.va-bank-check {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    color: #4f46e5;
    font-size: 1.125rem;
}

/* --- Benefit Box --- */
.va-benefit-box {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 0.5rem;
    padding: 0.875rem 1rem;
    margin-bottom: 1.25rem;
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

/* --- Payment Panel --- */
.va-payment-panel {
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 1rem;
    padding: 1.75rem;
    margin-top: 1rem;
}

.va-panel-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
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
.va-panel-icon.transfer-icon { background: #0284c7; }
.va-panel-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}
.va-panel-sub {
    font-size: 0.8125rem;
    color: #64748b;
    margin: 0.125rem 0 0;
}

/* --- VA Detail Grid --- */
.va-detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}
.va-detail-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
}
.va-detail-label {
    display: block;
    font-size: 0.6875rem;
    color: #64748b;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.03em;
    margin-bottom: 0.25rem;
}
.va-detail-value {
    display: block;
    font-size: 0.9375rem;
    font-weight: 700;
    color: #0f172a;
}
.va-detail-amount { color: #4f46e5; }

/* --- Steps --- */
.va-steps {
    margin-bottom: 1.5rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 1rem 1.25rem;
}
.va-steps-title {
    font-size: 0.875rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.75rem;
}
.va-step {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.375rem 0;
}
.va-step-num {
    width: 24px;
    height: 24px;
    background: #334155;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6875rem;
    font-weight: 700;
    flex-shrink: 0;
}
.va-step-text {
    font-size: 0.8125rem;
    color: #334155;
    line-height: 1.5;
}

/* --- Rekening Box --- */
.va-rek-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 1.125rem;
    text-align: center;
    margin-bottom: 1.25rem;
}
.va-rek-label {
    font-size: 0.6875rem;
    color: #64748b;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.04em;
    margin-bottom: 0.25rem;
}
.va-rek-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: 1px;
}
.va-rek-bank {
    font-size: 0.8125rem;
    color: #475569;
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
    color: #334155;
    margin-bottom: 0.375rem;
}
.va-form-control {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 1.5px solid #cbd5e1;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    box-sizing: border-box;
}
.va-form-control:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.15);
}

/* --- Upload Area --- */
.va-upload-area {
    border: 1.5px dashed #cbd5e1;
    border-radius: 0.75rem;
    padding: 1.5rem;
    text-align: center;
    cursor: pointer;
    background: #f8fafc;
    transition: all 0.15s;
}
.va-upload-area:hover {
    border-color: #4f46e5;
    background: #f5f3ff;
}
.va-file-input { display: none; }
.va-upload-placeholder {
    color: #64748b;
}
.va-upload-placeholder i {
    font-size: 1.75rem;
    margin-bottom: 0.375rem;
    display: block;
    color: #64748b;
}
.va-upload-placeholder span {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 0.125rem;
}
.va-upload-placeholder small {
    font-size: 0.6875rem;
    color: #94a3b8;
}
.va-upload-preview {
    color: #4f46e5;
}
.va-upload-preview i {
    font-size: 1.75rem;
    margin-bottom: 0.375rem;
    display: block;
}
.va-upload-preview span {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    margin-bottom: 0.125rem;
}
.va-upload-preview small {
    font-size: 0.6875rem;
    color: #94a3b8;
}

/* --- Buttons --- */
.va-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
}
.va-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
    border: none;
    text-decoration: none;
}
.va-btn-primary {
    background: #4f46e5;
    color: white;
}
.va-btn-primary:hover:not(:disabled) {
    background: #4338ca;
}
.va-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
.va-btn-secondary {
    background: #f1f5f9;
    color: #334155;
}
.va-btn-secondary:hover {
    background: #e2e8f0;
}

/* --- Paid Panel --- */
.va-paid-panel {
    text-align: center;
    padding: 3rem 1rem;
}
.va-paid-icon {
    font-size: 4rem;
    color: #16a34a;
    margin-bottom: 1rem;
}
.va-paid-panel h3 {
    font-size: 1.25rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 0.5rem;
}
.va-paid-panel p {
    color: #64748b;
    margin-bottom: 1.5rem;
}

.beasiswa-tagihan-box {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 0.75rem;
    padding: 0.875rem 1rem;
    margin-bottom: 1.25rem;
}
.beasiswa-tagihan-box .beasiswa-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.5rem;
    background: #059669;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.text-danger { color: #dc2626; }
.text-success { color: #16a34a; }

/* --- Responsive --- */
@media (max-width: 768px) {
    .va-card-header {
        padding: 1.25rem;
        flex-direction: column;
        align-items: flex-start;
    }
    .va-card-body { padding: 1.25rem; }
    .category-tabs { grid-template-columns: 1fr; }
    .va-header-title { font-size: 1.125rem; }
    .va-info-grid { grid-template-columns: 1fr; }
    .va-amount-value { font-size: 1.75rem; }
    .va-bank-grid { grid-template-columns: 1fr; }
    .va-detail-grid { grid-template-columns: 1fr; }
    .va-actions { flex-direction: column; }
    .va-btn { width: 100%; justify-content: center; }
}
</style>
