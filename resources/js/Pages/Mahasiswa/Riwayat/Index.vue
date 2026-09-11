<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { formatRupiah, formatDate, formatDateTime } from '@/utils';

const props = defineProps({
    riwayat: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_transaksi: 0,
            total_lunas: 0,
            total_nominal_lunas: 0,
            total_pending: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({
            status: 'semua',
            search: '',
        }),
    },
});

const activeStatusFilter = ref(props.filters.status || 'semua');
const searchQuery = ref(props.filters.search || '');
const selectedPayment = ref(null);
const showQuickModal = ref(false);
const copiedId = ref(null);

let searchTimeout = null;

const applyFilters = () => {
    router.get(
        route('mahasiswa.riwayat.index'),
        {
            status: activeStatusFilter.value,
            search: searchQuery.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const setFilter = (status) => {
    activeStatusFilter.value = status;
    applyFilters();
};

watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});

const clearSearch = () => {
    searchQuery.value = '';
    applyFilters();
};

const copyText = (text, id) => {
    if (!text) return;
    navigator.clipboard.writeText(text);
    copiedId.value = id;
    setTimeout(() => {
        if (copiedId.value === id) {
            copiedId.value = null;
        }
    }, 2000);
};

const openQuickView = (pembayaran) => {
    selectedPayment.value = pembayaran;
    showQuickModal.value = true;
};

const closeQuickView = () => {
    showQuickModal.value = false;
    selectedPayment.value = null;
};

const isVaExpired = (p) => {
    if (p.status !== 'pending') return false;
    const ts = p.va_expired_at ? new Date(p.va_expired_at).getTime() : NaN;
    return !isNaN(ts) && ts <= Date.now();
};

const statusInfo = (p) => {
    if (p.status === 'dikonfirmasi') {
        return {
            label: 'Lunas Terverifikasi',
            shortLabel: 'Lunas',
            badgeClass: 'badge-solid-success',
            icon: 'fas fa-check-circle',
            dotColor: '#10b981',
            desc: 'Dana telah diterima & tervalidasi di sistem.',
        };
    }
    if (p.status === 'ditolak') {
        return {
            label: 'Pembayaran Ditolak',
            shortLabel: 'Ditolak',
            badgeClass: 'badge-solid-danger',
            icon: 'fas fa-times-circle',
            dotColor: '#ef4444',
            desc: 'Verifikasi tidak valid. Hubungi bagian keuangan.',
        };
    }
    if (p.status === 'expired' || isVaExpired(p)) {
        return {
            label: 'VA Kedaluwarsa',
            shortLabel: 'Expired',
            badgeClass: 'badge-solid-danger',
            icon: 'fas fa-hourglass-end',
            dotColor: '#ef4444',
            desc: 'Batas waktu bayar lewat. Buat transaksi baru.',
        };
    }
    return {
        label: 'Menunggu Pembayaran',
        shortLabel: 'Menunggu',
        badgeClass: 'badge-solid-warning',
        icon: 'fas fa-clock',
        dotColor: '#f59e0b',
        desc: 'Menunggu transfer sebelum batas waktu VA.',
    };
};

const formatFullDateTime = (dateStr) => {
    if (!dateStr) return '-';
    return formatDateTime(dateStr);
};

const displayNominal = (p) => {
    if (p.beasiswa && Number(p.jumlah_bayar) === 0 && Number(p.beasiswa.diskon) > 0) {
        return Number(p.beasiswa.diskon);
    }
    return p.jumlah_bayar;
};
</script>

<template>
    <Head title="Riwayat Transaksi Pembayaran" />

    <AuthenticatedLayout>
        <template #header>
            <div class="riwayat-page-header">
                <div>
                    <h2 class="riwayat-header-title">
                        Riwayat Transaksi Pembayaran
                    </h2>
                    <p class="riwayat-header-subtitle">
                        Pantau arsip pembayaran UKT, status verifikasi bank, dan akses unduh invoice elektronik.
                    </p>
                </div>

                <div class="header-action-btns">
                    <Link
                        :href="route('mahasiswa.tagihan.index')"
                        class="solid-btn btn-white-border"
                        title="Periksa Tagihan Semester Aktif"
                    >
                        <i class="fas fa-file-invoice-dollar text-indigo"></i> Tagihan Kuliah
                    </Link>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">
                <!-- MAIN CARD: DATA TABLE & FILTER -->
                <div class="main-card">
                    <!-- Card Top Controls -->
                    <div class="card-controls-bar">
                        <!-- Status Filter Tabs -->
                        <div class="filter-tab-pills">
                            <button
                                type="button"
                                class="tab-pill"
                                :class="{ active: activeStatusFilter === 'semua' }"
                                @click="setFilter('semua')"
                            >
                                <i class="fas fa-list-ul"></i> Semua
                                <span class="tab-pill-count">{{ stats.total_transaksi }}</span>
                            </button>
                            <button
                                type="button"
                                class="tab-pill"
                                :class="{ active: activeStatusFilter === 'lunas' }"
                                @click="setFilter('lunas')"
                            >
                                <i class="fas fa-check-circle text-emerald"></i> Lunas
                                <span class="tab-pill-count count-emerald">{{ stats.total_lunas }}</span>
                            </button>
                            <button
                                type="button"
                                class="tab-pill"
                                :class="{ active: activeStatusFilter === 'pending' }"
                                @click="setFilter('pending')"
                            >
                                <i class="fas fa-clock text-amber"></i> Menunggu
                                <span class="tab-pill-count count-amber">{{ stats.total_pending }}</span>
                            </button>
                            <button
                                type="button"
                                class="tab-pill"
                                :class="{ active: activeStatusFilter === 'ditolak' }"
                                @click="setFilter('ditolak')"
                            >
                                <i class="fas fa-times-circle text-danger"></i> Ditolak / Expired
                            </button>
                        </div>

                        <!-- Search Bar Input -->
                        <div class="search-input-wrap">
                            <i class="fas fa-search search-icon"></i>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari No. VA, Semester, Bank..."
                                class="search-field"
                            />
                            <button
                                v-if="searchQuery"
                                type="button"
                                class="search-clear-btn"
                                title="Reset Pencarian"
                                @click="clearSearch"
                            >
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Table & Cards Body -->
                    <div class="table-container">
                        <!-- Empty State -->
                        <div v-if="!riwayat.data || riwayat.data.length === 0" class="empty-state-box">
                            <div class="empty-icon-circle">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <h4 class="empty-state-title">Tidak Ada Riwayat Transaksi</h4>
                            <p class="empty-state-text">
                                {{ searchQuery ? 'Tidak ada transaksi yang cocok dengan kata kunci pencarian Anda.' : 'Belum ada catatan aktivitas transaksi pembayaran UKT yang tersimpan.' }}
                            </p>
                            <div v-if="searchQuery" style="margin-top:1rem;">
                                <button type="button" class="solid-btn btn-indigo" @click="clearSearch">
                                    <i class="fas fa-undo"></i> Reset Pencarian
                                </button>
                            </div>
                            <div v-else style="margin-top:1rem;">
                                <Link :href="route('mahasiswa.tagihan.index')" class="solid-btn btn-indigo">
                                    <i class="fas fa-file-invoice-dollar"></i> Buka Daftar Tagihan Kuliah
                                </Link>
                            </div>
                        </div>

                        <div v-else>
                            <!-- DESKTOP TABLE -->
                            <div class="table-responsive desktop-view-only">
                                <table class="executive-table">
                                    <thead>
                                        <tr>
                                            <th style="width:20%;">No. & Waktu Transaksi</th>
                                            <th style="width:16%;">Tagihan Semester</th>
                                            <th style="width:20%;">Nominal Pembayaran</th>
                                            <th style="width:20%;">Kanal & Nomor VA</th>
                                            <th style="width:14%;">Status</th>
                                            <th style="width:10%; text-align:right;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="r in riwayat.data" :key="r.id" class="table-row-hover">
                                            <!-- Column 1: No & Waktu -->
                                            <td>
                                                <div class="trans-id-row">
                                                    <span class="badge-id">#{{ r.id }}</span>
                                                    <button
                                                        type="button"
                                                        class="copy-id-btn"
                                                        :title="copiedId === 'id-' + r.id ? 'Tersalin!' : 'Salin ID Transaksi'"
                                                        @click="copyText(String(r.id), 'id-' + r.id)"
                                                    >
                                                        <i :class="copiedId === 'id-' + r.id ? 'fas fa-check text-emerald' : 'far fa-copy'"></i>
                                                    </button>
                                                </div>
                                                <div class="trans-datetime">
                                                    <i class="far fa-calendar-alt"></i>
                                                    {{ formatFullDateTime(r.created_at) }}
                                                </div>
                                            </td>

                                            <!-- Column 2: Semester -->
                                            <td>
                                                <div class="semester-pill-badge">
                                                    <span class="semester-number">Semester {{ r.tagihan?.semester || '-' }}</span>
                                                    <span class="semester-ta">{{ r.tagihan?.tahun_akademik || '-' }}</span>
                                                </div>
                                            </td>

                                            <!-- Column 3: Nominal -->
                                            <td>
                                                <div class="nominal-amount">
                                                    {{ formatRupiah(displayNominal(r)) }}
                                                </div>
                                                <div v-if="r.beasiswa" class="beasiswa-tag">
                                                    <i class="fas fa-graduation-cap"></i>
                                                    <span>{{ r.beasiswa.nama }}</span>
                                                </div>
                                            </td>

                                            <!-- Column 4: Kanal & VA -->
                                            <td>
                                                <div class="payment-method-row">
                                                    <div class="bank-avatar">
                                                        <i class="fas fa-university"></i>
                                                    </div>
                                                    <div>
                                                        <div class="bank-name">
                                                            {{ r.metode_pembayaran?.nama_metode || 'Virtual Account' }}
                                                        </div>
                                                        <div v-if="r.va_number" class="va-number-pill">
                                                            <span class="font-mono">{{ r.va_number }}</span>
                                                            <button
                                                                type="button"
                                                                class="va-copy-icon-btn"
                                                                :title="copiedId === 'va-' + r.id ? 'Tersalin!' : 'Salin Nomor VA'"
                                                                @click="copyText(r.va_number, 'va-' + r.id)"
                                                            >
                                                                <i :class="copiedId === 'va-' + r.id ? 'fas fa-check text-emerald' : 'far fa-copy'"></i>
                                                            </button>
                                                        </div>
                                                        <div v-else-if="r.nama_pengirim" class="sender-name">
                                                            Pengirim: {{ r.nama_pengirim }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Column 5: Status -->
                                            <td>
                                                <span class="badge-solid" :class="statusInfo(r).badgeClass">
                                                    <span class="pulse-dot" :style="{ backgroundColor: statusInfo(r).dotColor }"></span>
                                                    <i :class="statusInfo(r).icon" style="margin-right:0.25rem;"></i>
                                                    {{ statusInfo(r).shortLabel }}
                                                </span>
                                            </td>

                                            <!-- Column 6: Actions -->
                                            <td style="text-align:right;">
                                                <div class="table-actions-group">
                                                    <!-- Detail Button -->
                                                    <Link
                                                        :href="route('mahasiswa.pembayaran.show', r.id)"
                                                        class="action-btn action-btn-secondary"
                                                        title="Detail Pembayaran"
                                                    >
                                                        <i class="fas fa-arrow-right"></i>
                                                    </Link>

                                                    <!-- Invoice PDF Button if Lunas -->
                                                    <a
                                                        v-if="r.status === 'dikonfirmasi' && r.tagihan_id"
                                                        :href="route('mahasiswa.tagihan.invoice', r.tagihan_id)"
                                                        target="_blank"
                                                        class="action-btn action-btn-pdf"
                                                        title="Buka / Unduh Invoice PDF Resmi"
                                                    >
                                                        <i class="fas fa-file-invoice"></i>
                                                    </a>

                                                    <!-- Quick View Modal Button -->
                                                    <button
                                                        type="button"
                                                        class="action-btn action-btn-quick"
                                                        title="Ringkasan Cepat"
                                                        @click="openQuickView(r)"
                                                    >
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- MOBILE CARDS (Visible on phone/tablet) -->
                            <div class="mobile-cards-list">
                                <div
                                    v-for="r in riwayat.data"
                                    :key="'m-' + r.id"
                                    class="mobile-receipt-card"
                                >
                                    <!-- Top Row -->
                                    <div class="mobile-receipt-header">
                                        <div class="mobile-receipt-title">
                                            <span class="badge-id">#{{ r.id }}</span>
                                            <span class="mobile-sem-text">Semester {{ r.tagihan?.semester }} ({{ r.tagihan?.tahun_akademik }})</span>
                                        </div>
                                        <span class="badge-solid" :class="statusInfo(r).badgeClass">
                                            <span class="pulse-dot" :style="{ backgroundColor: statusInfo(r).dotColor }"></span>
                                            {{ statusInfo(r).shortLabel }}
                                        </span>
                                    </div>

                                    <!-- Main Info -->
                                    <div class="mobile-receipt-body">
                                        <div class="mobile-amount-box">
                                            <span class="mobile-amount-label">Jumlah Pembayaran:</span>
                                            <div class="mobile-amount-val">{{ formatRupiah(displayNominal(r)) }}</div>
                                        </div>

                                        <div v-if="r.beasiswa" class="beasiswa-tag" style="margin-bottom:0.75rem;">
                                            <i class="fas fa-graduation-cap"></i>
                                            <span>{{ r.beasiswa.nama }}</span>
                                        </div>

                                        <div class="mobile-meta-grid">
                                            <div class="mobile-meta-item">
                                                <span class="meta-item-label">Metode / Bank</span>
                                                <span class="meta-item-value">{{ r.metode_pembayaran?.nama_metode || 'VA' }}</span>
                                            </div>
                                            <div class="mobile-meta-item">
                                                <span class="meta-item-label">Tanggal Transaksi</span>
                                                <span class="meta-item-value">{{ formatDate(r.created_at) }}</span>
                                            </div>
                                        </div>

                                        <!-- VA Number Row -->
                                        <div v-if="r.va_number" class="mobile-va-strip">
                                            <div class="mobile-va-info">
                                                <span class="mobile-va-label">Nomor VA:</span>
                                                <span class="mobile-va-code">{{ r.va_number }}</span>
                                            </div>
                                            <button
                                                type="button"
                                                class="mobile-copy-btn"
                                                @click="copyText(r.va_number, 'm-va-' + r.id)"
                                            >
                                                <i :class="copiedId === 'm-va-' + r.id ? 'fas fa-check text-emerald' : 'far fa-copy'"></i>
                                                {{ copiedId === 'm-va-' + r.id ? 'Disalin' : 'Salin' }}
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="mobile-receipt-footer">
                                        <Link
                                            :href="route('mahasiswa.pembayaran.show', r.id)"
                                            class="mobile-action-btn btn-secondary-fill"
                                        >
                                            <i class="fas fa-info-circle"></i> Detail Lengkap
                                        </Link>

                                        <a
                                            v-if="r.status === 'dikonfirmasi' && r.tagihan_id"
                                            :href="route('mahasiswa.tagihan.invoice', r.tagihan_id)"
                                            target="_blank"
                                            class="mobile-action-btn btn-indigo-fill"
                                        >
                                            <i class="fas fa-file-invoice"></i> Invoice PDF
                                        </a>

                                        <button
                                            type="button"
                                            class="mobile-action-btn btn-outline"
                                            @click="openQuickView(r)"
                                        >
                                            <i class="fas fa-eye"></i> Quick
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. PAGINATION FOOTER -->
                            <div v-if="riwayat.links && riwayat.links.length > 3" class="pagination-bar">
                                <div class="pagination-info-text">
                                    Menampilkan <span class="fw-bold">{{ riwayat.from || 0 }}</span> sampai
                                    <span class="fw-bold">{{ riwayat.to || 0 }}</span> dari
                                    <span class="fw-bold">{{ riwayat.total || 0 }}</span> total transaksi
                                </div>

                                <div class="pagination-controls">
                                    <template v-for="(link, idx) in riwayat.links" :key="idx">
                                        <Link
                                            v-if="link.url"
                                            :href="link.url"
                                            class="page-link-btn"
                                            :class="{ active: link.active }"
                                            preserve-scroll
                                            v-html="link.label"
                                        />
                                        <span
                                            v-else
                                            class="page-link-btn disabled"
                                            v-html="link.label"
                                        />
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. MODAL: QUICK TRANSACTION DETAILS -->
        <div v-if="showQuickModal && selectedPayment" class="modal-backdrop" @click.self="closeQuickView">
            <div class="modal-dialog-box animate-scale-in">
                <!-- Modal Header -->
                <div class="modal-header-custom">
                    <div class="modal-header-left">
                        <div class="modal-icon-badge">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <div>
                            <h3 class="modal-title-text">Ringkasan Transaksi #{{ selectedPayment.id }}</h3>
                            <p class="modal-subtitle-text">ID Referensi Pembayaran UKT</p>
                        </div>
                    </div>
                    <button type="button" class="modal-close-btn" @click="closeQuickView">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body-custom">
                    <!-- Status Banner -->
                    <div class="status-announcement" :class="statusInfo(selectedPayment).badgeClass">
                        <i :class="statusInfo(selectedPayment).icon" class="status-announcement-icon"></i>
                        <div>
                            <div class="status-announcement-title">{{ statusInfo(selectedPayment).label }}</div>
                            <div class="status-announcement-desc">{{ statusInfo(selectedPayment).desc }}</div>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="quick-details-grid">
                        <div class="detail-item">
                            <span class="detail-label">Tagihan Semester</span>
                            <span class="detail-value fw-bold">
                                Semester {{ selectedPayment.tagihan?.semester }} ({{ selectedPayment.tagihan?.tahun_akademik }})
                            </span>
                        </div>

                        <div class="detail-item">
                            <span class="detail-label">Nominal Pembayaran</span>
                            <span class="detail-value text-indigo fw-bold font-lg">
                                {{ formatRupiah(displayNominal(selectedPayment)) }}
                            </span>
                        </div>

                        <div class="detail-item">
                            <span class="detail-label">Kanal / Metode Pembayaran</span>
                            <span class="detail-value">
                                {{ selectedPayment.metode_pembayaran?.nama_metode || 'Virtual Account Bank' }}
                            </span>
                        </div>

                        <div v-if="selectedPayment.va_number" class="detail-item">
                            <span class="detail-label">Nomor Virtual Account</span>
                            <div class="va-copy-box">
                                <span class="font-mono fw-bold">{{ selectedPayment.va_number }}</span>
                                <button
                                    type="button"
                                    class="va-copy-pill-btn"
                                    @click="copyText(selectedPayment.va_number, 'modal-va')"
                                >
                                    <i :class="copiedId === 'modal-va' ? 'fas fa-check text-emerald' : 'far fa-copy'"></i>
                                    {{ copiedId === 'modal-va' ? 'Disalin' : 'Salin' }}
                                </button>
                            </div>
                        </div>

                        <div class="detail-item">
                            <span class="detail-label">Waktu Pembuatan VA</span>
                            <span class="detail-value">{{ formatFullDateTime(selectedPayment.created_at) }}</span>
                        </div>

                        <div v-if="selectedPayment.verified_at" class="detail-item">
                            <span class="detail-label">Waktu Pelunasan / Verifikasi</span>
                            <span class="detail-value text-emerald fw-bold">
                                <i class="fas fa-check-circle"></i> {{ formatFullDateTime(selectedPayment.verified_at) }}
                            </span>
                        </div>

                        <div v-if="selectedPayment.va_expired_at && selectedPayment.status === 'pending'" class="detail-item">
                            <span class="detail-label">Batas Waktu Pembayaran</span>
                            <span class="detail-value text-danger">
                                <i class="fas fa-hourglass-half"></i> {{ formatFullDateTime(selectedPayment.va_expired_at) }}
                            </span>
                        </div>

                        <div v-if="selectedPayment.beasiswa" class="detail-item detail-full-width">
                            <span class="detail-label">Potongan Program Beasiswa</span>
                            <div class="beasiswa-tag" style="margin-top:0.25rem;">
                                <i class="fas fa-graduation-cap"></i>
                                <span>{{ selectedPayment.beasiswa.nama }} ({{ selectedPayment.beasiswa.kode }})</span>
                            </div>
                        </div>

                        <div v-if="selectedPayment.catatan_admin" class="detail-item detail-full-width">
                            <span class="detail-label">Catatan Administrasi</span>
                            <div class="admin-notes-box">
                                {{ selectedPayment.catatan_admin }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer Actions -->
                <div class="modal-footer-custom">
                    <button type="button" class="solid-btn btn-white-border" @click="closeQuickView">
                        Tutup
                    </button>

                    <a
                        v-if="selectedPayment.status === 'dikonfirmasi' && selectedPayment.tagihan_id"
                        :href="route('mahasiswa.tagihan.invoice', selectedPayment.tagihan_id)"
                        target="_blank"
                        class="solid-btn btn-indigo"
                    >
                        <i class="fas fa-file-invoice"></i> Buka Invoice PDF
                    </a>

                    <Link
                        :href="route('mahasiswa.pembayaran.show', selectedPayment.id)"
                        class="solid-btn btn-emerald"
                    >
                        <i class="fas fa-external-link-alt"></i> Halaman Pembayaran
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* =======================================================
   MAHASISWA RIWAYAT TRANSAKSI - EXECUTIVE REDESIGN
   ======================================================= */

/* --- Page Header --- */
.riwayat-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.riwayat-header-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    margin: 0;
}

.riwayat-header-subtitle {
    font-size: 0.84rem;
    color: #64748b;
    margin: 0.25rem 0 0;
}

.header-action-btns {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

/* --- Buttons --- */
.solid-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
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
.btn-indigo:hover {
    background: #4338ca;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
    transform: translateY(-1px);
}

.btn-emerald {
    background: #059669;
    color: #ffffff;
}
.btn-emerald:hover {
    background: #047857;
    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
    transform: translateY(-1px);
}

/* --- Colors & Icons --- */
.text-indigo { color: #4f46e5 !important; }
.text-emerald { color: #059669 !important; }
.text-amber { color: #d97706 !important; }
.text-slate { color: #475569 !important; }
.text-danger { color: #dc2626 !important; }
.fw-bold { font-weight: 700; }
.font-mono { font-family: 'SF Mono', 'Cascadia Code', 'Fira Code', 'Consolas', monospace; }
.font-lg { font-size: 1.05rem; }

.bg-emerald-light { background: #ecfdf5; }
.bg-indigo-light { background: #eef2ff; }
.bg-amber-light { background: #fffbeb; }
.bg-slate-light { background: #f1f5f9; }



/* --- 2. Main Card Container --- */
.main-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.95rem;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}

/* Top Control Bar */
.card-controls-bar {
    padding: 1.15rem 1.35rem;
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.85rem;
}

.filter-tab-pills {
    display: inline-flex;
    gap: 0.35rem;
    background: #f1f5f9;
    padding: 0.3rem;
    border-radius: 0.65rem;
    flex-wrap: wrap;
}

.tab-pill {
    border: none;
    background: transparent;
    padding: 0.4rem 0.85rem;
    border-radius: 0.45rem;
    font-size: 0.775rem;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

.tab-pill:hover:not(.active) {
    color: #1e293b;
    background: rgba(255, 255, 255, 0.5);
}

.tab-pill.active {
    background: #ffffff;
    color: #0f172a;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.tab-pill-count {
    padding: 0.1rem 0.4rem;
    background: #e2e8f0;
    border-radius: 1rem;
    font-size: 0.68rem;
    font-weight: 700;
}

.tab-pill-count.count-emerald {
    background: #dcfce7;
    color: #166534;
}

.tab-pill-count.count-amber {
    background: #fef3c7;
    color: #92400e;
}

/* Search Box */
.search-input-wrap {
    position: relative;
    min-width: 260px;
}

.search-icon {
    position: absolute;
    left: 0.85rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.825rem;
}

.search-field {
    width: 100%;
    padding: 0.475rem 2rem 0.475rem 2.25rem;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 0.55rem;
    font-size: 0.8125rem;
    color: #0f172a;
    transition: all 0.2s ease;
}

.search-field:focus {
    outline: none;
    background: #ffffff;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
}

.search-clear-btn {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    font-size: 0.8rem;
    padding: 0.2rem;
}

.search-clear-btn:hover {
    color: #475569;
}

/* --- Table Styles --- */
.table-container {
    width: 100%;
}

.executive-table {
    width: 100%;
    border-collapse: collapse;
}

.executive-table th {
    padding: 0.85rem 1.25rem;
    background: #f8fafc;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    border-bottom: 1.5px solid #e2e8f0;
    text-align: left;
}

.executive-table td {
    padding: 1rem 1.25rem;
    font-size: 0.8125rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.table-row-hover:hover td {
    background-color: #fafbfc;
}

/* Trans ID & Date */
.trans-id-row {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    margin-bottom: 0.25rem;
}

.badge-id {
    font-family: 'SF Mono', monospace;
    font-size: 0.75rem;
    font-weight: 700;
    color: #334155;
    background: #f1f5f9;
    padding: 0.15rem 0.45rem;
    border-radius: 0.35rem;
    border: 1px solid #e2e8f0;
}

.copy-id-btn {
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 0.15rem;
    font-size: 0.75rem;
    transition: color 0.15s;
}
.copy-id-btn:hover {
    color: #4f46e5;
}

.trans-datetime {
    font-size: 0.75rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

/* Semester Pill */
.semester-pill-badge {
    display: inline-flex;
    flex-direction: column;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 0.35rem 0.65rem;
    border-radius: 0.5rem;
}

.semester-number {
    font-size: 0.78rem;
    font-weight: 700;
    color: #0f172a;
}

.semester-ta {
    font-size: 0.7rem;
    color: #64748b;
}

/* Nominal & Beasiswa */
.nominal-amount {
    font-size: 0.95rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.01em;
}

.beasiswa-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    margin-top: 0.25rem;
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
    padding: 0.15rem 0.45rem;
    border-radius: 0.35rem;
    font-size: 0.68rem;
    font-weight: 700;
}

/* Payment Method & VA */
.payment-method-row {
    display: flex;
    align-items: center;
    gap: 0.65rem;
}

.bank-avatar {
    width: 2.15rem;
    height: 2.15rem;
    border-radius: 0.5rem;
    background: #eef2ff;
    color: #4f46e5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.bank-name {
    font-weight: 700;
    color: #1e293b;
    font-size: 0.8125rem;
}

.va-number-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 0.1rem 0.4rem;
    border-radius: 0.35rem;
    font-size: 0.72rem;
    color: #475569;
    margin-top: 0.15rem;
}

.va-copy-icon-btn {
    background: none;
    border: none;
    padding: 0;
    color: #94a3b8;
    cursor: pointer;
    font-size: 0.72rem;
}
.va-copy-icon-btn:hover {
    color: #4f46e5;
}

.sender-name {
    font-size: 0.72rem;
    color: #64748b;
    margin-top: 0.15rem;
}

/* Status Badges */
.badge-solid {
    display: inline-flex;
    align-items: center;
    padding: 0.32rem 0.75rem;
    border-radius: 2rem;
    font-size: 0.72rem;
    font-weight: 700;
    white-space: nowrap;
}

.pulse-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    margin-right: 0.4rem;
    animation: pulseDot 2s infinite ease-in-out;
}

@keyframes pulseDot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.85); }
}

.badge-solid-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.badge-solid-warning {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}

.badge-solid-danger {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

/* Action Buttons in Table */
.table-actions-group {
    display: inline-flex;
    gap: 0.35rem;
    align-items: center;
}

.action-btn {
    width: 2rem;
    height: 2rem;
    border-radius: 0.45rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s ease;
    border: 1px solid transparent;
}

.action-btn-secondary {
    background: #f1f5f9;
    color: #334155;
    border-color: #e2e8f0;
}
.action-btn-secondary:hover {
    background: #e2e8f0;
    color: #0f172a;
    transform: translateY(-1px);
}

.action-btn-pdf {
    background: #eef2ff;
    color: #4f46e5;
    border-color: #c7d2fe;
}
.action-btn-pdf:hover {
    background: #4f46e5;
    color: #ffffff;
    transform: translateY(-1px);
}

.action-btn-quick {
    background: #f8fafc;
    color: #64748b;
    border-color: #e2e8f0;
}
.action-btn-quick:hover {
    background: #ffffff;
    color: #0f172a;
    border-color: #cbd5e1;
    transform: translateY(-1px);
}

/* --- Mobile Card Layout --- */
.desktop-view-only { display: block; }
.mobile-cards-list { display: none; }

.mobile-receipt-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 0.85rem;
    padding: 1.15rem;
    margin: 0.85rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.mobile-receipt-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.85rem;
    padding-bottom: 0.65rem;
    border-bottom: 1px solid #f1f5f9;
}

.mobile-receipt-title {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.mobile-sem-text {
    font-size: 0.8rem;
    font-weight: 700;
    color: #0f172a;
}

.mobile-amount-box {
    margin-bottom: 0.65rem;
}

.mobile-amount-label {
    font-size: 0.72rem;
    color: #64748b;
    display: block;
}

.mobile-amount-val {
    font-size: 1.25rem;
    font-weight: 800;
    color: #4f46e5;
}

.mobile-meta-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 0.65rem;
    margin-bottom: 0.75rem;
}

.mobile-meta-item {
    display: flex;
    flex-direction: column;
}

.meta-item-label {
    font-size: 0.68rem;
    color: #94a3b8;
    text-transform: uppercase;
    font-weight: 600;
}

.meta-item-value {
    font-size: 0.78rem;
    font-weight: 700;
    color: #1e293b;
    margin-top: 0.1rem;
}

.mobile-va-strip {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f1f5f9;
    border-radius: 0.45rem;
    padding: 0.45rem 0.65rem;
    margin-bottom: 0.85rem;
}

.mobile-va-info {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.mobile-va-label {
    font-size: 0.72rem;
    color: #64748b;
}

.mobile-va-code {
    font-family: 'SF Mono', monospace;
    font-size: 0.82rem;
    font-weight: 700;
    color: #0f172a;
}

.mobile-copy-btn {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 0.35rem;
    padding: 0.2rem 0.45rem;
    font-size: 0.72rem;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.mobile-receipt-footer {
    display: flex;
    gap: 0.45rem;
    padding-top: 0.75rem;
    border-top: 1px solid #f1f5f9;
}

.mobile-action-btn {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    padding: 0.5rem 0.65rem;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    font-weight: 700;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-secondary-fill {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #e2e8f0;
}

.btn-indigo-fill {
    background: #4f46e5;
    color: #ffffff;
}

.btn-outline {
    background: #ffffff;
    color: #475569;
    border: 1px solid #cbd5e1;
    flex: 0 0 auto;
}

/* --- Empty State --- */
.empty-state-box {
    text-align: center;
    padding: 3.75rem 1.5rem;
}

.empty-icon-circle {
    width: 4rem;
    height: 4rem;
    border-radius: 50%;
    background: #f1f5f9;
    color: #94a3b8;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    margin-bottom: 1rem;
}

.empty-state-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.35rem;
}

.empty-state-text {
    font-size: 0.84rem;
    color: #64748b;
    max-width: 380px;
    margin: 0 auto;
    line-height: 1.5;
}

/* --- Pagination Bar --- */
.pagination-bar {
    padding: 1rem 1.35rem;
    background: #ffffff;
    border-top: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.pagination-info-text {
    font-size: 0.8125rem;
    color: #64748b;
}

.pagination-controls {
    display: inline-flex;
    gap: 0.3rem;
    align-items: center;
}

.page-link-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.15rem;
    height: 2.15rem;
    padding: 0 0.55rem;
    border-radius: 0.45rem;
    font-size: 0.78rem;
    font-weight: 600;
    color: #475569;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    text-decoration: none;
    transition: all 0.15s ease;
}

.page-link-btn:hover:not(.disabled):not(.active) {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
}

.page-link-btn.active {
    background: #4f46e5;
    border-color: #4f46e5;
    color: #ffffff;
    font-weight: 700;
}

.page-link-btn.disabled {
    opacity: 0.45;
    cursor: not-allowed;
    background: #f8fafc;
}

/* --- Quick Detail Modal --- */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 1050;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.modal-dialog-box {
    background: #ffffff;
    border-radius: 1rem;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.18);
    width: 100%;
    max-width: 580px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.animate-scale-in {
    animation: scaleIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes scaleIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

.modal-header-custom {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
}

.modal-header-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.modal-icon-badge {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.65rem;
    background: #eef2ff;
    color: #4f46e5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
}

.modal-title-text {
    font-size: 1.05rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.modal-subtitle-text {
    font-size: 0.75rem;
    color: #64748b;
    margin: 0.15rem 0 0;
}

.modal-close-btn {
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 1.15rem;
    cursor: pointer;
    padding: 0.35rem;
    border-radius: 0.35rem;
    transition: all 0.15s;
}
.modal-close-btn:hover {
    color: #0f172a;
    background: #e2e8f0;
}

.modal-body-custom {
    padding: 1.5rem;
    overflow-y: auto;
}

.status-announcement {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.85rem 1rem;
    border-radius: 0.65rem;
    margin-bottom: 1.25rem;
}

.status-announcement-icon {
    font-size: 1.25rem;
    margin-top: 0.1rem;
}

.status-announcement-title {
    font-weight: 700;
    font-size: 0.85rem;
}

.status-announcement-desc {
    font-size: 0.75rem;
    margin-top: 0.15rem;
    line-height: 1.4;
    opacity: 0.9;
}

.quick-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-full-width {
    grid-column: 1 / -1;
}

.detail-label {
    font-size: 0.72rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin-bottom: 0.25rem;
}

.detail-value {
    font-size: 0.875rem;
    color: #1e293b;
}

.va-copy-box {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.45rem;
    padding: 0.35rem 0.65rem;
    width: fit-content;
}

.va-copy-pill-btn {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 0.35rem;
    padding: 0.15rem 0.45rem;
    font-size: 0.7rem;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.admin-notes-box {
    background: #fffbeb;
    border: 1px solid #fde68a;
    color: #92400e;
    padding: 0.65rem 0.85rem;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    line-height: 1.5;
}

.modal-footer-custom {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

/* --- Responsive Media Queries --- */
@media (max-width: 768px) {
    .desktop-view-only { display: none; }
    .mobile-cards-list { display: block; }

    .riwayat-page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .riwayat-header-title {
        font-size: 1.1rem;
    }

    .riwayat-header-subtitle {
        font-size: 0.78rem;
    }

    .header-action-btns {
        width: 100%;
    }

    .header-action-btns .solid-btn {
        flex: 1;
        justify-content: center;
        font-size: 0.78rem;
        padding: 0.5rem 0.75rem;
    }

    .card-controls-bar {
        flex-direction: column;
        align-items: stretch;
        padding: 0.85rem;
        gap: 0.65rem;
    }

    .filter-tab-pills {
        overflow-x: auto;
        white-space: nowrap;
        width: 100%;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        padding: 0.25rem;
    }

    .filter-tab-pills::-webkit-scrollbar {
        display: none;
    }

    .tab-pill {
        padding: 0.45rem 0.7rem;
        font-size: 0.72rem;
        flex-shrink: 0;
    }

    .search-input-wrap {
        width: 100%;
        min-width: unset;
    }

    .main-card {
        border-radius: 0.75rem;
    }

    /* Mobile card improvements */
    .mobile-receipt-card {
        margin: 0.65rem 0.75rem;
        padding: 1rem;
        border-radius: 0.75rem;
    }

    .mobile-receipt-header {
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
        padding-bottom: 0.55rem;
    }

    .mobile-receipt-title {
        flex-wrap: wrap;
        gap: 0.35rem;
    }

    .mobile-sem-text {
        font-size: 0.75rem;
    }

    .badge-solid {
        font-size: 0.68rem;
        padding: 0.25rem 0.55rem;
    }

    .mobile-amount-val {
        font-size: 1.15rem;
    }

    .mobile-meta-grid {
        padding: 0.55rem;
        gap: 0.4rem;
    }

    .mobile-va-strip {
        flex-wrap: wrap;
        gap: 0.45rem;
        padding: 0.5rem 0.55rem;
    }

    .mobile-va-code {
        font-size: 0.75rem;
        word-break: break-all;
    }

    .mobile-receipt-footer {
        flex-wrap: wrap;
        gap: 0.35rem;
    }

    .mobile-action-btn {
        min-height: 2.75rem;
        font-size: 0.72rem;
        padding: 0.45rem 0.5rem;
    }

    /* Pagination */
    .pagination-bar {
        flex-direction: column;
        align-items: center;
        padding: 0.85rem;
        gap: 0.65rem;
    }

    .pagination-info-text {
        font-size: 0.75rem;
        text-align: center;
    }

    .pagination-controls {
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.2rem;
    }

    .page-link-btn {
        min-width: 1.85rem;
        height: 1.85rem;
        padding: 0 0.4rem;
        font-size: 0.72rem;
    }

    /* Modal */
    .modal-backdrop {
        padding: 0.5rem;
        align-items: flex-end;
    }

    .modal-dialog-box {
        max-width: 100%;
        max-height: 85vh;
        border-radius: 1rem 1rem 0 0;
    }

    .modal-header-custom {
        padding: 1rem;
    }

    .modal-icon-badge {
        width: 2rem;
        height: 2rem;
        font-size: 0.95rem;
    }

    .modal-title-text {
        font-size: 0.92rem;
    }

    .modal-body-custom {
        padding: 1rem;
    }

    .quick-details-grid {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    .va-copy-box {
        flex-wrap: wrap;
    }

    .modal-footer-custom {
        flex-direction: column-reverse;
        padding: 0.85rem;
        gap: 0.4rem;
    }

    .modal-footer-custom .solid-btn {
        width: 100%;
        justify-content: center;
        min-height: 2.75rem;
    }

    /* Empty state */
    .empty-state-box {
        padding: 2.5rem 1rem;
    }

    .empty-icon-circle {
        width: 3.25rem;
        height: 3.25rem;
        font-size: 1.35rem;
    }

    .empty-state-title {
        font-size: 0.95rem;
    }

    .empty-state-text {
        font-size: 0.78rem;
    }

    /* Bottom nav padding */
    .page-body {
        padding-bottom: calc(76px + env(safe-area-inset-bottom, 0px));
    }
}

</style>
