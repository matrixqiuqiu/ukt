<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { formatRupiah, formatDate } from '@/utils';
import { ref } from 'vue';

const props = defineProps({
    pembayarans: Object,
    expiredPembayarans: Array,
    expiredCount: Number,
    stats: Object,
    filters: Object,
});

const activeTab = ref('pending'); // 'pending' | 'expired'
const search = ref(props.filters?.search || '');
const searchExpired = ref(props.filters?.search_expired || '');

const activeModal = ref(null);
const rejectNote = ref('');
const approveNote = ref('');
const processingId = ref(null);
const previewImage = ref(null);
const copiedVa = ref(null);

const doSearchPending = () => {
    router.get(route('admin.verifikasi.index'), {
        search: search.value,
        search_expired: searchExpired.value,
    }, { preserveState: true, replace: true });
};

const clearSearchPending = () => {
    search.value = '';
    router.get(route('admin.verifikasi.index'), {
        search_expired: searchExpired.value,
    }, { preserveState: true, replace: true });
};

const doSearchExpired = () => {
    router.get(route('admin.verifikasi.index'), {
        search: search.value,
        search_expired: searchExpired.value,
    }, { preserveState: true, replace: true });
};

const clearSearchExpired = () => {
    searchExpired.value = '';
    router.get(route('admin.verifikasi.index'), {
        search: search.value,
    }, { preserveState: true, replace: true });
};

const openApprove = (p) => {
    approveNote.value = '';
    activeModal.value = { type: 'approve', data: p };
};

const openReject = (p) => {
    rejectNote.value = '';
    activeModal.value = { type: 'reject', data: p };
};

const openImage = (url, title) => {
    previewImage.value = { url, title };
};

const closeModal = () => {
    activeModal.value = null;
};

const closeImage = () => {
    previewImage.value = null;
};

const copyVa = (va) => {
    if (!va) return;
    navigator.clipboard.writeText(va);
    copiedVa.value = va;
    setTimeout(() => {
        if (copiedVa.value === va) copiedVa.value = null;
    }, 2000);
};

const submitApprove = () => {
    if (!activeModal.value) return;
    const p = activeModal.value.data;
    processingId.value = p.id;
    router.post(route('admin.pembayaran.verifikasi', p.id), {
        catatan_admin: approveNote.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            processingId.value = null;
            closeModal();
        },
    });
};

const submitReject = () => {
    if (!activeModal.value) return;
    const p = activeModal.value.data;
    processingId.value = p.id;
    router.post(route('admin.pembayaran.tolak', p.id), {
        catatan_admin: rejectNote.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            processingId.value = null;
            closeModal();
        },
    });
};
</script>

<template>
    <Head title="Verifikasi Pembayaran UKT" />
    <AuthenticatedLayout>
        <template #header>
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div>
                    <h2 class="page-heading" style="font-size:1.375rem;font-weight:700;color:#0f172a;margin-bottom:0.25rem;">
                        Verifikasi Pembayaran UKT
                    </h2>
                    <p style="font-size:0.875rem;color:#64748b;margin:0;">
                        Pengecekan bukti transfer manual mahasiswa dan riwayat Virtual Account kedaluwarsa
                    </p>
                </div>
                <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                    <Link :href="route('admin.pembayaran.index')" class="solid-btn btn-white-border" title="Buka Semua Riwayat Pembayaran">
                        <i class="fas fa-history"></i> Semua Transaksi
                    </Link>
                    <Link :href="route('admin.verifikasi.ringkasan')" class="solid-btn btn-white-border" title="Lihat Rekapitulasi Pembayaran">
                        <i class="fas fa-chart-pie"></i> Rekapitulasi
                    </Link>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">
                <!-- 3 Top Statistic Cards -->
                <div class="stats-overview-grid">
                    <div class="metric-card">
                        <div class="metric-icon-box bg-amber-light text-amber-dark">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="metric-info">
                            <div class="metric-label">Antrean Verifikasi Manual</div>
                            <div class="metric-value">{{ stats?.pendingCount || pembayarans.total || 0 }} <span class="metric-unit">transaksi</span></div>
                            <div class="metric-desc">Menunggu persetujuan admin</div>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-icon-box bg-blue-light text-blue-dark">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="metric-info">
                            <div class="metric-label">Total Nominal Tertahan</div>
                            <div class="metric-value">{{ formatRupiah(stats?.pendingNominal || 0) }}</div>
                            <div class="metric-desc">Dana siap diverifikasi ke sistem</div>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-icon-box bg-red-light text-red-dark">
                            <i class="fas fa-hourglass-end"></i>
                        </div>
                        <div class="metric-info">
                            <div class="metric-label">VA Kedaluwarsa (Expired)</div>
                            <div class="metric-value">{{ stats?.expiredCount || expiredCount || 0 }} <span class="metric-unit">VA</span></div>
                            <div class="metric-desc">Melewati batas waktu bayar</div>
                        </div>
                    </div>
                </div>

                <!-- Segmented Tabs Navigation -->
                <div class="segmented-tabs-wrap">
                    <button
                        type="button"
                        class="tab-pill-btn"
                        :class="{ active: activeTab === 'pending' }"
                        @click="activeTab = 'pending'"
                    >
                        <i class="fas fa-clock"></i>
                        <span>Antrean Verifikasi Manual</span>
                        <span class="tab-badge badge-amber">{{ pembayarans.total || pembayarans.data.length }}</span>
                    </button>

                    <button
                        type="button"
                        class="tab-pill-btn"
                        :class="{ active: activeTab === 'expired' }"
                        @click="activeTab = 'expired'"
                    >
                        <i class="fas fa-hourglass-end"></i>
                        <span>Riwayat VA Kedaluwarsa (Expired)</span>
                        <span class="tab-badge badge-red">{{ expiredCount || expiredPembayarans.length }}</span>
                    </button>
                </div>

                <!-- ================= TAB 1: ANTREAN VERIFIKASI ================= -->
                <div v-show="activeTab === 'pending'">
                    <!-- Filter Toolbar -->
                    <div class="filter-card">
                        <div style="font-size:0.875rem;font-weight:700;color:#1e293b;margin-bottom:0.75rem;display:flex;align-items:center;justify-content:space-between;">
                            <span style="display:flex;align-items:center;gap:0.5rem;">
                                <i class="fas fa-filter" style="color:#4f46e5;"></i> Filter Antrean Verifikasi
                            </span>
                            <span style="font-size:0.8125rem;font-weight:500;color:#64748b;">
                                Total: <strong style="color:#0f172a;">{{ pembayarans.total || pembayarans.data.length }}</strong> pembayaran tertunda
                            </span>
                        </div>

                        <div class="filter-grid-simple">
                            <div class="search-box-wrap">
                                <i class="fas fa-search search-icon"></i>
                                <input
                                    type="search"
                                    class="filter-input search-input"
                                    v-model="search"
                                    placeholder="Cari NIM, nama mahasiswa, nama pengirim, atau ID transaksi..."
                                    @keyup.enter="doSearchPending"
                                />
                            </div>
                            <div class="filter-actions">
                                <button @click="doSearchPending" class="btn-solid-primary" style="padding:0.5625rem 1rem;">
                                    <i class="fas fa-search"></i> Terapkan
                                </button>
                                <button v-if="search" @click="clearSearchPending" class="btn-solid-secondary" style="padding:0.5625rem 0.875rem;" title="Reset filter">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Table Card -->
                    <div class="data-card">
                        <div v-if="pembayarans.data && pembayarans.data.length > 0">
                            <div class="table-responsive">
                                <table class="solid-table">
                                    <thead>
                                        <tr>
                                            <th style="width:40px;text-align:center;">No</th>
                                            <th>Waktu & ID</th>
                                            <th>Mahasiswa</th>
                                            <th>Semester & Periode</th>
                                            <th>Metode & Pengirim</th>
                                            <th>Jumlah Bayar</th>
                                            <th style="text-align:center;">Bukti Struk</th>
                                            <th style="width:160px;text-align:center;">Aksi Verifikasi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(p, i) in pembayarans.data" :key="p.id">
                                            <td style="text-align:center;color:#64748b;font-size:0.8125rem;">
                                                {{ (pembayarans.from || 1) + i }}
                                            </td>
                                            <td>
                                                <div style="font-weight:700;color:#0f172a;">{{ formatDate(p.created_at) }}</div>
                                                <div style="font-size:0.75rem;color:#64748b;font-family:monospace;">ID: #{{ p.id }}</div>
                                            </td>
                                            <td>
                                                <div style="font-weight:700;color:#0f172a;font-size:0.875rem;">
                                                    {{ p.tagihan?.mahasiswa?.nama_lengkap }}
                                                </div>
                                                <div style="font-size:0.75rem;font-family:monospace;color:#64748b;background:#f1f5f9;display:inline-block;padding:0.1rem 0.35rem;border-radius:0.25rem;margin-top:0.125rem;">
                                                    {{ p.tagihan?.mahasiswa?.nim }}
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-weight:600;color:#1e293b;font-size:0.8125rem;">
                                                    Semester {{ p.tagihan?.semester }}
                                                </div>
                                                <div style="font-size:0.75rem;color:#64748b;">
                                                    {{ p.tagihan?.tahun_akademik }}
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-weight:600;color:#0f172a;font-size:0.8125rem;">
                                                    {{ p.metode_pembayaran?.nama_metode || 'Transfer Bank Manual' }}
                                                </div>
                                                <div style="font-size:0.75rem;color:#64748b;">
                                                    A.n: <em>{{ p.nama_pengirim || '-' }}</em>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-weight:800;color:#0f172a;font-size:0.9375rem;">
                                                    {{ formatRupiah(p.jumlah_bayar) }}
                                                </div>
                                            </td>
                                            <td style="text-align:center;">
                                                <button
                                                    v-if="p.bukti_pembayaran"
                                                    type="button"
                                                    class="btn-receipt-thumb"
                                                    @click="openImage(`/storage/${p.bukti_pembayaran}`, `Bukti Bayar - ${p.tagihan?.mahasiswa?.nama_lengkap} (${p.tagihan?.mahasiswa?.nim})`)"
                                                    title="Klik untuk melihat bukti ukuran penuh"
                                                >
                                                    <img :src="`/storage/${p.bukti_pembayaran}`" alt="Struk" class="receipt-thumb-img" />
                                                    <span class="receipt-thumb-hover"><i class="fas fa-search-plus"></i></span>
                                                </button>
                                                <span v-else style="font-size:0.75rem;color:#94a3b8;font-style:italic;">
                                                    Tanpa Struk
                                                </span>
                                            </td>
                                            <td style="text-align:center;">
                                                <div style="display:flex;gap:0.375rem;justify-content:center;">
                                                    <Link
                                                        :href="route('admin.pembayaran.show', p.id)"
                                                        class="action-btn-custom btn-view"
                                                        title="Lihat Detail Transaksi"
                                                    >
                                                        <i class="fas fa-eye"></i>
                                                    </Link>
                                                    <button
                                                        type="button"
                                                        class="btn-solid-success btn-xs"
                                                        @click="openApprove(p)"
                                                        title="Konfirmasi Lunas Pembayaran"
                                                    >
                                                        <i class="fas fa-check"></i> Setujui
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="btn-solid-danger btn-xs"
                                                        @click="openReject(p)"
                                                        title="Tolak Pembayaran"
                                                    >
                                                        <i class="fas fa-times"></i> Tolak
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination Footer -->
                            <div class="pagination-footer">
                                <span style="font-size:0.8125rem;color:#64748b;">
                                    Menampilkan <strong style="color:#0f172a;">{{ pembayarans.from || 1 }}-{{ pembayarans.to || pembayarans.total }}</strong> dari <strong style="color:#0f172a;">{{ pembayarans.total }}</strong> antrean
                                </span>
                                <div class="pagination-btns">
                                    <template v-for="link in pembayarans.links" :key="link.label">
                                        <span v-if="!link.url" class="p-btn p-disabled" v-html="link.label"></span>
                                        <Link v-else :href="link.url" class="p-btn" :class="{ 'p-active': link.active }" v-html="link.label" preserve-state />
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div v-else style="text-align:center;padding:4rem 2rem;color:#64748b;">
                            <div style="width:56px;height:56px;border-radius:50%;background:#ecfdf5;color:#059669;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.75rem;">
                                <i class="fas fa-check"></i>
                            </div>
                            <h4 style="font-size:1rem;font-weight:700;color:#0f172a;margin:0 0 0.25rem;">Semua Bersih & Terverifikasi</h4>
                            <p style="font-size:0.8125rem;color:#64748b;margin:0;">Tidak ada antrean pembayaran manual yang perlu diverifikasi saat ini.</p>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB 2: VA EXPIRED ================= -->
                <div v-show="activeTab === 'expired'">
                    <!-- Info Notice -->
                    <div class="solid-notice solid-notice-info" style="margin-bottom:1.25rem;">
                        <i class="fas fa-info-circle" style="font-size:1.125rem;"></i>
                        <div>
                            Transaksi di bawah ini otomatis berstatus <strong>Expired</strong> karena mahasiswa tidak menyelesaikan pembayaran sebelum batas waktu VA habis.
                            Tagihan UKT mahasiswa tetap aman dan belum lunas — mahasiswa dapat men-generate Virtual Account baru sewaktu-waktu di portal mereka.
                        </div>
                    </div>

                    <!-- Filter Toolbar Expired -->
                    <div class="filter-card">
                        <div style="font-size:0.875rem;font-weight:700;color:#1e293b;margin-bottom:0.75rem;display:flex;align-items:center;justify-content:space-between;">
                            <span style="display:flex;align-items:center;gap:0.5rem;">
                                <i class="fas fa-filter" style="color:#dc2626;"></i> Filter Riwayat VA Expired
                            </span>
                            <span style="font-size:0.8125rem;font-weight:500;color:#64748b;">
                                Total: <strong style="color:#0f172a;">{{ expiredCount || expiredPembayarans.length }}</strong> transaksi kedaluwarsa
                            </span>
                        </div>

                        <div class="filter-grid-simple">
                            <div class="search-box-wrap">
                                <i class="fas fa-search search-icon"></i>
                                <input
                                    type="search"
                                    class="filter-input search-input"
                                    v-model="searchExpired"
                                    placeholder="Cari NIM, nama mahasiswa, atau nomor VA..."
                                    @keyup.enter="doSearchExpired"
                                />
                            </div>
                            <div class="filter-actions">
                                <button @click="doSearchExpired" class="btn-solid-primary" style="padding:0.5625rem 1rem;">
                                    <i class="fas fa-search"></i> Terapkan
                                </button>
                                <button v-if="searchExpired" @click="clearSearchExpired" class="btn-solid-secondary" style="padding:0.5625rem 0.875rem;" title="Reset filter">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Table Card Expired -->
                    <div class="data-card">
                        <div v-if="expiredPembayarans && expiredPembayarans.length > 0">
                            <div class="table-responsive">
                                <table class="solid-table">
                                    <thead>
                                        <tr>
                                            <th style="width:40px;text-align:center;">No</th>
                                            <th>Waktu Expired</th>
                                            <th>Mahasiswa</th>
                                            <th>Semester & Periode</th>
                                            <th>Jumlah Tagihan</th>
                                            <th>Nomor Virtual Account</th>
                                            <th style="text-align:center;">Status</th>
                                            <th style="width:100px;text-align:center;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(p, i) in expiredPembayarans" :key="p.id">
                                            <td style="text-align:center;color:#64748b;font-size:0.8125rem;">
                                                {{ i + 1 }}
                                            </td>
                                            <td>
                                                <div style="font-weight:700;color:#0f172a;">{{ formatDate(p.created_at) }}</div>
                                                <div style="font-size:0.75rem;color:#64748b;">ID: #{{ p.id }}</div>
                                            </td>
                                            <td>
                                                <div style="font-weight:700;color:#0f172a;font-size:0.875rem;">
                                                    {{ p.tagihan?.mahasiswa?.nama_lengkap }}
                                                </div>
                                                <div style="font-size:0.75rem;font-family:monospace;color:#64748b;background:#f1f5f9;display:inline-block;padding:0.1rem 0.35rem;border-radius:0.25rem;margin-top:0.125rem;">
                                                    {{ p.tagihan?.mahasiswa?.nim }}
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-weight:600;color:#1e293b;font-size:0.8125rem;">
                                                    Semester {{ p.tagihan?.semester }}
                                                </div>
                                                <div style="font-size:0.75rem;color:#64748b;">
                                                    {{ p.tagihan?.tahun_akademik }}
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-weight:800;color:#0f172a;font-size:0.9375rem;">
                                                    {{ formatRupiah(p.jumlah_bayar) }}
                                                </div>
                                            </td>
                                            <td>
                                                <div style="display:flex;align-items:center;gap:0.375rem;">
                                                    <span style="font-family:monospace;font-weight:700;font-size:0.8125rem;color:#0f172a;background:#f1f5f9;padding:0.2rem 0.45rem;border-radius:0.375rem;">
                                                        {{ p.va_number || '-' }}
                                                    </span>
                                                    <button
                                                        v-if="p.va_number"
                                                        type="button"
                                                        class="btn-copy-va"
                                                        @click="copyVa(p.va_number)"
                                                        :title="copiedVa === p.va_number ? 'Tersalin!' : 'Salin Nomor VA'"
                                                    >
                                                        <i :class="copiedVa === p.va_number ? 'fas fa-check text-success' : 'fas fa-copy'"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td style="text-align:center;">
                                                <span class="solid-badge badge-solid-danger">
                                                    <i class="fas fa-times-circle"></i> Expired
                                                </span>
                                            </td>
                                            <td style="text-align:center;">
                                                <Link
                                                    :href="route('admin.pembayaran.show', p.id)"
                                                    class="action-btn-custom btn-view"
                                                    title="Lihat Detail Log Transaksi"
                                                >
                                                    <i class="fas fa-eye"></i>
                                                </Link>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div v-else style="text-align:center;padding:4rem 2rem;color:#64748b;">
                            <i class="fas fa-hourglass-end" style="font-size:2.5rem;color:#cbd5e1;margin-bottom:0.75rem;display:block;"></i>
                            <h4 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0 0 0.25rem;">Tidak Ada Riwayat VA Expired</h4>
                            <p style="font-size:0.8125rem;color:#64748b;margin:0;">Semua transaksi Virtual Account mahasiswa berjalan lancar atau belum ada yang kedaluwarsa.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teleport Modals -->
        <Teleport to="body">
            <!-- MODAL 1: SETUJUI PEMBAYARAN -->
            <div v-if="activeModal?.type === 'approve'" class="modal-overlay" @click.self="closeModal">
                <div class="modal-card" style="max-width:520px;">
                    <div class="modal-header">
                        <div style="display:flex;align-items:center;gap:0.625rem;">
                            <span style="width:36px;height:36px;border-radius:0.5rem;background:#ecfdf5;color:#059669;display:flex;align-items:center;justify-content:center;font-size:1.125rem;">
                                <i class="fas fa-check-circle"></i>
                            </span>
                            <div>
                                <h4 style="margin:0;font-size:1rem;font-weight:700;color:#0f172a;">Konfirmasi Persetujuan Pembayaran</h4>
                                <p style="margin:0;font-size:0.75rem;color:#64748b;">Verifikasi dana transfer masuk dan ubah status tagihan menjadi lunas</p>
                            </div>
                        </div>
                        <button class="modal-close-btn" @click="closeModal">&times;</button>
                    </div>

                    <div class="modal-body" style="display:grid;gap:0.875rem;">
                        <div class="solid-notice solid-notice-success">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                Anda akan mengkonfirmasi pembayaran UKT sebesar <strong style="font-size:0.9375rem;">{{ formatRupiah(activeModal.data.jumlah_bayar) }}</strong> untuk:
                                <div style="font-weight:700;color:#0f172a;margin-top:0.25rem;">
                                    {{ activeModal.data.tagihan?.mahasiswa?.nama_lengkap }} ({{ activeModal.data.tagihan?.mahasiswa?.nim }})
                                </div>
                            </div>
                        </div>

                        <div v-if="activeModal.data.bukti_pembayaran" class="receipt-verify-preview">
                            <div style="font-size:0.75rem;font-weight:700;color:#475569;margin-bottom:0.375rem;display:flex;justify-content:space-between;align-items:center;">
                                <span>PRATINJAU BUKTI TRANSFER:</span>
                                <a :href="`/storage/${activeModal.data.bukti_pembayaran}`" target="_blank" style="color:#4f46e5;text-decoration:none;font-size:0.75rem;">
                                    <i class="fas fa-external-link-alt"></i> Buka Asli
                                </a>
                            </div>
                            <img :src="`/storage/${activeModal.data.bukti_pembayaran}`" class="receipt-verify-img" alt="Bukti Transfer" />
                        </div>

                        <div>
                            <label class="modal-label">Catatan Admin (Opsional)</label>
                            <textarea
                                v-model="approveNote"
                                rows="2"
                                class="modal-input"
                                placeholder="Contoh: Dana telah dicek dan valid di rekening koran bank..."
                            ></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="solid-btn btn-white-border" @click="closeModal">Batal</button>
                        <button
                            type="button"
                            class="solid-btn btn-success-solid"
                            :disabled="processingId === activeModal.data.id"
                            @click="submitApprove"
                        >
                            <i class="fas" :class="processingId === activeModal.data.id ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                            {{ processingId === activeModal.data.id ? 'Memproses...' : 'Ya, Konfirmasi Lunas' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- MODAL 2: TOLAK PEMBAYARAN -->
            <div v-if="activeModal?.type === 'reject'" class="modal-overlay" @click.self="closeModal">
                <div class="modal-card" style="max-width:480px;">
                    <div class="modal-header">
                        <div style="display:flex;align-items:center;gap:0.625rem;">
                            <span style="width:36px;height:36px;border-radius:0.5rem;background:#fee2e2;color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:1.125rem;">
                                <i class="fas fa-times-circle"></i>
                            </span>
                            <div>
                                <h4 style="margin:0;font-size:1rem;font-weight:700;color:#991b1b;">Tolak Pembayaran</h4>
                                <p style="margin:0;font-size:0.75rem;color:#64748b;">Kirim alasan penolakan agar mahasiswa dapat mengunggah bukti yang benar</p>
                            </div>
                        </div>
                        <button class="modal-close-btn" @click="closeModal">&times;</button>
                    </div>

                    <div class="modal-body" style="display:grid;gap:0.875rem;">
                        <div class="solid-notice solid-notice-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                Alasan penolakan wajib diisi. Catatan ini akan ditampilkan secara langsung di dashboard portal mahasiswa terkait.
                            </div>
                        </div>

                        <div>
                            <label class="modal-label">Alasan Penolakan <span style="color:#dc2626;">*</span></label>
                            <textarea
                                v-model="rejectNote"
                                rows="3"
                                class="modal-input"
                                placeholder="Contoh: Bukti transfer buram/tidak terbaca, nominal kurang, atau dana belum masuk rekening..."
                                required
                            ></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="solid-btn btn-white-border" @click="closeModal">Batal</button>
                        <button
                            type="button"
                            class="solid-btn btn-danger-solid"
                            :disabled="!rejectNote.trim() || processingId === activeModal.data.id"
                            @click="submitReject"
                        >
                            <i class="fas" :class="processingId === activeModal.data.id ? 'fa-spinner fa-spin' : 'fa-times'"></i>
                            {{ processingId === activeModal.data.id ? 'Memproses...' : 'Tolak Pembayaran' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- MODAL 3: LIGHTBOX PREVIEW BUKTI STRUK -->
            <div v-if="previewImage" class="modal-overlay lightbox-overlay" @click="closeImage">
                <div class="modal-card lightbox-card" @click.stop>
                    <div class="modal-header">
                        <div style="font-weight:700;color:#0f172a;font-size:0.9375rem;">{{ previewImage.title }}</div>
                        <button class="modal-close-btn" @click="closeImage">&times;</button>
                    </div>
                    <div class="lightbox-img-pane">
                        <img :src="previewImage.url" alt="Bukti Transfer" class="lightbox-full-img" />
                    </div>
                    <div class="modal-footer" style="justify-content:space-between;">
                        <a :href="previewImage.url" target="_blank" class="solid-btn btn-white-border">
                            <i class="fas fa-external-link-alt"></i> Buka Resolusi Penuh
                        </a>
                        <button type="button" class="solid-btn btn-indigo-solid" @click="closeImage">Tutup</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Top Metric Cards */
.stats-overview-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}
@media (max-width: 900px) {
    .stats-overview-grid {
        grid-template-columns: 1fr;
    }
}
.metric-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.125rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
}
.metric-icon-box {
    width: 48px;
    height: 48px;
    border-radius: 0.625rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.375rem;
    flex-shrink: 0;
}
.bg-amber-light { background: #fffbeb; }
.text-amber-dark { color: #d97706; }
.bg-blue-light { background: #eff6ff; }
.text-blue-dark { color: #2563eb; }
.bg-red-light { background: #fef2f2; }
.text-red-dark { color: #dc2626; }

.metric-info {
    min-width: 0;
}
.metric-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin-bottom: 0.125rem;
}
.metric-value {
    font-size: 1.25rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
}
.metric-unit {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #64748b;
}
.metric-desc {
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 0.125rem;
}

/* Segmented Tabs */
.segmented-tabs-wrap {
    display: flex;
    gap: 0.5rem;
    background: #e2e8f0;
    padding: 0.375rem;
    border-radius: 0.625rem;
    margin-bottom: 1.25rem;
    width: fit-content;
    max-width: 100%;
    overflow-x: auto;
}
.tab-pill-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    border: none;
    background: transparent;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s;
    white-space: nowrap;
}
.tab-pill-btn:hover {
    color: #0f172a;
}
.tab-pill-btn.active {
    background: #ffffff;
    color: #0f172a;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}
.tab-badge {
    padding: 0.125rem 0.45rem;
    border-radius: 1rem;
    font-size: 0.6875rem;
    font-weight: 700;
}
.badge-amber { background: #fef3c7; color: #b45309; }
.badge-red { background: #fee2e2; color: #991b1b; }

/* Filters */
.filter-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1rem 1.25rem;
    margin-bottom: 1.25rem;
}
.filter-grid-simple {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0.75rem;
    align-items: center;
}
@media (max-width: 640px) {
    .filter-grid-simple {
        grid-template-columns: 1fr;
    }
}
.search-box-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.search-icon {
    position: absolute;
    left: 0.875rem;
    color: #94a3b8;
    font-size: 0.8125rem;
}
.search-input {
    padding-left: 2.25rem !important;
}
.filter-input {
    width: 100%;
    padding: 0.5625rem 0.875rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    color: #1e293b;
    background: #ffffff;
    outline: none;
    transition: border-color 0.15s;
    box-sizing: border-box;
}
.filter-input:focus {
    border-color: #4f46e5;
}
.filter-actions {
    display: flex;
    gap: 0.5rem;
}

/* Data Table */
.data-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    overflow: hidden;
}
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
    user-select: none;
}
.solid-table td {
    padding: 0.875rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.solid-table tbody tr:hover {
    background: #f8fafc;
}

/* Receipt Thumbnail */
.btn-receipt-thumb {
    position: relative;
    width: 44px;
    height: 44px;
    border-radius: 0.375rem;
    border: 1px solid #cbd5e1;
    padding: 0;
    overflow: hidden;
    cursor: pointer;
    background: #f8fafc;
    display: inline-block;
}
.receipt-thumb-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.receipt-thumb-hover {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.4);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    opacity: 0;
    transition: opacity 0.15s;
}
.btn-receipt-thumb:hover .receipt-thumb-hover {
    opacity: 1;
}

/* Badges */
.solid-badge {
    display: inline-block;
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

/* Buttons */
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
.btn-solid-primary {
    background: #4f46e5;
    color: #ffffff;
    border: none;
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.8125rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
}
.btn-solid-secondary {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.8125rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
}
.btn-solid-success {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
    border-radius: 0.375rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}
.btn-solid-success:hover {
    background: #d1fae5;
}
.btn-solid-danger {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
    border-radius: 0.375rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}
.btn-solid-danger:hover {
    background: #fee2e2;
}
.btn-xs {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

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
.btn-copy-va {
    border: none;
    background: none;
    cursor: pointer;
    font-size: 0.8125rem;
    color: #64748b;
    padding: 0.15rem;
}
.btn-copy-va:hover {
    color: #0f172a;
}

/* Pagination */
.pagination-footer {
    padding: 0.875rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #f1f5f9;
    flex-wrap: wrap;
    gap: 0.75rem;
}
.pagination-btns {
    display: flex;
    gap: 0.25rem;
}
.p-btn {
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.8125rem;
    color: #334155;
    text-decoration: none;
    border: 1px solid #e2e8f0;
    background: #ffffff;
}
.p-btn:hover {
    background: #f8fafc;
}
.p-active {
    background: #4f46e5 !important;
    color: #ffffff !important;
    border-color: #4f46e5 !important;
    font-weight: 600;
}
.p-disabled {
    opacity: 0.4;
    cursor: not-allowed;
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
.solid-notice-info {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1e40af;
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

/* Lightbox Modal */
.lightbox-card {
    max-width: 680px;
}
.lightbox-img-pane {
    max-height: 70vh;
    overflow: auto;
    padding: 1rem;
    background: #0f172a;
    display: flex;
    align-items: center;
    justify-content: center;
}
.lightbox-full-img {
    max-width: 100%;
    max-height: 65vh;
    object-fit: contain;
    border-radius: 0.375rem;
}
.receipt-verify-preview {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 0.75rem;
}
.receipt-verify-img {
    max-height: 140px;
    max-width: 100%;
    object-fit: contain;
    border-radius: 0.375rem;
    border: 1px solid #cbd5e1;
    display: block;
    margin: 0 auto;
}
</style>
