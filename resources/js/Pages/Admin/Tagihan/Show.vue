<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { formatRupiah, formatDate } from '@/utils';
import { computed } from 'vue';

const props = defineProps({
    tagihan: Object,
    pembayarans: Array,
});

const latestPayment = computed(() => {
    // Access from separate pembayarans prop
    return props.pembayarans?.[0] || null;
});

const paymentMethodLabel = computed(() => {
    if (!latestPayment.value) return '-';
    return latestPayment.value.metode_pembayaran?.nama_metode || '-';
});

const isVA = computed(() => {
    return latestPayment.value?.va_number !== null && latestPayment.value?.va_number !== undefined;
});

const paymentModeLabel = computed(() => {
    if (!latestPayment.value) return '-';
    if (isVA.value) return 'Virtual Account';
    if (latestPayment.value.bukti_pembayaran) return 'Transfer Bank';
    return '-';
});

const bankLogo = computed(() => {
    if (!latestPayment.value?.metode_pembayaran?.nama_metode) return 'Bank';
    const name = latestPayment.value.metode_pembayaran.nama_metode;
    const bankColors = {
        'BNI': '#003399',
        'BTN': '#006633',
        'Mandiri': '#0033A0',
        'BRI': '#008C4A',
        'BCA': '#003399',
        'Bank NTB': '#1e40af',
        'NTB Syariah': '#1e40af',
    };
    const key = Object.keys(bankColors).find(k => name.toLowerCase().includes(k.toLowerCase()));
    return key ? { bg: bankColors[key], text: key.substring(0, 3).toUpperCase() } : { bg: '#6b7280', text: name.substring(0, 3).toUpperCase() };
});
</script>
<template>
    <Head title="Detail Tagihan" />
    <AuthenticatedLayout>
        <template #header>
            <div class="page-heading">
                <Link :href="route('admin.tagihan.index')" class="btn btn-secondary btn-sm">&larr; Kembali</Link>
                <span>Detail Tagihan</span>
            </div>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="custom-card">
                            <div class="card-header">
                                <h3 class="card-title">Informasi Tagihan</h3>
                            </div>
                            <div class="card-body">
                                <div class="stat-card">
                                    <div class="stat-card__label">Mahasiswa</div>
                                    <div class="stat-card__value">{{ tagihan.mahasiswa?.nama_lengkap }}</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-card__label">NIM</div>
                                    <div class="stat-card__value">{{ tagihan.mahasiswa?.nim }}</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-card__label">Semester</div>
                                    <div class="stat-card__value">{{ tagihan.semester }}</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-card__label">Tahun Akademik</div>
                                    <div class="stat-card__value">{{ tagihan.tahun_akademik }}</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-card__label">Nominal</div>
                                    <div class="stat-card__value stat-card__value--primary">{{ formatRupiah(tagihan.nominal) }}</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-card__label">Status</div>
                                    <div class="stat-card__value">
                                        <span :class="{
                                            'badge-custom': true,
                                            'badge-success': tagihan.status === 'sudah_dibayar',
                                            'badge-danger': tagihan.status === 'belum_dibayar',
                                            'badge-warning': tagihan.status === 'terlambat'
                                        }">{{ tagihan.status.replace('_', ' ') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="custom-card">
                            <div class="card-header">
                                <h3 class="card-title">Detail Pembayaran</h3>
                            </div>
                            <div class="card-body" v-if="latestPayment">
                                <!-- Payment Mode & Bank -->
                                <div class="payment-mode-section">
                                    <div class="payment-mode-badge" :style="{ background: bankLogo.bg }">
                                        <span class="payment-mode-text">{{ bankLogo.text }}</span>
                                    </div>
                                    <div class="payment-mode-info">
                                        <div class="payment-mode-label">Mode Pembayaran</div>
                                        <div class="payment-mode-value">{{ paymentModeLabel }}</div>
                                    </div>
                                    <div class="payment-bank-info">
                                        <div class="payment-bank-label">Bank / Metode</div>
                                        <div class="payment-bank-value">{{ paymentMethodLabel }}</div>
                                    </div>
                                </div>

                                <!-- VA Number (if VA) -->
                                <div v-if="isVA" class="va-section">
                                    <div class="va-label">Nomor Virtual Account</div>
                                    <div class="va-value font-mono">{{ latestPayment.va_number }}</div>
                                </div>

                                <!-- Payment Details Grid -->
                                <div class="payment-details-grid">
                                    <div class="detail-item">
                                        <span class="detail-label">Nama Pengirim</span>
                                        <span class="detail-value">{{ latestPayment.nama_pengirim || '-' }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Jumlah Bayar</span>
                                        <span class="detail-value detail-value--primary">{{ formatRupiah(latestPayment.jumlah_bayar) }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Tanggal Pembayaran</span>
                                        <span class="detail-value">{{ formatDate(latestPayment.created_at) }}</span>
                                    </div>
                                    <div class="detail-item" v-if="latestPayment.va_expired_at">
                                        <span class="detail-label">Batas Bayar</span>
                                        <span class="detail-value">{{ formatDate(latestPayment.va_expired_at) }}</span>
                                    </div>
                                    <div class="detail-item" v-if="latestPayment.verified_at">
                                        <span class="detail-label">Tanggal Verifikasi</span>
                                        <span class="detail-value">{{ formatDate(latestPayment.verified_at) }}</span>
                                    </div>
                                </div>

                                <!-- Bukti Pembayaran (if transfer) -->
                                <div v-if="!isVA && latestPayment.bukti_pembayaran" class="bukti-section">
                                    <div class="bukti-label">Bukti Transfer</div>
                                    <img :src="`/storage/${latestPayment.bukti_pembayaran}`" class="bukti-img" alt="Bukti Transfer" />
                                </div>

                                <!-- Status -->
                                <div class="status-section">
                                    <div class="status-label">Status Pembayaran</div>
                                    <span :class="{
                                        'badge-custom': true,
                                        'badge-warning': latestPayment.status === 'pending',
                                        'badge-success': latestPayment.status === 'dikonfirmasi',
                                        'badge-danger': latestPayment.status === 'ditolak'
                                    }">{{ latestPayment.status }}</span>
                                </div>

                                <!-- Catatan Admin -->
                                <div v-if="latestPayment.catatan_admin" class="catatan-section">
                                    <div class="catatan-label">Catatan Admin</div>
                                    <div class="catatan-value">{{ latestPayment.catatan_admin }}</div>
                                </div>
                            </div>
                            <div v-else class="card-body">
                                <div class="text-muted text-center py-4">
                                    <i class="fas fa-info-circle"></i> Belum ada pembayaran
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Payment Mode Section */
.payment-mode-section {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
}
.payment-mode-badge {
    width: 48px;
    height: 48px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.payment-mode-text {
    font-size: 1.125rem;
    font-weight: 700;
    color: white;
    letter-spacing: 1px;
}
.payment-mode-info,
.payment-bank-info {
    flex: 1;
}
.payment-mode-label,
.payment-bank-label {
    font-size: 0.6875rem;
    color: #9ca3af;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 0.25rem;
}
.payment-mode-value,
.payment-bank-value {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1f2937;
}

/* VA Section */
.va-section {
    background: #f0f4ff;
    border: 1px solid #c7d2fe;
    border-radius: 0.5rem;
    padding: 0.75rem;
    margin-bottom: 1.5rem;
}
.va-label {
    font-size: 0.6875rem;
    color: #6366f1;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 0.25rem;
}
.va-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #4f46e5;
    letter-spacing: 2px;
}

/* Payment Details Grid */
.payment-details-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}
.detail-item {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 0.75rem;
}
.detail-label {
    display: block;
    font-size: 0.6875rem;
    color: #9ca3af;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 0.25rem;
}
.detail-value {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #1f2937;
}
.detail-value--primary {
    color: #4f46e5;
}

/* Bukti Section */
.bukti-section {
    margin-bottom: 1.5rem;
}
.bukti-label {
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 0.5rem;
}
.bukti-img {
    max-width: 100%;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
}

/* Status Section */
.status-section {
    margin-bottom: 1rem;
}
.status-label {
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

/* Catatan Section */
.catatan-section {
    background: #fef3c7;
    border: 1px solid #fcd34d;
    border-radius: 0.5rem;
    padding: 0.75rem;
}
.catatan-label {
    font-size: 0.6875rem;
    color: #92400e;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 0.25rem;
}
.catatan-value {
    font-size: 0.875rem;
    color: #92400e;
}

/* Responsive */
@media (max-width: 768px) {
    .payment-details-grid {
        grid-template-columns: 1fr;
    }
    .payment-mode-section {
        flex-direction: column;
        text-align: center;
    }
}
</style>
