<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { formatRupiah, formatDate } from '@/utils';
import { ref, computed } from 'vue';

const props = defineProps({
    tagihan: Object,
    pembayarans: Array,
});

const showImageLightbox = ref(false);
const copiedVa = ref(false);

const latestPayment = computed(() => {
    return props.pembayarans?.[0] || null;
});

const isVA = computed(() => {
    return latestPayment.value?.va_number !== null && latestPayment.value?.va_number !== undefined;
});

const copyVa = () => {
    if (!latestPayment.value?.va_number) return;
    navigator.clipboard.writeText(latestPayment.value.va_number);
    copiedVa.value = true;
    setTimeout(() => { copiedVa.value = false; }, 2000);
};
</script>

<template>
    <Head :title="`Detail Tagihan #${tagihan.id} - ${tagihan.mahasiswa?.nama_lengkap || ''}`" />
    <AuthenticatedLayout>
        <template #header>
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div style="display:flex;align-items:center;gap:0.75rem;">
                    <Link :href="route('admin.tagihan.index')" class="solid-btn btn-white-border" title="Kembali ke Daftar Tagihan">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </Link>
                    <div>
                        <h2 class="page-heading" style="font-size:1.375rem;font-weight:700;color:#0f172a;margin-bottom:0.125rem;">
                            Detail Tagihan UKT #{{ tagihan.id }}
                        </h2>
                        <p style="font-size:0.8125rem;color:#64748b;margin:0;">
                            Mahasiswa: <strong>{{ tagihan.mahasiswa?.nama_lengkap }}</strong> &bull; Semester {{ tagihan.semester }} ({{ tagihan.tahun_akademik }})
                        </p>
                    </div>
                </div>

                <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                    <span :class="['solid-badge', {
                        'badge-solid-success': tagihan.status === 'sudah_dibayar',
                        'badge-solid-danger': tagihan.status === 'belum_dibayar',
                        'badge-solid-warning': tagihan.status === 'terlambat'
                    }]" style="font-size:0.875rem;padding:0.4rem 0.875rem;">
                        <i :class="tagihan.status === 'sudah_dibayar' ? 'fas fa-check-circle' : 'fas fa-clock'"></i>
                        <span style="margin-left:0.35rem;">
                            {{ tagihan.status === 'sudah_dibayar' ? 'Tagihan Lunas' : tagihan.status === 'terlambat' ? 'Jatuh Tempo Terlambat' : 'Belum Dibayar' }}
                        </span>
                    </span>

                    <Link
                        v-if="tagihan.status === 'sudah_dibayar'"
                        :href="route('admin.tagihan.invoice', tagihan.id)"
                        class="solid-btn btn-indigo-solid"
                        title="Unduh Lembar Kwitansi / Invoice PDF"
                    >
                        <i class="fas fa-file-invoice"></i> Unduh Invoice PDF
                    </Link>

                    <Link
                        v-if="tagihan.mahasiswa_id"
                        :href="route('admin.mahasiswa.show', tagihan.mahasiswa_id)"
                        class="solid-btn btn-white-border"
                        title="Lihat Profil Lengkap Mahasiswa"
                    >
                        <i class="fas fa-user-graduate"></i> Profil Mahasiswa
                    </Link>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">
                <div class="detail-grid-layout">
                    <!-- ================= LEFT COLUMN: BILL & STUDENT DETAILS ================= -->
                    <div class="detail-left-col">
                        <!-- Hero Card: Nominal Tagihan -->
                        <div class="solid-card hero-bill-card">
                            <div class="hero-bill-header">
                                <div>
                                    <div class="hero-sub">Nominal Tagihan UKT Semester {{ tagihan.semester }}</div>
                                    <div class="hero-amount">{{ formatRupiah(tagihan.nominal) }}</div>
                                </div>
                                <div class="hero-status-pill">
                                    <span :class="['solid-badge', tagihan.status === 'sudah_dibayar' ? 'badge-solid-success' : 'badge-solid-danger']">
                                        {{ tagihan.status === 'sudah_dibayar' ? 'LUNAS' : 'BELUM LUNAS' }}
                                    </span>
                                </div>
                            </div>

                            <div class="hero-meta-row">
                                <div class="meta-item">
                                    <span class="meta-k"><i class="fas fa-calendar-alt"></i> Tahun Akademik:</span>
                                    <span class="meta-v">{{ tagihan.tahun_akademik }}</span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-k"><i class="fas fa-hourglass-half"></i> Batas Jatuh Tempo:</span>
                                    <span class="meta-v" :style="{ color: tagihan.status === 'terlambat' ? '#dc2626' : '#0f172a' }">
                                        {{ tagihan.jatuh_tempo ? formatDate(tagihan.jatuh_tempo) : 'Belum ditentukan' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Data Mahasiswa Terkait -->
                        <div class="solid-card" style="margin-top:1.25rem;">
                            <div class="card-title-bar">
                                <h3><i class="fas fa-user-graduate text-primary"></i> Data Mahasiswa Penanggung Tagihan</h3>
                            </div>
                            <div class="card-content">
                                <div class="info-two-col-grid">
                                    <div class="info-item">
                                        <label>Nama Lengkap</label>
                                        <div class="info-val-strong">{{ tagihan.mahasiswa?.nama_lengkap || '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label>Nomor Induk Mahasiswa (NIM)</label>
                                        <div class="info-val-mono">{{ tagihan.mahasiswa?.nim || '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label>Program Studi</label>
                                        <div class="info-val">{{ tagihan.mahasiswa?.jurusan || '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label>Tahun Angkatan</label>
                                        <div class="info-val">Angkatan {{ tagihan.mahasiswa?.angkatan || '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Riwayat Pembayaran Terkait Tagihan Ini -->
                        <div class="solid-card" style="margin-top:1.25rem;">
                            <div class="card-title-bar">
                                <h3><i class="fas fa-history text-primary"></i> Riwayat Pembayaran Masuk ({{ pembayarans?.length || 0 }})</h3>
                            </div>
                            <div class="card-content" style="padding:0;">
                                <div v-if="pembayarans && pembayarans.length > 0" class="table-responsive">
                                    <table class="solid-table">
                                        <thead>
                                            <tr>
                                                <th style="width:40px;">No</th>
                                                <th>Waktu & ID</th>
                                                <th>Metode</th>
                                                <th>Jumlah Bayar</th>
                                                <th style="text-align:center;">Status</th>
                                                <th style="width:80px;text-align:center;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(p, idx) in pembayarans" :key="p.id">
                                                <td style="text-align:center;color:#64748b;">{{ idx + 1 }}</td>
                                                <td>
                                                    <div style="font-weight:700;color:#0f172a;">{{ formatDate(p.created_at) }}</div>
                                                    <div style="font-size:0.75rem;color:#64748b;">ID: #{{ p.id }}</div>
                                                </td>
                                                <td>
                                                    <div style="font-weight:600;color:#1e293b;">
                                                        {{ p.metode_pembayaran?.nama_metode || 'Transfer Bank' }}
                                                    </div>
                                                    <div v-if="p.va_number" style="font-size:0.75rem;font-family:monospace;color:#2563eb;">
                                                        VA: {{ p.va_number }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <div style="font-weight:800;color:#0f172a;">
                                                        {{ formatRupiah(p.jumlah_bayar) }}
                                                    </div>
                                                </td>
                                                <td style="text-align:center;">
                                                    <span :class="['solid-badge', {
                                                        'badge-solid-success': p.status === 'dikonfirmasi',
                                                        'badge-solid-warning': p.status === 'pending',
                                                        'badge-solid-danger': p.status === 'ditolak' || p.status === 'expired',
                                                    }]">
                                                        {{ p.status === 'dikonfirmasi' ? 'Lunas' : p.status === 'pending' ? 'Menunggu' : p.status }}
                                                    </span>
                                                </td>
                                                <td style="text-align:center;">
                                                    <Link
                                                        :href="route('admin.pembayaran.show', p.id)"
                                                        class="action-btn-custom btn-view"
                                                        title="Lihat Detail Transaksi"
                                                    >
                                                        <i class="fas fa-eye"></i>
                                                    </Link>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-else style="padding:2.5rem;text-align:center;color:#64748b;">
                                    <i class="fas fa-receipt" style="font-size:2rem;color:#cbd5e1;margin-bottom:0.5rem;display:block;"></i>
                                    <div style="font-weight:600;font-size:0.875rem;color:#1e293b;">Belum Ada Transaksi Pembayaran</div>
                                    <div style="font-size:0.75rem;color:#64748b;">Mahasiswa belum melakukan checkout atau transfer untuk tagihan ini.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================= RIGHT COLUMN: PAYMENT GATEWAY & QUICK ACCESS ================= -->
                    <div class="detail-right-col">
                        <!-- Card 1: Detail Pembayaran Terakhir / VA Gateway -->
                        <div class="solid-card">
                            <div class="card-title-bar">
                                <h3><i class="fas fa-credit-card text-primary"></i> Info Gateway & Pembayaran</h3>
                            </div>
                            <div class="card-content">
                                <div v-if="latestPayment">
                                    <!-- VA Section -->
                                    <div v-if="isVA" class="va-side-box">
                                        <div class="va-side-label"><i class="fas fa-university"></i> Nomor Virtual Account (VA)</div>
                                        <div class="va-side-number font-mono">{{ latestPayment.va_number }}</div>
                                        <button type="button" class="btn-copy-va-pill" @click="copyVa" style="margin-top:0.375rem;">
                                            <i :class="copiedVa ? 'fas fa-check text-success' : 'fas fa-copy'"></i>
                                            <span>{{ copiedVa ? 'Tersalin' : 'Salin Nomor VA' }}</span>
                                        </button>
                                    </div>

                                    <div class="side-detail-list" style="margin-top:1rem;">
                                        <div class="side-detail-row">
                                            <span class="side-k">Metode Bayar</span>
                                            <span class="side-v">{{ latestPayment.metode_pembayaran?.nama_metode || 'Transfer Bank' }}</span>
                                        </div>
                                        <div class="side-detail-row">
                                            <span class="side-k">Nama Pengirim</span>
                                            <span class="side-v">{{ latestPayment.nama_pengirim || '-' }}</span>
                                        </div>
                                        <div class="side-detail-row">
                                            <span class="side-k">Jumlah Bayar</span>
                                            <span class="side-v" style="font-weight:800;color:#0f172a;">{{ formatRupiah(latestPayment.jumlah_bayar) }}</span>
                                        </div>
                                        <div class="side-detail-row">
                                            <span class="side-k">Waktu Bayar</span>
                                            <span class="side-v">{{ formatDate(latestPayment.created_at) }}</span>
                                        </div>
                                        <div class="side-detail-row" v-if="latestPayment.verified_at">
                                            <span class="side-k">Waktu Verifikasi</span>
                                            <span class="side-v text-success">{{ formatDate(latestPayment.verified_at) }}</span>
                                        </div>
                                    </div>

                                    <!-- Bukti Struk Mini Preview (if Transfer Manual) -->
                                    <div v-if="!isVA && latestPayment.bukti_pembayaran" style="margin-top:1rem;text-align:center;">
                                        <div style="font-size:0.75rem;font-weight:700;color:#64748b;margin-bottom:0.375rem;text-align:left;">
                                            BUKTI STRUK TRANSFER:
                                        </div>
                                        <div class="receipt-mini-box" @click="showImageLightbox = true" title="Klik untuk perbesar bukti">
                                            <img :src="`/storage/${latestPayment.bukti_pembayaran}`" class="receipt-mini-img" alt="Bukti Transfer" />
                                            <div class="receipt-mini-overlay"><i class="fas fa-search-plus"></i></div>
                                        </div>
                                    </div>

                                    <div style="margin-top:1rem;">
                                        <Link
                                            :href="route('admin.pembayaran.show', latestPayment.id)"
                                            class="solid-btn btn-indigo-solid"
                                            style="width:100%;justify-content:center;"
                                        >
                                            <i class="fas fa-receipt"></i> Buka Rincian Pembayaran
                                        </Link>
                                    </div>
                                </div>

                                <div v-else class="empty-side-box">
                                    <i class="fas fa-file-invoice-dollar" style="font-size:2.5rem;color:#cbd5e1;margin-bottom:0.5rem;display:block;"></i>
                                    <div style="font-weight:700;color:#0f172a;font-size:0.875rem;">Belum Ada Pembayaran</div>
                                    <p style="font-size:0.75rem;color:#64748b;margin:0.25rem 0 0;">
                                        Mahasiswa dapat melakukan pembayaran melalui Virtual Account atau transfer manual via portal mahasiswa.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Dokumen & Cetak Lembar Kwitansi -->
                        <div class="solid-card" style="margin-top:1.25rem;">
                            <div class="card-title-bar">
                                <h3><i class="fas fa-print text-primary"></i> Dokumen & Cetak</h3>
                            </div>
                            <div class="card-content" style="display:grid;gap:0.5rem;">
                                <Link
                                    v-if="tagihan.status === 'sudah_dibayar'"
                                    :href="route('admin.tagihan.invoice', tagihan.id)"
                                    class="solid-btn btn-white-border"
                                    style="width:100%;justify-content:flex-start;"
                                >
                                    <i class="fas fa-file-pdf text-danger"></i> Unduh Invoice Resmi PDF
                                </Link>

                                <Link
                                    v-if="tagihan.status === 'sudah_dibayar'"
                                    :href="route('admin.tagihan.print', tagihan.id)"
                                    target="_blank"
                                    class="solid-btn btn-white-border"
                                    style="width:100%;justify-content:flex-start;"
                                >
                                    <i class="fas fa-print"></i> Cetak Lembar Kwitansi
                                </Link>

                                <Link
                                    v-if="tagihan.mahasiswa_id"
                                    :href="route('admin.mahasiswa.show', tagihan.mahasiswa_id)"
                                    class="solid-btn btn-white-border"
                                    style="width:100%;justify-content:flex-start;"
                                >
                                    <i class="fas fa-user-graduate"></i> Buka Profil Mahasiswa Ini
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teleport Modal Lightbox Bukti Transfer -->
        <Teleport to="body">
            <div v-if="showImageLightbox && latestPayment?.bukti_pembayaran" class="modal-overlay" @click="showImageLightbox = false" style="background:rgba(15, 23, 42, 0.85);z-index:200;">
                <div class="modal-card" style="max-width:720px;background:transparent;box-shadow:none;border:none;" @click.stop>
                    <div style="background:#ffffff;border-radius:0.75rem;overflow:hidden;box-shadow:0 25px 50px rgba(0,0,0,0.5);">
                        <div class="modal-header">
                            <div style="font-weight:700;color:#0f172a;font-size:0.9375rem;">
                                Bukti Pembayaran - {{ tagihan.mahasiswa?.nama_lengkap }}
                            </div>
                            <button class="modal-close-btn" @click="showImageLightbox = false">&times;</button>
                        </div>
                        <div style="max-height:75vh;overflow:auto;padding:1rem;background:#0f172a;display:flex;align-items:center;justify-content:center;">
                            <img
                                :src="'/storage/' + latestPayment.bukti_pembayaran"
                                alt="Bukti Pembayaran Penuh"
                                style="max-width:100%;max-height:70vh;object-fit:contain;border-radius:0.375rem;"
                            />
                        </div>
                        <div class="modal-footer" style="justify-content:space-between;">
                            <a :href="'/storage/' + latestPayment.bukti_pembayaran" target="_blank" class="solid-btn btn-white-border">
                                <i class="fas fa-external-link-alt"></i> Buka Resolusi Asli
                            </a>
                            <button type="button" class="solid-btn btn-indigo-solid" @click="showImageLightbox = false">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.detail-grid-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.5fr) minmax(320px, 1fr);
    gap: 1.25rem;
    align-items: start;
}
@media (max-width: 960px) {
    .detail-grid-layout {
        grid-template-columns: 1fr;
    }
}

/* Hero Bill Card */
.hero-bill-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.5rem;
}
.hero-bill-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 1rem;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid #f1f5f9;
}
.hero-sub {
    font-size: 0.8125rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 0.25rem;
}
.hero-amount {
    font-size: 1.875rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
}

.hero-meta-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 1rem;
}
.meta-item {
    font-size: 0.8125rem;
}
.meta-k {
    color: #64748b;
    margin-right: 0.35rem;
}
.meta-v {
    font-weight: 700;
    color: #0f172a;
}

/* Solid Card Standards */
.solid-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    overflow: hidden;
}
.card-title-bar {
    padding: 0.875rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
    background: #ffffff;
}
.card-title-bar h3 {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.card-content {
    padding: 1.25rem;
}

/* Two Column Info Grid */
.info-two-col-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
@media (max-width: 600px) {
    .info-two-col-grid {
        grid-template-columns: 1fr;
    }
}
.info-item label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 0.2rem;
}
.info-val {
    font-size: 0.875rem;
    color: #1e293b;
}
.info-val-strong {
    font-size: 0.875rem;
    font-weight: 700;
    color: #0f172a;
}
.info-val-mono {
    font-family: monospace;
    font-weight: 700;
    font-size: 0.875rem;
    color: #0f172a;
    background: #f1f5f9;
    display: inline-block;
    padding: 0.1rem 0.4rem;
    border-radius: 0.25rem;
}

/* Right VA Box */
.va-side-box {
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.875rem 1rem;
    text-align: center;
}
.va-side-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #475569;
}
.va-side-number {
    font-size: 1.25rem;
    font-weight: 800;
    color: #2563eb;
    margin-top: 0.25rem;
    letter-spacing: 0.04em;
}
.btn-copy-va-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.625rem;
    border-radius: 0.375rem;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    font-size: 0.75rem;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
    transition: all 0.15s;
}
.btn-copy-va-pill:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.side-detail-list {
    display: grid;
    gap: 0.5rem;
}
.side-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8125rem;
    padding-bottom: 0.375rem;
    border-bottom: 1px dashed #f1f5f9;
}
.side-detail-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.side-k {
    color: #64748b;
}
.side-v {
    color: #0f172a;
    font-weight: 600;
    text-align: right;
}

.receipt-mini-box {
    position: relative;
    border-radius: 0.375rem;
    border: 1px solid #cbd5e1;
    overflow: hidden;
    cursor: pointer;
    background: #f8fafc;
    max-height: 160px;
    display: inline-block;
}
.receipt-mini-img {
    max-width: 100%;
    max-height: 160px;
    object-fit: contain;
    display: block;
}
.receipt-mini-overlay {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.4);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    opacity: 0;
    transition: opacity 0.15s;
}
.receipt-mini-box:hover .receipt-mini-overlay {
    opacity: 1;
}

.empty-side-box {
    padding: 2.5rem 1rem;
    text-align: center;
}

/* Solid Badges */
.solid-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.2rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}
.badge-solid-success {
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}
.badge-solid-danger {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}
.badge-solid-warning {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
}

/* Solid Buttons */
.solid-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.875rem;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: opacity 0.15s;
}
.solid-btn:hover {
    opacity: 0.9;
}
.btn-indigo-solid {
    background: #4f46e5;
    color: #ffffff;
}
.btn-white-border {
    background: #ffffff;
    color: #334155;
    border: 1px solid #cbd5e1;
}
.btn-white-border:hover {
    background: #f8fafc;
}

/* Action Icon Buttons */
.action-btn-custom {
    width: 28px;
    height: 28px;
    border-radius: 0.375rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    cursor: pointer;
    border: 1px solid transparent;
    text-decoration: none;
}
.btn-view {
    background: #eff6ff;
    color: #1d4ed8;
    border-color: #bfdbfe;
}
.btn-view:hover {
    background: #dbeafe;
}

/* Solid Table */
.table-responsive {
    overflow-x: auto;
}
.solid-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
}
.solid-table th {
    background: #f8fafc;
    color: #475569;
    font-weight: 700;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
    text-align: left;
}
.solid-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.solid-table tbody tr:hover {
    background: #f8fafc;
}

/* Modals */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.65);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1050;
    padding: 1rem;
}
.modal-card {
    background: #ffffff;
    border-radius: 0.75rem;
    width: 100%;
    max-width: 480px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.25);
    overflow: hidden;
}
.modal-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-close-btn {
    border: none;
    background: none;
    font-size: 1.5rem;
    line-height: 1;
    color: #94a3b8;
    cursor: pointer;
}
.modal-footer {
    padding: 0.875rem 1.25rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    background: #f8fafc;
}
</style>
