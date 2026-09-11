<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { formatRupiah, formatDate, formatDateTime } from '@/utils';
import { computed, ref, watch } from 'vue';
import QuickPayDrawer from '@/Components/QuickPayDrawer.vue';

const props = defineProps({
    tagihans: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_tagihan: 0,
            total_lunas: 0,
            total_belum_lunas: 0,
            total_nominal_lunas: 0,
            total_nominal_belum_lunas: 0,
        }),
    },
    semesterAktif: {
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
    filters: {
        type: Object,
        default: () => ({
            status: 'semua',
            search: '',
        }),
    },
});

const showQuickPay = ref(false);
const selectedTagihanForPay = ref(null);
const activeStatusFilter = ref(props.filters.status || 'semua');
const searchQuery = ref(props.filters.search || '');

let searchTimeout = null;

const applyFilters = () => {
    router.get(
        route('mahasiswa.tagihan.index'),
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

const openQuickPay = (tagihan) => {
    selectedTagihanForPay.value = tagihan;
    showQuickPay.value = true;
};

const handleDrawerSuccess = () => {
    router.reload({ only: ['tagihans', 'stats'] });
};

const isExpiredPayment = (p) => {
    if (p.status === 'expired') return true;
    if (p.status === 'pending' && p.va_expired_at) {
        return new Date(p.va_expired_at).getTime() <= Date.now();
    }
    return false;
};

const isLunas = (tagihan) => {
    return tagihan.status === 'sudah_dibayar' || tagihan.pembayarans?.some(p => p.status === 'dikonfirmasi');
};

const canDispensasi = (tagihan) => {
    if (tagihan.status === 'dispen') return false;
    return !isLunas(tagihan);
};

const getTagihanStatus = (tagihan) => {
    if (isLunas(tagihan)) {
        return {
            label: 'Lunas Terverifikasi',
            shortLabel: 'Lunas',
            badgeClass: 'badge-solid-success',
            icon: 'fas fa-check-circle',
            dotColor: '#10b981',
        };
    }

    if (tagihan.status === 'dispen') {
        return {
            label: 'Dispensasi Disetujui',
            shortLabel: 'Dispensasi',
            badgeClass: 'badge-solid-dispen',
            icon: 'fas fa-user-clock',
            dotColor: '#3b82f6',
        };
    }

    const pendingPayment = tagihan.pembayarans?.find(p => p.status === 'pending' && !isExpiredPayment(p));
    if (pendingPayment) {
        return {
            label: 'Menunggu Pembayaran VA',
            shortLabel: 'Menunggu VA',
            badgeClass: 'badge-solid-warning',
            icon: 'fas fa-clock',
            dotColor: '#f59e0b',
        };
    }

    const expiredPayment = tagihan.pembayarans?.find(p => isExpiredPayment(p));
    if (expiredPayment) {
        return {
            label: 'VA Kedaluwarsa',
            shortLabel: 'VA Expired',
            badgeClass: 'badge-solid-danger',
            icon: 'fas fa-hourglass-end',
            dotColor: '#ef4444',
        };
    }

    if (tagihan.status === 'terlambat') {
        return {
            label: 'Melewati Jatuh Tempo',
            shortLabel: 'Terlambat',
            badgeClass: 'badge-solid-danger',
            icon: 'fas fa-exclamation-circle',
            dotColor: '#ef4444',
        };
    }

    return {
        label: 'Belum Dibayar',
        shortLabel: 'Belum Lunas',
        badgeClass: 'badge-solid-danger',
        icon: 'fas fa-exclamation-triangle',
        dotColor: '#ef4444',
    };
};

const getInvoiceLink = (tagihan) => {
    if (isLunas(tagihan)) {
        return route('mahasiswa.tagihan.invoice', tagihan.id);
    }
    return null;
};

const getDaysUntilDeadline = (dateStr) => {
    if (!dateStr) return null;
    const deadline = new Date(dateStr).getTime();
    const now = Date.now();
    const diffDays = Math.ceil((deadline - now) / (1000 * 60 * 60 * 24));
    return diffDays;
};

const copiedVaId = ref(null);
const copyVa = (vaNum, id) => {
    if (!vaNum) return;
    navigator.clipboard.writeText(vaNum);
    copiedVaId.value = id;
    setTimeout(() => {
        if (copiedVaId.value === id) {
            copiedVaId.value = null;
        }
    }, 2000);
};

// Check if any tagihan has an active pending payment
const activePendingTagihan = computed(() => {
    return props.tagihans.data?.find(t => t.pending_pembayaran && !isLunas(t)) || null;
});
</script>

<template>
    <Head title="Tagihan UKT Mahasiswa" />

    <AuthenticatedLayout>
        <template #header>
            <div class="tagihan-page-header">
                <div>
                    <h2 class="tagihan-header-title">
                        Tagihan Uang Kuliah Tunggal (UKT)
                    </h2>
                    <p class="tagihan-header-subtitle">
                        Kelola tagihan perkuliahan, bayar instan via Virtual Account, ajukan perpanjangan dispensasi, dan unduh invoice resmi.
                    </p>
                </div>

                <div class="header-action-btns">
                    <Link
                        :href="route('mahasiswa.dispensasi.index')"
                        class="solid-btn btn-white-border"
                        title="Buka Layanan Pengajuan Dispensasi"
                    >
                        <i class="fas fa-hand-holding-usd text-indigo"></i> Layanan Dispensasi
                    </Link>
                    <Link
                        :href="route('mahasiswa.riwayat.index')"
                        class="solid-btn btn-white-border"
                        title="Buka Riwayat & Bukti Transaksi"
                    >
                        <i class="fas fa-history text-indigo"></i> Riwayat Bayar
                    </Link>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">

                <!-- 1. ACTIVE SEMESTER HERO BANNER -->
                <div v-if="semesterAktif" class="academic-hero-banner">
                    <div class="academic-hero-content">
                        <div class="hero-left-info">
                            <div class="hero-status-pill">
                                <span class="pulse-dot bg-emerald"></span>
                                <span>Periode Akademik Aktif</span>
                            </div>
                            <h3 class="hero-ta-title">
                                Tahun Akademik {{ semesterAktif.tahun_akademik }}
                            </h3>
                            <p class="hero-student-meta">
                                Mahasiswa: <strong>{{ mahasiswa?.nama_lengkap || 'Mahasiswa' }}</strong> (NIM: <span class="font-mono">{{ mahasiswa?.nim || '-' }}</span>) · {{ mahasiswa?.jurusan || '-' }}
                            </p>
                        </div>

                        <div class="hero-right-deadline">
                            <div class="deadline-icon-box">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div>
                                <div class="deadline-label">Batas Jatuh Tempo UKT:</div>
                                <div class="deadline-date-val">
                                    {{ formatDate(semesterAktif.jatuh_tempo) }}
                                </div>
                                <div v-if="getDaysUntilDeadline(semesterAktif.jatuh_tempo) !== null" class="deadline-relative">
                                    <span v-if="getDaysUntilDeadline(semesterAktif.jatuh_tempo) > 0" class="badge-time text-emerald">
                                        <i class="fas fa-hourglass-half"></i> Tersisa {{ getDaysUntilDeadline(semesterAktif.jatuh_tempo) }} hari lagi
                                    </span>
                                    <span v-else-if="getDaysUntilDeadline(semesterAktif.jatuh_tempo) === 0" class="badge-time text-amber">
                                        <i class="fas fa-exclamation-circle"></i> Berakhir hari ini
                                    </span>
                                    <span v-else class="badge-time text-danger">
                                        <i class="fas fa-times-circle"></i> Melewati tenggat waktu
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 1B. ACTIVE PENDING VA NOTICE BANNER (IF VA WAS CREATED) -->
                <div v-if="activePendingTagihan" class="pending-va-alert-card">
                    <div class="alert-icon-wrap">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="alert-text-content">
                        <div class="alert-top-badge">MENUNGGU TRANSFER BANK</div>
                        <div class="alert-msg">
                            Nomor Virtual Account untuk <strong>Semester {{ activePendingTagihan.semester }}</strong> ({{ activePendingTagihan.tahun_akademik }}) telah diterbitkan:
                            <strong class="font-mono active-va-inline">{{ activePendingTagihan.pending_pembayaran.va_number }}</strong>
                            <span class="active-bank-inline">({{ activePendingTagihan.pending_pembayaran.metode_pembayaran_nama }})</span>.
                            Silakan lakukan transfer sebelum batas waktu untuk otomatis mengubah status menjadi Lunas.
                        </div>
                    </div>
                    <Link
                        :href="route('mahasiswa.pembayaran.show', activePendingTagihan.pending_pembayaran.id)"
                        class="solid-btn btn-amber-fill"
                    >
                        <i class="fas fa-clock"></i> Selesaikan Bayar (Lihat VA)
                    </Link>
                </div>

                <!-- 2. MAIN CARD: DATA TABLE & CONTROLS -->
                <div class="main-card">
                    <!-- Control Bar -->
                    <div class="card-controls-bar">
                        <!-- Filter Tabs -->
                        <div class="filter-tab-pills">
                            <button
                                type="button"
                                class="tab-pill"
                                :class="{ active: activeStatusFilter === 'semua' }"
                                @click="setFilter('semua')"
                            >
                                <i class="fas fa-list-ul"></i> Semua
                                <span class="tab-pill-count">{{ stats.total_tagihan }}</span>
                            </button>
                            <button
                                type="button"
                                class="tab-pill"
                                :class="{ active: activeStatusFilter === 'belum_lunas' }"
                                @click="setFilter('belum_lunas')"
                            >
                                <i class="fas fa-exclamation-circle text-rose"></i> Belum Lunas
                                <span class="tab-pill-count count-rose">{{ stats.total_belum_lunas }}</span>
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
                                :class="{ active: activeStatusFilter === 'dispen' }"
                                @click="setFilter('dispen')"
                            >
                                <i class="fas fa-user-clock text-indigo"></i> Dispensasi
                            </button>
                        </div>

                        <!-- Search Input -->
                        <div class="search-input-wrap">
                            <i class="fas fa-search search-icon"></i>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari Semester / Tahun Akademik..."
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
                        <div v-if="!tagihans.data || tagihans.data.length === 0" class="empty-state-box">
                            <div class="empty-icon-circle">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <h4 class="empty-state-title">Tidak Ada Tagihan Ditemukan</h4>
                            <p class="empty-state-text">
                                {{ searchQuery ? 'Tidak ada tagihan UKT yang sesuai dengan kata kunci pencarian.' : 'Belum ada catatan tagihan UKT yang terdaftar untuk akun mahasiswa Anda.' }}
                            </p>
                            <div v-if="searchQuery" style="margin-top:1rem;">
                                <button type="button" class="solid-btn btn-indigo" @click="clearSearch">
                                    <i class="fas fa-undo"></i> Reset Pencarian
                                </button>
                            </div>
                        </div>

                        <div v-else>
                            <!-- DESKTOP TABLE -->
                            <div class="table-responsive desktop-view-only">
                                <table class="executive-table">
                                    <thead>
                                        <tr>
                                            <th style="width:20%;">Semester & Tahun Akademik</th>
                                            <th style="width:28%;">Nominal & Status Bayar</th>
                                            <th style="width:18%;">Batas Jatuh Tempo</th>
                                            <th style="width:16%;">Status</th>
                                            <th style="width:18%; text-align:right;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="t in tagihans.data"
                                            :key="t.id"
                                            class="table-row-hover"
                                            :class="{ 'row-is-paid': isLunas(t) }"
                                        >
                                            <!-- Column 1: Semester & Tahun -->
                                            <td>
                                                <div class="semester-ident-box">
                                                    <div class="sem-badge-chip">
                                                        Semester {{ t.semester }}
                                                    </div>
                                                    <div class="ta-year-text">
                                                        {{ t.tahun_akademik }}
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Column 2: Nominal & Beasiswa & VA Strip -->
                                            <td>
                                                <div class="nominal-text">
                                                    {{ formatRupiah(t.nominal) }}
                                                </div>

                                                <!-- Active Pending VA Strip -->
                                                <div v-if="t.pending_pembayaran && !isLunas(t)" class="active-va-strip">
                                                    <div class="active-va-info">
                                                        <i class="fas fa-university text-amber"></i>
                                                        <span class="active-va-code font-mono">{{ t.pending_pembayaran.va_number }}</span>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        class="btn-copy-va-pill"
                                                        :title="copiedVaId === 'va-' + t.id ? 'Tersalin!' : 'Salin Nomor VA'"
                                                        @click="copyVa(t.pending_pembayaran.va_number, 'va-' + t.id)"
                                                    >
                                                        <i :class="copiedVaId === 'va-' + t.id ? 'fas fa-check text-emerald' : 'far fa-copy'"></i>
                                                        <span>{{ copiedVaId === 'va-' + t.id ? 'Tersalin' : 'Salin' }}</span>
                                                    </button>
                                                </div>

                                                <div v-if="t.beasiswa" class="beasiswa-tag-pill">
                                                    <i class="fas fa-graduation-cap"></i>
                                                    <span>{{ t.beasiswa.nama }} ({{ t.beasiswa.kode }}) · Potongan {{ formatRupiah(t.beasiswa.diskon) }}</span>
                                                </div>
                                                <div v-else-if="t.keterangan && t.keterangan.includes('Beasiswa')" class="beasiswa-tag-pill">
                                                    <i class="fas fa-graduation-cap"></i>
                                                    <span>{{ t.keterangan }}</span>
                                                </div>
                                            </td>

                                            <!-- Column 3: Jatuh Tempo -->
                                            <td>
                                                <div class="deadline-cell">
                                                    <div class="deadline-main">
                                                        <i class="far fa-calendar-alt text-slate"></i>
                                                        <span>{{ formatDate(t.jatuh_tempo) }}</span>
                                                    </div>
                                                    <div v-if="!isLunas(t) && t.jatuh_tempo" class="deadline-sub">
                                                        <span v-if="getDaysUntilDeadline(t.jatuh_tempo) > 0" class="days-remaining text-slate">
                                                            (Sisa {{ getDaysUntilDeadline(t.jatuh_tempo) }} hari)
                                                        </span>
                                                        <span v-else class="days-expired text-danger">
                                                            (Lewat tempo)
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Column 4: Status -->
                                            <td>
                                                <span class="badge-solid" :class="getTagihanStatus(t).badgeClass">
                                                    <span class="pulse-dot" :style="{ backgroundColor: getTagihanStatus(t).dotColor }"></span>
                                                    <i :class="getTagihanStatus(t).icon" style="margin-right:0.25rem;"></i>
                                                    {{ getTagihanStatus(t).shortLabel }}
                                                </span>
                                            </td>

                                            <!-- Column 5: Actions -->
                                            <td style="text-align:right;">
                                                <div class="table-actions-group">
                                                    <!-- If VA is already active: Button to View & Complete VA -->
                                                    <Link
                                                        v-if="!isLunas(t) && t.pending_pembayaran && t.status !== 'dispen'"
                                                        :href="route('mahasiswa.pembayaran.show', t.pending_pembayaran.id)"
                                                        class="action-btn-va-pending"
                                                        title="Selesaikan Pembayaran (Lihat Nomor VA & Panduan)"
                                                    >
                                                        <i class="fas fa-clock"></i>
                                                        <span>Selesaikan VA</span>
                                                    </Link>

                                                    <!-- If No Active VA yet: 1-Click Instant Pay Trigger -->
                                                    <button
                                                        v-else-if="!isLunas(t) && t.status !== 'dispen'"
                                                        type="button"
                                                        class="action-btn-pay"
                                                        title="Bayar Instan UKT"
                                                        @click="openQuickPay(t)"
                                                    >
                                                        <i class="fas fa-bolt text-amber-300"></i>
                                                        <span>Bayar Instan</span>
                                                    </button>

                                                    <!-- Dispensasi link -->
                                                    <Link
                                                        v-if="canDispensasi(t)"
                                                        :href="route('mahasiswa.dispensasi.index')"
                                                        class="action-icon-btn btn-dispensasi"
                                                        title="Ajukan Perpanjangan Tempo Dispensasi"
                                                    >
                                                        <i class="fas fa-hand-holding-usd"></i>
                                                    </Link>

                                                    <!-- Bukti Pembayaran if Lunas -->
                                                    <Link
                                                        v-if="isLunas(t) && t.last_pembayaran_id"
                                                        :href="route('mahasiswa.pembayaran.show', t.last_pembayaran_id)"
                                                        class="action-icon-btn btn-receipt"
                                                        title="Lihat Bukti Transaksi Pembayaran"
                                                    >
                                                        <i class="fas fa-receipt"></i>
                                                    </Link>

                                                    <!-- Invoice PDF if Lunas -->
                                                    <a
                                                        v-if="getInvoiceLink(t)"
                                                        :href="getInvoiceLink(t)"
                                                        target="_blank"
                                                        class="action-icon-btn btn-invoice"
                                                        title="Buka / Unduh Invoice PDF Resmi"
                                                    >
                                                        <i class="fas fa-file-invoice"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- MOBILE CARDS (Visible on Phone/Tablet) -->
                            <div class="mobile-cards-list">
                                <div
                                    v-for="t in tagihans.data"
                                    :key="'m-' + t.id"
                                    class="mobile-tagihan-card"
                                    :class="{ 'card-is-paid': isLunas(t) }"
                                >
                                    <!-- Header -->
                                    <div class="mobile-tagihan-header">
                                        <div class="sem-badge-chip">Semester {{ t.semester }}</div>
                                        <span class="badge-solid" :class="getTagihanStatus(t).badgeClass">
                                            <span class="pulse-dot" :style="{ backgroundColor: getTagihanStatus(t).dotColor }"></span>
                                            {{ getTagihanStatus(t).shortLabel }}
                                        </span>
                                    </div>

                                    <!-- Body -->
                                    <div class="mobile-tagihan-body">
                                        <div class="mobile-ta-label">{{ t.tahun_akademik }}</div>
                                        <div class="mobile-amount-val">{{ formatRupiah(t.nominal) }}</div>

                                        <!-- Mobile Active VA Strip -->
                                        <div v-if="t.pending_pembayaran && !isLunas(t)" class="mobile-active-va-box">
                                            <div class="m-va-lbl-row">
                                                <i class="fas fa-university text-amber"></i>
                                                <span>VA Aktif ({{ t.pending_pembayaran.metode_pembayaran_nama }}):</span>
                                            </div>
                                            <div class="m-va-val-row">
                                                <span class="m-va-num font-mono">{{ t.pending_pembayaran.va_number }}</span>
                                                <button
                                                    type="button"
                                                    class="btn-copy-va-pill"
                                                    @click="copyVa(t.pending_pembayaran.va_number, 'm-va-' + t.id)"
                                                >
                                                    <i :class="copiedVaId === 'm-va-' + t.id ? 'fas fa-check text-emerald' : 'far fa-copy'"></i>
                                                    <span>{{ copiedVaId === 'm-va-' + t.id ? 'Tersalin' : 'Salin' }}</span>
                                                </button>
                                            </div>
                                        </div>

                                        <div v-if="t.beasiswa" class="beasiswa-tag-pill" style="margin-bottom:0.75rem;">
                                            <i class="fas fa-graduation-cap"></i>
                                            <span>{{ t.beasiswa.nama }} (-{{ formatRupiah(t.beasiswa.diskon) }})</span>
                                        </div>

                                        <div class="mobile-info-strip">
                                            <div class="m-info-item">
                                                <span class="m-info-lbl">Jatuh Tempo:</span>
                                                <span class="m-info-val">{{ formatDate(t.jatuh_tempo) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Footer Action Buttons -->
                                    <div class="mobile-tagihan-footer">
                                        <!-- If VA is active: Selesaikan VA Link -->
                                        <Link
                                            v-if="!isLunas(t) && t.pending_pembayaran && t.status !== 'dispen'"
                                            :href="route('mahasiswa.pembayaran.show', t.pending_pembayaran.id)"
                                            class="mobile-btn-va-pending"
                                        >
                                            <i class="fas fa-clock"></i> Selesaikan Pembayaran VA
                                        </Link>

                                        <!-- If No VA: Bayar Instan Button -->
                                        <button
                                            v-else-if="!isLunas(t) && t.status !== 'dispen'"
                                            type="button"
                                            class="mobile-btn-pay"
                                            @click="openQuickPay(t)"
                                        >
                                            <i class="fas fa-bolt text-amber-300"></i>
                                            <span>Bayar Instan UKT</span>
                                        </button>

                                        <div v-if="isLunas(t)" class="mobile-paid-actions">
                                            <Link
                                                v-if="t.last_pembayaran_id"
                                                :href="route('mahasiswa.pembayaran.show', t.last_pembayaran_id)"
                                                class="solid-btn btn-white-border"
                                                style="flex:1; justify-content:center;"
                                            >
                                                <i class="fas fa-receipt"></i> Bukti
                                            </Link>
                                            <a
                                                v-if="getInvoiceLink(t)"
                                                :href="getInvoiceLink(t)"
                                                target="_blank"
                                                class="solid-btn btn-indigo"
                                                style="flex:1; justify-content:center;"
                                            >
                                                <i class="fas fa-file-invoice"></i> Invoice PDF
                                            </a>
                                        </div>

                                        <Link
                                            v-if="canDispensasi(t)"
                                            :href="route('mahasiswa.dispensasi.index')"
                                            class="mobile-btn-dispen"
                                        >
                                            <i class="fas fa-hand-holding-usd"></i> Ajukan Dispensasi
                                        </Link>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. PAGINATION FOOTER -->
                            <div v-if="tagihans.links && tagihans.links.length > 3" class="pagination-bar">
                                <div class="pagination-info-text">
                                    Menampilkan <span class="fw-bold">{{ tagihans.from || 0 }}</span> sampai
                                    <span class="fw-bold">{{ tagihans.to || 0 }}</span> dari
                                    <span class="fw-bold">{{ tagihans.total || 0 }}</span> total data tagihan
                                </div>

                                <div class="pagination-controls">
                                    <template v-for="(link, idx) in tagihans.links" :key="idx">
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

        <!-- 1-Click Quick Pay Slide-over Component (Preserved & Integrated) -->
        <QuickPayDrawer
            :show="showQuickPay"
            :tagihan="selectedTagihanForPay"
            :metode-pembayarans="metodePembayarans"
            :mahasiswa="mahasiswa"
            :va-expired-at="vaExpiredAt"
            @update:show="showQuickPay = $event"
            @payment-success="handleDrawerSuccess"
            @status-changed="handleDrawerSuccess"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
/* =======================================================
   MAHASISWA TAGIHAN UKT - EXECUTIVE REDESIGN
   ======================================================= */

/* --- Page Header --- */
.tagihan-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.tagihan-header-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    margin: 0;
}

.tagihan-header-subtitle {
    font-size: 0.84rem;
    color: #64748b;
    margin: 0.25rem 0 0;
}

.header-action-btns {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-wrap: wrap;
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
.btn-indigo:hover {
    background: #4338ca;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
    transform: translateY(-1px);
}

.btn-amber-fill {
    background: #d97706;
    color: #ffffff;
    white-space: nowrap;
}
.btn-amber-fill:hover {
    background: #b45309;
    box-shadow: 0 4px 12px rgba(217, 119, 6, 0.25);
    transform: translateY(-1px);
}

/* --- Colors & Icons --- */
.text-indigo { color: #4f46e5 !important; }
.text-emerald { color: #059669 !important; }
.text-rose { color: #e11d48 !important; }
.text-amber { color: #d97706 !important; }
.text-slate { color: #64748b !important; }
.text-danger { color: #dc2626 !important; }
.fw-bold { font-weight: 700; }
.font-mono { font-family: 'SF Mono', 'Cascadia Code', 'Fira Code', 'Consolas', monospace; }

.bg-emerald { background-color: #10b981 !important; }
.bg-indigo-light { background: #eef2ff; }
.bg-emerald-light { background: #ecfdf5; }
.bg-rose-light { background: #fff1f2; }
.bg-amber-light { background: #fffbeb; }

/* --- 1. Academic Semester Hero Banner --- */
.academic-hero-banner {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
    color: #ffffff;
    border-radius: 1rem;
    padding: 1.5rem 1.75rem;
    margin-bottom: 1rem;
    box-shadow: 0 8px 24px rgba(30, 27, 75, 0.2);
}

.academic-hero-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.25rem;
}

.hero-left-info { flex: 1; min-width: 280px; }

.hero-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: rgba(255, 255, 255, 0.15);
    padding: 0.25rem 0.65rem;
    border-radius: 1rem;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    margin-bottom: 0.5rem;
}

.hero-ta-title {
    font-size: 1.35rem;
    font-weight: 800;
    margin: 0 0 0.35rem;
    letter-spacing: -0.01em;
}

.hero-student-meta {
    font-size: 0.8125rem;
    opacity: 0.85;
    margin: 0;
}

.hero-right-deadline {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 0.85rem 1.25rem;
    border-radius: 0.75rem;
    backdrop-filter: blur(4px);
}

.deadline-icon-box {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.6rem;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}

.deadline-label {
    font-size: 0.68rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    opacity: 0.8;
}

.deadline-date-val {
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: -0.01em;
}

.deadline-relative {
    margin-top: 0.2rem;
}

.badge-time {
    font-size: 0.72rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    background: #ffffff;
    padding: 0.15rem 0.45rem;
    border-radius: 0.35rem;
}

/* --- 1B. Pending VA Alert Card --- */
.pending-va-alert-card {
    background: #fffbeb;
    border: 1.5px solid #fde68a;
    border-radius: 0.95rem;
    padding: 1rem 1.35rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.08);
}

.alert-icon-wrap {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.65rem;
    background: #fef3c7;
    color: #d97706;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.alert-text-content {
    flex: 1;
    min-width: 260px;
}

.alert-top-badge {
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    color: #b45309;
    margin-bottom: 0.15rem;
}

.alert-msg {
    font-size: 0.84rem;
    color: #92400e;
    line-height: 1.45;
}

.active-va-inline {
    background: #ffffff;
    border: 1px solid #fcd34d;
    padding: 0.1rem 0.4rem;
    border-radius: 0.35rem;
    color: #0f172a;
    margin: 0 0.2rem;
}

.active-bank-inline {
    font-weight: 600;
    color: #b45309;
}



/* --- 3. Main Card Container --- */
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
.tab-pill-count.count-rose { background: #ffe4e6; color: #be123c; }
.tab-pill-count.count-emerald { background: #dcfce7; color: #166534; }

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

/* --- Table Styles --- */
.table-container { width: 100%; }

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

.table-row-hover:hover td { background-color: #fafbfc; }

.row-is-paid td {
    background-color: #fafdfb;
}

/* Semester Chip & Ident */
.semester-ident-box {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.sem-badge-chip {
    font-size: 0.85rem;
    font-weight: 800;
    color: #0f172a;
}

.ta-year-text {
    font-size: 0.75rem;
    color: #64748b;
}

/* Nominal & Active VA Strip */
.nominal-text {
    font-size: 1.05rem;
    font-weight: 800;
    color: #4f46e5;
    letter-spacing: -0.01em;
}

.active-va-strip {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    background: #fffbeb;
    border: 1px solid #fde68a;
    padding: 0.2rem 0.5rem;
    border-radius: 0.4rem;
    margin-top: 0.35rem;
}

.active-va-info {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.active-va-code {
    font-size: 0.78rem;
    font-weight: 800;
    color: #92400e;
}

.btn-copy-va-pill {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 0.3rem;
    padding: 0.1rem 0.35rem;
    font-size: 0.68rem;
    font-weight: 700;
    color: #334155;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    transition: all 0.15s;
}
.btn-copy-va-pill:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.beasiswa-tag-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    margin-top: 0.25rem;
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
    padding: 0.15rem 0.5rem;
    border-radius: 0.35rem;
    font-size: 0.7rem;
    font-weight: 700;
}

/* Deadline Cell */
.deadline-cell {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.deadline-main {
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.deadline-sub {
    font-size: 0.72rem;
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
}

.badge-solid-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.badge-solid-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.badge-solid-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
.badge-solid-dispen { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }

/* Table Actions */
.table-actions-group {
    display: inline-flex;
    gap: 0.4rem;
    align-items: center;
}

.action-btn-pay {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.45rem 0.85rem;
    border-radius: 0.5rem;
    font-size: 0.78rem;
    font-weight: 700;
    background: #4f46e5;
    color: #ffffff;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}
.action-btn-pay:hover {
    background: #4338ca;
    box-shadow: 0 4px 10px rgba(79, 70, 229, 0.25);
    transform: translateY(-1px);
}

.action-btn-va-pending {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.45rem 0.85rem;
    border-radius: 0.5rem;
    font-size: 0.78rem;
    font-weight: 700;
    background: #d97706;
    color: #ffffff;
    text-decoration: none;
    transition: all 0.2s;
    white-space: nowrap;
}
.action-btn-va-pending:hover {
    background: #b45309;
    box-shadow: 0 4px 10px rgba(217, 119, 6, 0.25);
    transform: translateY(-1px);
}

.action-icon-btn {
    width: 2.15rem;
    height: 2.15rem;
    border-radius: 0.5rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.825rem;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s ease;
    border: 1px solid transparent;
}

.btn-dispensasi {
    background: #fffbeb;
    color: #d97706;
    border-color: #fde68a;
}
.btn-dispensasi:hover {
    background: #fef3c7;
    color: #b45309;
    transform: translateY(-1px);
}

.btn-receipt {
    background: #f1f5f9;
    color: #334155;
    border-color: #e2e8f0;
}
.btn-receipt:hover {
    background: #e2e8f0;
    color: #0f172a;
    transform: translateY(-1px);
}

.btn-invoice {
    background: #eef2ff;
    color: #4f46e5;
    border-color: #c7d2fe;
}
.btn-invoice:hover {
    background: #4f46e5;
    color: #ffffff;
    transform: translateY(-1px);
}

/* --- Mobile Card Layout --- */
.desktop-view-only { display: block; }
.mobile-cards-list { display: none; }

.mobile-tagihan-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 0.9rem;
    padding: 1.15rem;
    margin: 0 0 0.85rem 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.mobile-tagihan-card.card-is-paid {
    background: #fafffc;
    border-color: #bbf7d0;
}

.mobile-tagihan-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.65rem;
}

.mobile-ta-label {
    font-size: 0.75rem;
    color: #64748b;
}

.mobile-amount-val {
    font-size: 1.35rem;
    font-weight: 800;
    color: #4f46e5;
    margin: 0.15rem 0 0.5rem;
}

.mobile-active-va-box {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
    margin-bottom: 0.65rem;
}

.m-va-lbl-row {
    font-size: 0.7rem;
    font-weight: 700;
    color: #92400e;
    display: flex;
    align-items: center;
    gap: 0.35rem;
    margin-bottom: 0.25rem;
}

.m-va-val-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.m-va-num {
    font-size: 1rem;
    font-weight: 900;
    color: #78350f;
}

.mobile-info-strip {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 0.65rem;
    margin-bottom: 0.85rem;
}

.m-info-item {
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
}
.m-info-lbl { color: #64748b; }
.m-info-val { font-weight: 700; color: #1e293b; }

.mobile-tagihan-footer {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding-top: 0.75rem;
    border-top: 1px solid #f1f5f9;
}

.mobile-btn-pay {
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    padding: 0.65rem 1rem;
    border-radius: 0.55rem;
    font-size: 0.85rem;
    font-weight: 700;
    background: #4f46e5;
    color: #ffffff;
    border: none;
    cursor: pointer;
    min-height: 44px;
}

.mobile-btn-va-pending {
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    padding: 0.65rem 1rem;
    border-radius: 0.55rem;
    font-size: 0.85rem;
    font-weight: 700;
    background: #d97706;
    color: #ffffff;
    text-decoration: none;
    min-height: 44px;
}

.mobile-paid-actions {
    display: flex;
    gap: 0.5rem;
}

.mobile-paid-actions .solid-btn {
    min-height: 42px;
}

.mobile-btn-dispen {
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    padding: 0.55rem 0.85rem;
    border-radius: 0.55rem;
    font-size: 0.78rem;
    font-weight: 600;
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
    text-decoration: none;
    min-height: 40px;
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

.pagination-info-text { font-size: 0.8125rem; color: #64748b; }
.pagination-controls { display: inline-flex; gap: 0.3rem; align-items: center; }

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

/* --- Responsive Media Queries --- */
@media (max-width: 768px) {
    .desktop-view-only { display: none; }
    .mobile-cards-list { display: block; padding: 0.75rem; }

    .tagihan-page-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }

    .tagihan-header-title {
        font-size: 1.2rem;
    }

    .header-action-btns {
        width: 100%;
    }
    .header-action-btns .solid-btn {
        flex: 1;
        justify-content: center;
        min-height: 40px;
    }

    .academic-hero-banner {
        padding: 1.15rem;
        border-radius: 0.85rem;
    }

    .hero-ta-title {
        font-size: 1.2rem;
    }

    .academic-hero-content {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }

    .hero-right-deadline {
        width: 100%;
        padding: 0.75rem 1rem;
    }

    .pending-va-alert-card {
        flex-direction: column;
        align-items: stretch;
        padding: 1rem;
        gap: 0.85rem;
    }

    .pending-va-alert-card .solid-btn {
        width: 100%;
        justify-content: center;
        min-height: 44px;
    }

    .card-controls-bar {
        flex-direction: column;
        align-items: stretch;
        padding: 0.85rem 1rem;
        gap: 0.75rem;
    }

    .search-input-wrap {
        width: 100%;
    }

    .filter-tab-pills {
        display: flex;
        gap: 0.35rem;
        overflow-x: auto;
        white-space: nowrap;
        width: 100%;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 2px;
    }
    .tab-pill {
        flex-shrink: 0;
        padding: 0.45rem 0.85rem;
        min-height: 36px;
    }

    .pagination-bar {
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
        text-align: center;
    }
}
</style>

