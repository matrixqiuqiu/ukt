<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { formatRupiah, formatDate } from '@/utils';
import QuickPayDrawer from '@/Components/QuickPayDrawer.vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            totalTagihan: 0,
            sudahBayar: 0,
            belumBayar: 0,
            nominalLunas: 0,
            nominalBelumBayar: 0,
        }),
    },
    tagihans: {
        type: Array,
        default: () => [],
    },
    activeTagihan: {
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
    semesterAktif: {
        type: Object,
        default: () => null,
    },
    vaExpiredAt: {
        type: String,
        default: '',
    },
});

const showQuickPay = ref(false);
const selectedTagihanForPay = ref(null);
const activeFilter = ref('semua'); // 'semua' | 'belum_bayar' | 'lunas'

const openQuickPay = (tagihan) => {
    selectedTagihanForPay.value = tagihan || props.activeTagihan || props.tagihans[0] || null;
    if (selectedTagihanForPay.value) {
        showQuickPay.value = true;
    }
};

const filteredTagihans = computed(() => {
    if (!props.tagihans || !props.tagihans.length) return [];
    if (activeFilter.value === 'belum_bayar') {
        return props.tagihans.filter(t => t.status !== 'sudah_dibayar');
    }
    if (activeFilter.value === 'lunas') {
        return props.tagihans.filter(t => t.status === 'sudah_dibayar');
    }
    return props.tagihans;
});

const isHeroPaid = computed(() => {
    return props.activeTagihan?.status === 'sudah_dibayar';
});

const heroHasPendingVa = computed(() => {
    return props.activeTagihan?.pending_pembayaran && props.activeTagihan?.status !== 'sudah_dibayar';
});

const dueDaysRemaining = computed(() => {
    if (!props.activeTagihan?.jatuh_tempo_raw) return null;
    const target = new Date(props.activeTagihan.jatuh_tempo_raw).getTime();
    const now = Date.now();
    return Math.ceil((target - now) / (1000 * 60 * 60 * 24));
});

const copiedVa = ref(false);
const copyHeroVa = (num) => {
    if (!num) return;
    navigator.clipboard.writeText(num);
    copiedVa.value = true;
    setTimeout(() => { copiedVa.value = false; }, 2000);
};

const handleDrawerSuccess = () => {
    router.reload({ only: ['tagihans', 'activeTagihan', 'stats'] });
};
</script>

<template>
    <Head title="Dashboard Mahasiswa" />
    <AuthenticatedLayout>
        <div class="page-body">
            <div class="container-xl">
                <!-- 1. Header & Identity -->
                <div class="dashboard-header">
                    <div>
                        <h2 class="user-greeting">Halo, {{ mahasiswa?.nama_lengkap || $page.props.auth.user?.name }}</h2>
                        <div class="user-meta-row">
                            <span class="meta-item"><i class="fas fa-id-card"></i> NIM: <strong>{{ mahasiswa?.nim || '-' }}</strong></span>
                            <span class="meta-item"><i class="fas fa-graduation-cap"></i> {{ mahasiswa?.jurusan || 'Program Studi' }}</span>
                            <span v-if="mahasiswa?.semester" class="meta-item"><i class="fas fa-layer-group"></i> Semester {{ mahasiswa?.semester }}</span>
                        </div>
                    </div>
                    <div v-if="semesterAktif" class="academic-badge">
                        <span class="academic-label">Tahun Akademik</span>
                        <span class="academic-value">{{ semesterAktif.tahun_akademik }}</span>
                    </div>
                </div>

                <!-- 2. Main Bill / Tagihan Aktif Card -->
                <div v-if="activeTagihan" class="bill-hero-card" :class="{ 'is-paid': isHeroPaid }">
                    <div class="bill-hero-content">
                        <div class="bill-hero-info">
                            <div class="bill-badge-row">
                                <span class="semester-tag">Semester {{ activeTagihan.semester }} · {{ activeTagihan.tahun_akademik }}</span>
                                <span
                                    class="m-badge"
                                    :class="{
                                        'm-badge-success': isHeroPaid,
                                        'm-badge-dispen': activeTagihan.status === 'dispen',
                                        'm-badge-warning': !isHeroPaid && activeTagihan.status !== 'dispen'
                                    }"
                                >
                                    <i v-if="isHeroPaid" class="fas fa-check-circle" style="margin-right:0.25rem;"></i>
                                    <i v-else-if="heroHasPendingVa" class="fas fa-clock" style="margin-right:0.25rem;"></i>
                                    {{ isHeroPaid ? 'Lunas' : (heroHasPendingVa ? 'Menunggu Pembayaran VA' : (activeTagihan.status === 'dispen' ? 'Dispensasi' : 'Belum Dibayar')) }}
                                </span>
                            </div>

                            <div class="bill-amount-section">
                                <div class="bill-label">Total Tagihan UKT</div>
                                <div class="bill-value">{{ formatRupiah(activeTagihan.nominal) }}</div>
                            </div>

                            <div v-if="activeTagihan.beasiswa" class="beasiswa-info-line">
                                <i class="fas fa-tag"></i>
                                <span>Potongan Beasiswa <strong>{{ activeTagihan.beasiswa.nama }}</strong>: Rp {{ Number(activeTagihan.beasiswa.diskon).toLocaleString('id-ID') }}</span>
                            </div>

                            <!-- Pending VA Info Box -->
                            <div v-if="heroHasPendingVa" class="pending-va-box">
                                <div class="va-text">
                                    <span class="va-title">Virtual Account ({{ activeTagihan.pending_pembayaran.metode_pembayaran_nama }}):</span>
                                    <strong class="va-number font-mono">{{ activeTagihan.pending_pembayaran.va_number }}</strong>
                                </div>
                                <div style="display:flex;align-items:center;gap:0.35rem;">
                                    <button
                                        type="button"
                                        class="btn-copy-va-hero"
                                        :title="copiedVa ? 'Tersalin!' : 'Salin Nomor VA'"
                                        @click="copyHeroVa(activeTagihan.pending_pembayaran.va_number)"
                                    >
                                        <i :class="copiedVa ? 'fas fa-check text-success' : 'far fa-copy'"></i>
                                        <span>{{ copiedVa ? 'Tersalin' : 'Salin' }}</span>
                                    </button>
                                    <span class="va-status-tag">Menunggu Bayar</span>
                                </div>
                            </div>

                            <div class="due-info-row">
                                <span class="due-text">
                                    <i class="fas fa-calendar-alt"></i>
                                    Jatuh tempo: <strong>{{ activeTagihan.jatuh_tempo }}</strong>
                                    <template v-if="!isHeroPaid && dueDaysRemaining !== null">
                                        <span v-if="dueDaysRemaining <= 0" class="due-days text-danger font-bold" style="color:#dc2626;background:#fee2e2;padding:0.2rem 0.5rem;border-radius:0.25rem;display:inline-flex;align-items:center;gap:0.25rem;">
                                            <i class="fas fa-exclamation-triangle"></i> Lewat jatuh tempo!
                                        </span>
                                        <span v-else-if="dueDaysRemaining <= 3" class="due-days font-bold" style="color:#b45309;background:#fef3c7;padding:0.2rem 0.5rem;border-radius:0.25rem;display:inline-flex;align-items:center;gap:0.25rem;">
                                            <i class="fas fa-hourglass-half"></i> Sisa {{ dueDaysRemaining }} hari lagi! Segera bayar
                                        </span>
                                        <span v-else class="due-days">
                                            ({{ dueDaysRemaining }} hari lagi)
                                        </span>
                                    </template>
                                </span>
                            </div>
                        </div>

                        <!-- Action Button on Right -->
                        <div class="bill-hero-action">
                            <!-- If VA is already active: Link directly to View & Pay VA page -->
                            <Link
                                v-if="!isHeroPaid && heroHasPendingVa"
                                :href="route('mahasiswa.pembayaran.show', activeTagihan.pending_pembayaran.id)"
                                class="m-btn btn-amber-hero"
                                title="Selesaikan Pembayaran & Lihat Instruksi"
                            >
                                <i class="fas fa-clock"></i>
                                <span>Selesaikan Bayar (Lihat VA)</span>
                            </Link>

                            <!-- If No VA yet: 1-Click Pay Trigger -->
                            <button
                                v-else-if="!isHeroPaid"
                                type="button"
                                class="m-btn m-btn-primary btn-pay-hero"
                                @click="openQuickPay(activeTagihan)"
                            >
                                <i class="fas fa-bolt"></i>
                                <span>Bayar Sekarang</span>
                            </button>

                            <div v-else class="paid-hero-buttons">
                                <Link
                                    v-if="activeTagihan.last_pembayaran_id"
                                    :href="route('mahasiswa.pembayaran.show', activeTagihan.last_pembayaran_id)"
                                    class="m-btn m-btn-secondary"
                                >
                                    <i class="fas fa-receipt"></i> Bukti Bayar
                                </Link>
                                <a
                                    :href="route('mahasiswa.tagihan.invoice', activeTagihan.id)"
                                    target="_blank"
                                    class="m-btn m-btn-outline"
                                >
                                    <i class="fas fa-file-invoice"></i> Invoice PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Quick Actions -->
                <div class="shortcuts-row">
                    <Link
                        v-if="!isHeroPaid && heroHasPendingVa"
                        :href="route('mahasiswa.pembayaran.show', activeTagihan.pending_pembayaran.id)"
                        class="shortcut-btn btn-amber-shortcut"
                    >
                        <i class="fas fa-clock"></i>
                        <span>Selesaikan Pembayaran (VA Aktif)</span>
                    </Link>
                    <button
                        v-else-if="!isHeroPaid"
                        type="button"
                        class="shortcut-btn btn-primary-shortcut"
                        @click="openQuickPay(activeTagihan)"
                    >
                        <i class="fas fa-bolt"></i>
                        <span>Bayar UKT Instan</span>
                    </button>
                    <Link :href="route('mahasiswa.tagihan.index')" class="shortcut-btn">
                        <i class="fas fa-list"></i>
                        <span>Daftar Semua Tagihan</span>
                    </Link>
                    <Link :href="route('mahasiswa.riwayat.index')" class="shortcut-btn">
                        <i class="fas fa-history"></i>
                        <span>Riwayat Pembayaran</span>
                    </Link>
                    <Link :href="route('mahasiswa.dispensasi.index')" class="shortcut-btn">
                        <i class="fas fa-file-signature"></i>
                        <span>Ajukan Dispensasi</span>
                    </Link>
                </div>

                <!-- 5. Tagihan Table & History Feed -->
                <div class="custom-card">
                    <div class="card-header-clean">
                        <div class="header-left">
                            <h3 class="card-title">Daftar Tagihan UKT</h3>
                        </div>

                        <!-- Clean Pill Filter -->
                        <div class="filter-group">
                            <button
                                type="button"
                                class="filter-tab"
                                :class="{ active: activeFilter === 'semua' }"
                                @click="activeFilter = 'semua'"
                            >
                                Semua ({{ tagihans.length }})
                            </button>
                            <button
                                type="button"
                                class="filter-tab"
                                :class="{ active: activeFilter === 'belum_bayar' }"
                                @click="activeFilter = 'belum_bayar'"
                            >
                                Belum Lunas ({{ stats.belumBayar }})
                            </button>
                            <button
                                type="button"
                                class="filter-tab"
                                :class="{ active: activeFilter === 'lunas' }"
                                @click="activeFilter = 'lunas'"
                            >
                                Lunas ({{ stats.sudahBayar }})
                            </button>
                        </div>
                    </div>

                    <div class="card-body" style="padding:0;">
                        <div v-if="filteredTagihans.length > 0">
                            <!-- Desktop Table -->
                            <div class="table-responsive desktop-only">
                                <table class="m-data-table">
                                    <thead>
                                        <tr>
                                            <th>Semester</th>
                                            <th>Tahun Akademik</th>
                                            <th>Nominal</th>
                                            <th>Jatuh Tempo</th>
                                            <th>Status</th>
                                            <th style="text-align:right;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="t in filteredTagihans"
                                            :key="t.id"
                                            :class="{ 'row-paid': t.status === 'sudah_dibayar' }"
                                        >
                                            <td>
                                                <span class="m-badge m-badge-secondary">Smt {{ t.semester }}</span>
                                                <div v-if="t.beasiswa" class="m-badge m-badge-success" style="font-size:0.65rem;margin-top:0.25rem;display:inline-flex;gap:0.25rem;">
                                                    <i class="fas fa-graduation-cap"></i> {{ t.beasiswa.kode }}
                                                </div>
                                            </td>
                                            <td><strong>{{ t.tahun_akademik }}</strong></td>
                                            <td>
                                                <div style="font-weight:700;">{{ formatRupiah(t.nominal) }}</div>
                                                <div v-if="t.beasiswa" style="font-size:0.75rem;color:#059669;">
                                                    Potongan: Rp {{ Number(t.beasiswa.diskon).toLocaleString('id-ID') }}
                                                </div>
                                            </td>
                                            <td>{{ t.jatuh_tempo }}</td>
                                            <td>
                                                <span
                                                    class="m-badge"
                                                    :class="{
                                                        'm-badge-success': t.status === 'sudah_dibayar',
                                                        'm-badge-dispen': t.status === 'dispen',
                                                        'm-badge-warning': t.status !== 'sudah_dibayar' && t.status !== 'dispen'
                                                    }"
                                                >
                                                    <i v-if="t.status === 'sudah_dibayar'" class="fas fa-check-circle" style="margin-right:0.25rem;"></i>
                                                    <i v-else-if="t.pending_pembayaran" class="fas fa-clock" style="margin-right:0.25rem;"></i>
                                                    {{ t.status === 'sudah_dibayar' ? 'Lunas' : (t.status === 'dispen' ? 'Dispensasi' : (t.pending_pembayaran ? 'Menunggu Bayar' : 'Belum Lunas')) }}
                                                </span>
                                            </td>
                                            <td style="text-align:right;">
                                                <div style="display:inline-flex;gap:0.375rem;">
                                                    <!-- If VA is active: Link directly to View & Pay VA page -->
                                                    <Link
                                                        v-if="t.status !== 'sudah_dibayar' && t.pending_pembayaran && t.status !== 'dispen'"
                                                        :href="route('mahasiswa.pembayaran.show', t.pending_pembayaran.id)"
                                                        class="m-btn m-btn-sm btn-amber-table"
                                                        title="Lihat Nomor VA & Instruksi Pembayaran"
                                                    >
                                                        <i class="fas fa-clock"></i>
                                                        <span>Selesaikan VA</span>
                                                    </Link>

                                                    <!-- If No VA yet: 1-Click Pay Trigger -->
                                                    <button
                                                        v-else-if="t.status !== 'sudah_dibayar' && t.status !== 'dispen'"
                                                        type="button"
                                                        class="m-btn m-btn-sm m-btn-primary"
                                                        @click="openQuickPay(t)"
                                                    >
                                                        <i class="fas fa-bolt"></i>
                                                        <span>Bayar</span>
                                                    </button>

                                                    <template v-else-if="t.status === 'sudah_dibayar'">
                                                        <Link
                                                            v-if="t.last_pembayaran_id"
                                                            :href="route('mahasiswa.pembayaran.show', t.last_pembayaran_id)"
                                                            class="m-btn m-btn-sm m-btn-secondary"
                                                        >
                                                            <i class="fas fa-receipt"></i> Bukti
                                                        </Link>
                                                        <a
                                                            :href="route('mahasiswa.tagihan.invoice', t.id)"
                                                            target="_blank"
                                                            class="m-btn m-btn-sm m-btn-outline"
                                                        >
                                                            <i class="fas fa-file-invoice"></i> Invoice
                                                        </a>
                                                    </template>

                                                    <Link
                                                        v-else-if="t.status === 'dispen'"
                                                        :href="route('mahasiswa.dispensasi.index')"
                                                        class="m-btn m-btn-sm m-btn-secondary"
                                                    >
                                                        Detail Dispen
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Mobile Cards Feed -->
                            <div class="mobile-cards">
                                <div
                                    v-for="t in filteredTagihans"
                                    :key="t.id"
                                    class="m-card-mobile"
                                    :class="{ 'is-paid': t.status === 'sudah_dibayar' }"
                                >
                                    <div class="m-card-mobile-head">
                                        <span class="m-badge m-badge-secondary">Smt {{ t.semester }} · {{ t.tahun_akademik }}</span>
                                        <span
                                            class="m-badge"
                                            :class="{
                                                'm-badge-success': t.status === 'sudah_dibayar',
                                                'm-badge-dispen': t.status === 'dispen',
                                                'm-badge-warning': t.status !== 'sudah_dibayar' && t.status !== 'dispen'
                                            }"
                                        >
                                            {{ t.status === 'sudah_dibayar' ? 'Lunas' : (t.status === 'dispen' ? 'Dispen' : (t.pending_pembayaran ? 'Menunggu Bayar' : 'Belum Lunas')) }}
                                        </span>
                                    </div>

                                    <div class="mobile-amount">{{ formatRupiah(t.nominal) }}</div>

                                    <div v-if="t.beasiswa" style="font-size:0.75rem;color:#059669;margin-bottom:0.375rem;">
                                        <i class="fas fa-graduation-cap"></i> {{ t.beasiswa.nama }} (-Rp {{ Number(t.beasiswa.diskon).toLocaleString('id-ID') }})
                                    </div>

                                    <div style="font-size:0.75rem;color:var(--gray-600);margin-bottom:0.75rem;">
                                        <i class="fas fa-clock"></i> Jatuh tempo: {{ t.jatuh_tempo }}
                                    </div>

                                    <div style="display:flex;gap:0.5rem;">
                                        <!-- If VA is active: Link directly to View & Pay VA page -->
                                        <Link
                                            v-if="t.status !== 'sudah_dibayar' && t.pending_pembayaran && t.status !== 'dispen'"
                                            :href="route('mahasiswa.pembayaran.show', t.pending_pembayaran.id)"
                                            class="m-btn m-btn-sm btn-amber-table"
                                            style="width:100%;justify-content:center;"
                                        >
                                            <i class="fas fa-clock"></i>
                                            <span>Selesaikan Bayar (Lihat VA)</span>
                                        </Link>

                                        <!-- If No VA yet: 1-Click Pay Trigger -->
                                        <button
                                            v-else-if="t.status !== 'sudah_dibayar' && t.status !== 'dispen'"
                                            type="button"
                                            class="m-btn m-btn-sm m-btn-primary"
                                            style="width:100%;justify-content:center;"
                                            @click="openQuickPay(t)"
                                        >
                                            <i class="fas fa-bolt"></i>
                                            <span>Bayar Sekarang</span>
                                        </button>

                                        <template v-else-if="t.status === 'sudah_dibayar'">
                                            <Link
                                                v-if="t.last_pembayaran_id"
                                                :href="route('mahasiswa.pembayaran.show', t.last_pembayaran_id)"
                                                class="m-btn m-btn-sm m-btn-secondary"
                                                style="flex:1;justify-content:center;"
                                            >
                                                <i class="fas fa-receipt"></i> Bukti
                                            </Link>
                                            <a
                                                :href="route('mahasiswa.tagihan.invoice', t.id)"
                                                target="_blank"
                                                class="m-btn m-btn-sm m-btn-outline"
                                                style="flex:1;justify-content:center;"
                                            >
                                                <i class="fas fa-file-invoice"></i> Invoice
                                            </a>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else style="text-align:center;padding:3rem 1rem;color:var(--gray-600);">
                            <i class="fas fa-folder-open" style="font-size:2rem;color:var(--gray-300);margin-bottom:0.5rem;display:block;"></i>
                            <p style="margin:0;">Tidak ada tagihan dalam filter ini.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 1-Click Quick Pay Drawer -->
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
/* Header */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.user-greeting {
    font-size: 1.375rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 0.25rem;
}

.user-meta-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.875rem;
    font-size: 0.8125rem;
    color: #475569;
}

.meta-item {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
}

.academic-badge {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 0.5rem 1rem;
    text-align: right;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}

.academic-label {
    display: block;
    font-size: 0.6875rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
}

.academic-value {
    font-size: 0.875rem;
    font-weight: 700;
    color: #0f172a;
}

/* Main Bill Hero Card - Solid White Background */
.bill-hero-card {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.bill-hero-card.is-paid {
    background: #f0fdf4;
    border-color: #86efac;
}

.bill-hero-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.bill-hero-info {
    flex: 1;
    min-width: 260px;
}

.bill-badge-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.semester-tag {
    font-size: 0.75rem;
    font-weight: 700;
    background: #f1f5f9;
    color: #334155;
    padding: 0.25rem 0.625rem;
    border-radius: 0.375rem;
}

.bill-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 0.25rem;
}

.bill-value {
    font-size: 2rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
    margin-bottom: 0.5rem;
}

.beasiswa-info-line {
    font-size: 0.8125rem;
    color: #059669;
    margin-bottom: 0.5rem;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
}

.pending-va-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.5rem 0.875rem;
    margin-bottom: 0.75rem;
    max-width: 480px;
    gap: 0.5rem;
}

.va-text {
    font-size: 0.8125rem;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 0.375rem;
    flex-wrap: wrap;
}

.va-number {
    font-size: 0.9375rem;
    color: #4f46e5;
    font-weight: 700;
}

.va-status-tag {
    font-size: 0.6875rem;
    font-weight: 700;
    background: #fef3c7;
    color: #b45309;
    padding: 0.125rem 0.5rem;
    border-radius: 1rem;
}

.due-info-row {
    font-size: 0.8125rem;
    color: #475569;
}

.due-text {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
}

.due-days {
    color: #64748b;
}

.btn-pay-hero {
    padding: 0.75rem 1.5rem;
    font-size: 0.9375rem;
    font-weight: 700;
    border-radius: 0.5rem;
    background: #4f46e5;
    color: #ffffff;
    border: none;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.btn-pay-hero:hover {
    background: #4338ca;
}

.btn-amber-hero {
    padding: 0.75rem 1.5rem;
    font-size: 0.9375rem;
    font-weight: 700;
    border-radius: 0.5rem;
    background: #d97706;
    color: #ffffff;
    border: none;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 2px 8px rgba(217, 119, 6, 0.25);
    transition: all 0.15s ease;
}
.btn-amber-hero:hover {
    background: #b45309;
    color: #ffffff;
    transform: translateY(-1px);
}

.btn-amber-shortcut {
    background: #fffbeb !important;
    border-color: #fde68a !important;
    color: #b45309 !important;
    font-weight: 700 !important;
}
.btn-amber-shortcut:hover {
    background: #fef3c7 !important;
    border-color: #fcd34d !important;
}

.btn-amber-table {
    background: #fffbeb !important;
    border: 1px solid #fde68a !important;
    color: #b45309 !important;
    font-weight: 700 !important;
    text-decoration: none !important;
}
.btn-amber-table:hover {
    background: #d97706 !important;
    border-color: #d97706 !important;
    color: #ffffff !important;
}

.btn-copy-va-hero {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 0.375rem;
    padding: 0.2rem 0.5rem;
    font-size: 0.6875rem;
    font-weight: 700;
    color: #475569;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    transition: all 0.15s ease;
}
.btn-copy-va-hero:hover {
    background: #f1f5f9;
    color: #0f172a;
    border-color: #94a3b8;
}

.paid-hero-buttons {
    display: flex;
    gap: 0.5rem;
}

/* Shortcuts Row - Solid Buttons */
.shortcuts-row {
    display: flex;
    gap: 0.625rem;
    flex-wrap: wrap;
    margin-bottom: 1.25rem;
}

.shortcut-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #1e293b;
    text-decoration: none;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    transition: background 0.15s;
}

.shortcut-btn:hover {
    background: #f8fafc;
}

.btn-primary-shortcut {
    background: #4f46e5;
    border-color: #4338ca;
    color: #ffffff;
}

.btn-primary-shortcut:hover {
    background: #4338ca;
    color: #ffffff;
}

/* Custom Card & Table - Solid */
.custom-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    overflow: hidden;
}

.card-header-clean {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.card-title {
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.filter-group {
    display: flex;
    gap: 0.25rem;
    background: #f1f5f9;
    padding: 0.2rem;
    border-radius: 0.375rem;
}

.filter-tab {
    border: none;
    background: transparent;
    padding: 0.25rem 0.625rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all 0.15s;
}

.filter-tab.active {
    background: #ffffff;
    color: #4f46e5;
    font-weight: 700;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.desktop-only { display: block; }
.mobile-cards { display: none; }
.mobile-amount { font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-bottom: 0.35rem; }

.m-card-mobile {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 0.85rem;
    padding: 1.15rem;
    margin-bottom: 0.85rem;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
    transition: border-color 0.15s ease;
}

.m-card-mobile.is-paid {
    background: #fafffc;
    border-color: #bbf7d0;
}

.m-card-mobile-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.65rem;
}

.row-paid {
    background: #fcfdfd;
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
.m-badge-dispen { background: #dbeafe; color: #1d4ed8; }

.m-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.55rem 0.875rem;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all 0.15s ease;
    min-height: 38px;
}

.m-btn-primary { background: #4f46e5; color: #ffffff; }
.m-btn-primary:hover { background: #4338ca; }

.m-btn-secondary { background: #f1f5f9; color: #334155; border-color: #cbd5e1; }
.m-btn-secondary:hover { background: #e2e8f0; }

.m-btn-outline { background: transparent; border-color: #cbd5e1; color: #334155; }
.m-btn-outline:hover { background: #f8fafc; }

.m-btn-sm { padding: 0.45rem 0.75rem; font-size: 0.75rem; min-height: 36px; }

@media (max-width: 768px) {
    .desktop-only { display: none; }
    .mobile-cards { display: block; padding: 0.75rem; }
    
    .dashboard-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    .user-greeting {
        font-size: 1.25rem;
    }
    .user-meta-row {
        gap: 0.5rem;
        font-size: 0.75rem;
    }
    .academic-badge {
        display: flex;
        justify-content: space-between;
        align-items: center;
        text-align: left;
        padding: 0.5rem 0.85rem;
    }
    .academic-label {
        margin-bottom: 0;
    }
    
    .bill-hero-card {
        padding: 1.15rem;
        margin-bottom: 1rem;
    }
    .bill-value {
        font-size: 1.65rem;
    }
    .bill-hero-content { flex-direction: column; align-items: stretch; gap: 1rem; }
    .btn-pay-hero,
    .btn-amber-hero {
        width: 100%;
        justify-content: center;
        min-height: 44px;
    }
    .paid-hero-buttons { width: 100%; }
    .paid-hero-buttons .m-btn { flex: 1; justify-content: center; min-height: 42px; }
    
    .pending-va-box {
        max-width: 100%;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    .pending-va-box > div:last-child {
        width: 100%;
        justify-content: space-between;
    }
    
    .card-header-clean { flex-direction: column; align-items: flex-start; padding: 0.85rem 1rem; }
    .filter-group {
        width: 100%;
        overflow-x: auto;
        white-space: nowrap;
        scrollbar-width: none;
    }
    .filter-tab { flex: 1; text-align: center; min-height: 32px; }
}

@media (max-width: 640px) {
    .shortcuts-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    .shortcut-btn {
        width: 100%;
        justify-content: center;
        padding: 0.65rem 0.5rem;
        font-size: 0.75rem;
        flex-direction: column;
        text-align: center;
        gap: 0.35rem;
        min-height: 52px;
    }
    .btn-primary-shortcut,
    .btn-amber-shortcut {
        grid-column: span 2;
        flex-direction: row;
        padding: 0.75rem 1rem;
        font-size: 0.8125rem;
        min-height: 44px;
    }
}
</style>
