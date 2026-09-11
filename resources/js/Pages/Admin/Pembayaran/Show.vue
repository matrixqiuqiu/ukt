<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { formatRupiah, formatDate } from '@/utils';

const props = defineProps({
    pembayaran: Object,
    beasiswa: Object,
});

const showConfirmModal = ref(false);
const showRejectModal = ref(false);
const rejectReason = ref('');
const rejectError = ref('');
const showImageLightbox = ref(false);
const copiedVa = ref(false);
const isProcessing = ref(false);

const copyVa = () => {
    if (!props.pembayaran.va_number) return;
    navigator.clipboard.writeText(props.pembayaran.va_number);
    copiedVa.value = true;
    setTimeout(() => { copiedVa.value = false; }, 2000);
};

const executeConfirm = () => {
    isProcessing.value = true;
    router.post(route('admin.pembayaran.verifikasi', props.pembayaran.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            isProcessing.value = false;
            showConfirmModal.value = false;
        }
    });
};

const openRejectModal = () => {
    rejectReason.value = '';
    rejectError.value = '';
    showRejectModal.value = true;
};

const executeReject = () => {
    if (!rejectReason.value.trim()) {
        rejectError.value = 'Harap isi alasan penolakan pembayaran.';
        return;
    }
    isProcessing.value = true;
    router.post(route('admin.pembayaran.tolak', props.pembayaran.id), {
        catatan_admin: rejectReason.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            isProcessing.value = false;
            showRejectModal.value = false;
        }
    });
};
</script>

<template>
    <Head :title="`Detail Pembayaran #${pembayaran.id}`" />
    <AuthenticatedLayout>
        <template #header>
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div style="display:flex;align-items:center;gap:0.75rem;">
                    <Link :href="route('admin.pembayaran.index')" class="solid-btn btn-white-border" title="Kembali ke Daftar Pembayaran">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </Link>
                    <div>
                        <h2 class="page-heading" style="font-size:1.375rem;font-weight:700;color:#0f172a;margin-bottom:0.125rem;">
                            Detail Pembayaran #{{ pembayaran.id }}
                        </h2>
                        <p style="font-size:0.8125rem;color:#64748b;margin:0;">
                            Waktu Transaksi: <strong>{{ formatDate(pembayaran.created_at) }}</strong>
                        </p>
                    </div>
                </div>

                <!-- Header Actions & Status Badge -->
                <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                    <span :class="['solid-badge', {
                        'badge-solid-success': pembayaran.status === 'dikonfirmasi',
                        'badge-solid-warning': pembayaran.status === 'pending',
                        'badge-solid-danger': pembayaran.status === 'ditolak' || pembayaran.status === 'expired',
                    }]" style="font-size:0.875rem;padding:0.4rem 0.875rem;">
                        <i :class="{
                            'fas fa-check-circle': pembayaran.status === 'dikonfirmasi',
                            'fas fa-clock': pembayaran.status === 'pending',
                            'fas fa-times-circle': pembayaran.status === 'ditolak',
                            'fas fa-hourglass-end': pembayaran.status === 'expired',
                        }"></i>
                        <span style="margin-left:0.35rem;">
                            {{ pembayaran.status === 'dikonfirmasi' ? 'Dikonfirmasi (Lunas)' : pembayaran.status === 'pending' ? 'Menunggu Verifikasi' : pembayaran.status === 'expired' ? 'Kedaluwarsa (Expired)' : 'Ditolak' }}
                        </span>
                    </span>

                    <Link
                        v-if="pembayaran.status === 'dikonfirmasi'"
                        :href="route('admin.tagihan.invoice', pembayaran.tagihan_id)"
                        class="solid-btn btn-indigo-solid"
                        title="Unduh Lembar Kwitansi / Invoice PDF"
                    >
                        <i class="fas fa-file-invoice"></i> Unduh Invoice PDF
                    </Link>

                    <Link
                        v-if="pembayaran.tagihan_id"
                        :href="route('admin.tagihan.show', pembayaran.tagihan_id)"
                        class="solid-btn btn-white-border"
                        title="Buka Lembar Tagihan UKT"
                    >
                        <i class="fas fa-file-invoice-dollar"></i> Lihat Tagihan
                    </Link>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">
                <div class="detail-grid-layout">
                    <!-- ================= LEFT COLUMN: PRIMARY DETAILS ================= -->
                    <div class="detail-left-col">
                        <!-- Hero Card: Nilai Transaksi & Virtual Account -->
                        <div class="solid-card hero-payment-card">
                            <div class="hero-payment-header">
                                <div>
                                    <div class="hero-sub">Jumlah Pembayaran Transaksi</div>
                                    <div class="hero-amount">{{ formatRupiah(pembayaran.jumlah_bayar) }}</div>
                                </div>
                                <div class="hero-method-badge">
                                    <i class="fas fa-credit-card"></i>
                                    <span>{{ pembayaran.metode_pembayaran?.nama_metode || 'Transfer Bank Manual' }}</span>
                                </div>
                            </div>

                            <!-- Virtual Account Box (If VA) -->
                            <div v-if="pembayaran.va_number" class="va-display-container">
                                <div class="va-label-row">
                                    <span class="va-title"><i class="fas fa-university"></i> Nomor Virtual Account (VA):</span>
                                    <span v-if="pembayaran.va_expired_at" class="va-deadline">
                                        Batas Waktu: <strong>{{ formatDate(pembayaran.va_expired_at) }}</strong>
                                    </span>
                                </div>
                                <div class="va-number-box">
                                    <span class="va-digits">{{ pembayaran.va_number }}</span>
                                    <button type="button" class="btn-copy-va-pill" @click="copyVa">
                                        <i :class="copiedVa ? 'fas fa-check text-success' : 'fas fa-copy'"></i>
                                        <span>{{ copiedVa ? 'Tersalin' : 'Salin VA' }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Data Mahasiswa & Rincian Tagihan UKT -->
                        <div class="solid-card" style="margin-top:1.25rem;">
                            <div class="card-title-bar">
                                <h3><i class="fas fa-user-graduate text-primary"></i> Data Mahasiswa & Rincian Tagihan</h3>
                            </div>
                            <div class="card-content">
                                <div class="info-two-col-grid">
                                    <div class="info-item">
                                        <label>Nama Lengkap Mahasiswa</label>
                                        <div class="info-val-strong">{{ pembayaran.tagihan?.mahasiswa?.nama_lengkap || '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label>Nomor Induk Mahasiswa (NIM)</label>
                                        <div class="info-val-mono">{{ pembayaran.tagihan?.mahasiswa?.nim || '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label>Program Studi</label>
                                        <div class="info-val">{{ pembayaran.tagihan?.mahasiswa?.jurusan || '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label>Tahun Angkatan</label>
                                        <div class="info-val">Angkatan {{ pembayaran.tagihan?.mahasiswa?.angkatan || '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label>Semester / Periode Akademik</label>
                                        <div class="info-val">
                                            Semester {{ pembayaran.tagihan?.semester }} ({{ pembayaran.tagihan?.tahun_akademik }})
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label>Nominal Pokok Tagihan UKT</label>
                                        <div class="info-val-strong text-primary">
                                            {{ formatRupiah(pembayaran.tagihan?.nominal) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informasi Pengirim & Audit Verifikasi -->
                        <div class="solid-card" style="margin-top:1.25rem;">
                            <div class="card-title-bar">
                                <h3><i class="fas fa-receipt text-primary"></i> Rincian Pengirim & Audit Verifikasi</h3>
                            </div>
                            <div class="card-content">
                                <div class="info-two-col-grid">
                                    <div class="info-item">
                                        <label>Nama Rekening Pengirim</label>
                                        <div class="info-val">{{ pembayaran.nama_pengirim || '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label>Waktu Transaksi Dibuat</label>
                                        <div class="info-val">{{ formatDate(pembayaran.created_at) }}</div>
                                    </div>
                                    <div class="info-item" v-if="pembayaran.verified_at">
                                        <label>Waktu Verifikasi Selesai</label>
                                        <div class="info-val text-success">
                                            <i class="fas fa-check-circle"></i> {{ formatDate(pembayaran.verified_at) }}
                                        </div>
                                    </div>
                                    <div class="info-item" v-if="pembayaran.verifier">
                                        <label>Diverifikasi Oleh Admin</label>
                                        <div class="info-val">
                                            <i class="fas fa-user-shield"></i> {{ pembayaran.verifier?.name || 'Administrator' }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Beasiswa Banner (If Applied) -->
                                <div v-if="beasiswa" class="beasiswa-card-highlight" style="margin-top:1rem;">
                                    <div class="beasiswa-header">
                                        <span class="beasiswa-tag"><i class="fas fa-graduation-cap"></i> Program Beasiswa</span>
                                        <span class="beasiswa-potongan">Hemat {{ formatRupiah(beasiswa.diskon) }}</span>
                                    </div>
                                    <div class="beasiswa-title">{{ beasiswa.nama }} ({{ beasiswa.kode }})</div>
                                    <div class="beasiswa-meta">
                                        Kategori: <strong>{{ beasiswa.jenis }}</strong> &bull;
                                        Skema: <strong>{{ beasiswa.tipe === 'persen' ? beasiswa.nilai + '%' : beasiswa.tipe === 'full' ? 'Gratis 100%' : formatRupiah(beasiswa.nilai) }}</strong> &bull;
                                        Sumber: <strong>{{ beasiswa.sumber }}</strong>
                                    </div>
                                </div>

                                <!-- Admin Notes / Rejection Reason -->
                                <div v-if="pembayaran.catatan_admin" class="solid-notice solid-notice-danger" style="margin-top:1rem;">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <div>
                                        <strong>Catatan Admin / Alasan Penolakan:</strong>
                                        <div style="margin-top:0.25rem;line-height:1.4;">{{ pembayaran.catatan_admin }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Riwayat Transaksi API / Gateway Log (If Available) -->
                        <div v-if="pembayaran.riwayat_transaksi && pembayaran.riwayat_transaksi.length > 0" class="solid-card" style="margin-top:1.25rem;">
                            <div class="card-title-bar">
                                <h3><i class="fas fa-stream text-primary"></i> Log Riwayat Transaksi Gateway</h3>
                            </div>
                            <div class="card-content" style="padding:0;">
                                <div class="table-responsive">
                                    <table class="solid-table">
                                        <thead>
                                            <tr>
                                                <th style="width:40px;">No</th>
                                                <th>Waktu</th>
                                                <th>Aktivitas</th>
                                                <th>Keterangan</th>
                                                <th style="text-align:center;">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(log, idx) in pembayaran.riwayat_transaksi" :key="log.id">
                                                <td style="text-align:center;color:#64748b;">{{ idx + 1 }}</td>
                                                <td>{{ formatDate(log.created_at) }}</td>
                                                <td style="font-weight:600;">{{ log.aktivitas || 'Status Update' }}</td>
                                                <td>{{ log.keterangan || '-' }}</td>
                                                <td style="text-align:center;">
                                                    <span class="solid-badge badge-solid-info">{{ log.status || 'Success' }}</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ================= RIGHT COLUMN: BUKTI TRANSFER & ACTION PANEL ================= -->
                    <div class="detail-right-col">
                        <!-- Card 1: Bukti Pembayaran / Struk -->
                        <div class="solid-card">
                            <div class="card-title-bar">
                                <h3><i class="fas fa-image text-primary"></i> Bukti Pembayaran</h3>
                            </div>
                            <div class="card-content" style="text-align:center;">
                                <div v-if="pembayaran.bukti_pembayaran">
                                    <div class="receipt-large-container" @click="showImageLightbox = true" title="Klik untuk memperbesar struk">
                                        <img
                                            :src="'/storage/' + pembayaran.bukti_pembayaran"
                                            alt="Bukti Pembayaran"
                                            class="receipt-large-img"
                                        />
                                        <div class="receipt-zoom-overlay">
                                            <i class="fas fa-search-plus"></i>
                                            <span>Klik untuk Memperbesar</span>
                                        </div>
                                    </div>
                                    <div style="margin-top:0.75rem;display:flex;gap:0.5rem;justify-content:center;">
                                        <button type="button" class="solid-btn btn-white-border btn-sm" @click="showImageLightbox = true">
                                            <i class="fas fa-search-plus"></i> Perbesar Bukti
                                        </button>
                                        <a :href="'/storage/' + pembayaran.bukti_pembayaran" target="_blank" class="solid-btn btn-white-border btn-sm">
                                            <i class="fas fa-external-link-alt"></i> Buka Asli
                                        </a>
                                    </div>
                                </div>
                                <div v-else class="empty-receipt-box">
                                    <i class="fas fa-file-invoice-dollar" style="font-size:2.5rem;color:#cbd5e1;margin-bottom:0.5rem;display:block;"></i>
                                    <div style="font-weight:700;color:#0f172a;font-size:0.875rem;">Pembayaran Otomatis</div>
                                    <p style="font-size:0.75rem;color:#64748b;margin:0.25rem 0 0;">
                                        Transaksi Virtual Account langsung divalidasi secara real-time oleh sistem perbankan.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Panel Aksi Verifikasi (Hanya Jika Status Pending) -->
                        <div v-if="pembayaran.status === 'pending'" class="solid-card" style="margin-top:1.25rem;">
                            <div class="card-title-bar">
                                <h3><i class="fas fa-tasks text-primary"></i> Aksi Verifikasi Admin</h3>
                            </div>
                            <div class="card-content">
                                <p style="font-size:0.8125rem;color:#64748b;margin:0 0 1rem;line-height:1.4;">
                                    Pastikan nominal transfer dan mutasi rekening bank kampus telah cocok sebelum menyetujui transaksi.
                                </p>
                                <div style="display:grid;gap:0.625rem;">
                                    <button
                                        type="button"
                                        class="solid-btn btn-success-solid"
                                        style="width:100%;justify-content:center;padding:0.625rem;"
                                        @click="showConfirmModal = true"
                                    >
                                        <i class="fas fa-check-circle"></i> Setujui & Lunaskan Tagihan
                                    </button>
                                    <button
                                        type="button"
                                        class="solid-btn btn-danger-solid"
                                        style="width:100%;justify-content:center;padding:0.625rem;"
                                        @click="openRejectModal"
                                    >
                                        <i class="fas fa-times-circle"></i> Tolak Bukti Transfer
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Navigasi & Tautan Terkait -->
                        <div class="solid-card" style="margin-top:1.25rem;">
                            <div class="card-title-bar">
                                <h3><i class="fas fa-link text-primary"></i> Tautan & Dokumen Cepat</h3>
                            </div>
                            <div class="card-content" style="display:grid;gap:0.5rem;">
                                <Link
                                    v-if="pembayaran.tagihan?.mahasiswa_id"
                                    :href="route('admin.mahasiswa.show', pembayaran.tagihan.mahasiswa_id)"
                                    class="solid-btn btn-white-border"
                                    style="width:100%;justify-content:flex-start;"
                                >
                                    <i class="fas fa-user"></i> Lihat Profil Mahasiswa
                                </Link>

                                <Link
                                    v-if="pembayaran.tagihan_id"
                                    :href="route('admin.tagihan.show', pembayaran.tagihan_id)"
                                    class="solid-btn btn-white-border"
                                    style="width:100%;justify-content:flex-start;"
                                >
                                    <i class="fas fa-file-invoice"></i> Buka Lembar Tagihan UKT
                                </Link>

                                <Link
                                    v-if="pembayaran.status === 'dikonfirmasi'"
                                    :href="route('admin.tagihan.print', pembayaran.tagihan_id)"
                                    target="_blank"
                                    class="solid-btn btn-white-border"
                                    style="width:100%;justify-content:flex-start;"
                                >
                                    <i class="fas fa-print"></i> Cetak Kwitansi Resmi (Print)
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teleport Modals -->
        <Teleport to="body">
            <!-- Modal Konfirmasi Persetujuan Pembayaran -->
            <div v-if="showConfirmModal" class="modal-overlay" @click.self="showConfirmModal = false">
                <div class="modal-card" style="max-width:480px;">
                    <div class="modal-header">
                        <div style="display:flex;align-items:center;gap:0.625rem;">
                            <span style="width:36px;height:36px;border-radius:0.5rem;background:#ecfdf5;color:#059669;display:flex;align-items:center;justify-content:center;font-size:1.125rem;">
                                <i class="fas fa-check-circle"></i>
                            </span>
                            <div>
                                <h4 style="margin:0;font-size:1rem;font-weight:700;color:#0f172a;">Konfirmasi Persetujuan</h4>
                                <p style="margin:0;font-size:0.75rem;color:#64748b;">Verifikasi pembayaran mahasiswa</p>
                            </div>
                        </div>
                        <button class="modal-close-btn" @click="showConfirmModal = false">&times;</button>
                    </div>

                    <div class="modal-body" style="display:grid;gap:0.875rem;">
                        <p style="margin:0;color:#334155;font-size:0.875rem;line-height:1.5;">
                            Apakah Anda yakin ingin mengkonfirmasi pembayaran sebesar <strong>{{ formatRupiah(pembayaran.jumlah_bayar) }}</strong> untuk mahasiswa <strong>{{ pembayaran.tagihan?.mahasiswa?.nama_lengkap }}</strong> ({{ pembayaran.tagihan?.mahasiswa?.nim }})?
                        </p>
                        <div class="solid-notice solid-notice-success">
                            <i class="fas fa-info-circle"></i>
                            <div>Status tagihan UKT mahasiswa akan otomatis diperbarui menjadi <strong>LUNAS</strong>.</div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="solid-btn btn-white-border" @click="showConfirmModal = false">Batal</button>
                        <button
                            type="button"
                            class="solid-btn btn-success-solid"
                            :disabled="isProcessing"
                            @click="executeConfirm"
                        >
                            <i class="fas" :class="isProcessing ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                            {{ isProcessing ? 'Memproses...' : 'Ya, Konfirmasi Lunas' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Tolak Pembayaran -->
            <div v-if="showRejectModal" class="modal-overlay" @click.self="showRejectModal = false">
                <div class="modal-card" style="max-width:480px;">
                    <div class="modal-header">
                        <div style="display:flex;align-items:center;gap:0.625rem;">
                            <span style="width:36px;height:36px;border-radius:0.5rem;background:#fee2e2;color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:1.125rem;">
                                <i class="fas fa-times-circle"></i>
                            </span>
                            <div>
                                <h4 style="margin:0;font-size:1rem;font-weight:700;color:#991b1b;">Tolak Pembayaran</h4>
                                <p style="margin:0;font-size:0.75rem;color:#64748b;">Kirim alasan penolakan ke mahasiswa</p>
                            </div>
                        </div>
                        <button class="modal-close-btn" @click="showRejectModal = false">&times;</button>
                    </div>

                    <div class="modal-body" style="display:grid;gap:0.875rem;">
                        <div class="solid-notice solid-notice-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                Alasan penolakan wajib diisi. Catatan ini akan ditampilkan langsung pada dashboard portal mahasiswa terkait.
                            </div>
                        </div>

                        <div>
                            <label class="modal-label">Alasan Penolakan <span style="color:#dc2626;">*</span></label>
                            <textarea
                                v-model="rejectReason"
                                rows="3"
                                class="modal-input"
                                placeholder="Contoh: Bukti transfer tidak jelas / nominal tidak sesuai / dana belum masuk mutasi..."
                                required
                            ></textarea>
                            <div v-if="rejectError" style="color:#dc2626;font-size:0.75rem;margin-top:0.375rem;">
                                {{ rejectError }}
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="solid-btn btn-white-border" @click="showRejectModal = false">Batal</button>
                        <button
                            type="button"
                            class="solid-btn btn-danger-solid"
                            :disabled="!rejectReason.trim() || isProcessing"
                            @click="executeReject"
                        >
                            <i class="fas" :class="isProcessing ? 'fa-spinner fa-spin' : 'fa-times'"></i>
                            {{ isProcessing ? 'Memproses...' : 'Tolak Pembayaran' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Lightbox Bukti Transfer Penuh -->
            <div v-if="showImageLightbox" class="modal-overlay" @click="showImageLightbox = false" style="background:rgba(15, 23, 42, 0.85);z-index:200;">
                <div class="modal-card" style="max-width:720px;background:transparent;box-shadow:none;border:none;" @click.stop>
                    <div style="background:#ffffff;border-radius:0.75rem;overflow:hidden;box-shadow:0 25px 50px rgba(0,0,0,0.5);">
                        <div class="modal-header">
                            <div style="font-weight:700;color:#0f172a;font-size:0.9375rem;">
                                Pratinjau Bukti Pembayaran - {{ pembayaran.tagihan?.mahasiswa?.nama_lengkap }}
                            </div>
                            <button class="modal-close-btn" @click="showImageLightbox = false">&times;</button>
                        </div>
                        <div style="max-height:75vh;overflow:auto;padding:1rem;background:#0f172a;display:flex;align-items:center;justify-content:center;">
                            <img
                                :src="'/storage/' + pembayaran.bukti_pembayaran"
                                alt="Bukti Pembayaran Penuh"
                                style="max-width:100%;max-height:70vh;object-fit:contain;border-radius:0.375rem;"
                            />
                        </div>
                        <div class="modal-footer" style="justify-content:space-between;">
                            <a :href="'/storage/' + pembayaran.bukti_pembayaran" target="_blank" class="solid-btn btn-white-border">
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

/* Hero Payment Card */
.hero-payment-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.5rem;
}
.hero-payment-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 1rem;
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
.hero-method-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #f1f5f9;
    color: #334155;
    padding: 0.4rem 0.75rem;
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.8125rem;
}

/* VA Container */
.va-display-container {
    margin-top: 1.25rem;
    padding: 1rem 1.25rem;
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 0.625rem;
}
.va-label-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}
.va-title {
    font-size: 0.8125rem;
    font-weight: 700;
    color: #475569;
}
.va-deadline {
    font-size: 0.75rem;
    color: #64748b;
}
.va-number-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}
.va-digits {
    font-family: monospace;
    font-size: 1.375rem;
    font-weight: 800;
    color: #2563eb;
    letter-spacing: 0.05em;
}
.btn-copy-va-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.75rem;
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

/* Two-Column Info Grid */
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

/* Beasiswa Highlight */
.beasiswa-card-highlight {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 0.625rem;
    padding: 1rem;
}
.beasiswa-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.375rem;
}
.beasiswa-tag {
    font-size: 0.75rem;
    font-weight: 700;
    color: #166534;
    text-transform: uppercase;
}
.beasiswa-potongan {
    font-size: 0.8125rem;
    font-weight: 700;
    color: #15803d;
}
.beasiswa-title {
    font-weight: 700;
    color: #0f172a;
    font-size: 0.9375rem;
}
.beasiswa-meta {
    font-size: 0.75rem;
    color: #334155;
    margin-top: 0.25rem;
}

/* Receipt Preview on Right Side */
.receipt-large-container {
    position: relative;
    border-radius: 0.5rem;
    overflow: hidden;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    cursor: pointer;
    max-height: 280px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.receipt-large-img {
    max-width: 100%;
    max-height: 280px;
    object-fit: contain;
    display: block;
}
.receipt-zoom-overlay {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    color: #ffffff;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    font-weight: 600;
    opacity: 0;
    transition: opacity 0.15s;
}
.receipt-large-container:hover .receipt-zoom-overlay {
    opacity: 1;
}

.empty-receipt-box {
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
.badge-solid-warning {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
}
.badge-solid-danger {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}
.badge-solid-info {
    background: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
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
.btn-sm {
    padding: 0.375rem 0.625rem;
    font-size: 0.75rem;
}
.btn-indigo-solid {
    background: #4f46e5;
    color: #ffffff;
}
.btn-success-solid {
    background: #16a34a;
    color: #ffffff;
}
.btn-danger-solid {
    background: #dc2626;
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
.modal-body {
    padding: 1.25rem;
}
.modal-footer {
    padding: 0.875rem 1.25rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    background: #f8fafc;
}
.modal-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 0.375rem;
}
.modal-input {
    width: 100%;
    padding: 0.5625rem 0.875rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    outline: none;
    background: #ffffff;
    color: #1e293b;
    box-sizing: border-box;
}
.modal-input:focus {
    border-color: #4f46e5;
}
.solid-notice {
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    line-height: 1.5;
    display: flex;
    align-items: flex-start;
    gap: 0.625rem;
}
.solid-notice-success {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
}
.solid-notice-danger {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

/* Solid Table */
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
</style>
