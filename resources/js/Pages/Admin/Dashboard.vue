<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { formatRupiah, formatDate } from '@/utils';
import { computed } from 'vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            totalMahasiswa: 0,
            pendingPayments: 0,
            confirmedPayments: 0,
            totalPendapatan: 0,
            totalTagihan: 0,
            unpaidTagihan: 0,
            dispensasiPending: 0,
            vaCount: 0,
            manualCount: 0,
            vaTotal: 0,
            manualTotal: 0,
        }),
    },
    recentPayments: {
        type: Array,
        default: () => [],
    },
    pendingVerifications: {
        type: Array,
        default: () => [],
    },
});

const vaPercent = computed(() => {
    const total = (props.stats.vaCount || 0) + (props.stats.manualCount || 0);
    if (total === 0) return 50;
    return Math.round((props.stats.vaCount / total) * 100);
});

const statusBadge = (status) => {
    const map = {
        'dikonfirmasi': { label: 'Lunas', class: 'm-badge-success', icon: 'fas fa-check-circle' },
        'pending': { label: 'Menunggu', class: 'm-badge-warning', icon: 'fas fa-clock' },
        'ditolak': { label: 'Ditolak', class: 'm-badge-danger', icon: 'fas fa-times-circle' },
        'expired': { label: 'Expired', class: 'm-badge-danger', icon: 'fas fa-hourglass-end' },
    };
    return map[status] || { label: status, class: 'm-badge-secondary', icon: 'fas fa-info-circle' };
};
</script>

<template>
    <Head title="Admin Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <div class="page-heading">
                <div>
                    <h2 class="page-heading-title">Dashboard Overview</h2>
                    <p class="page-heading-sub">Ringkasan transaksi, penerimaan UKT, dan antrean verifikasi</p>
                </div>
                <div class="header-actions">
                    <Link :href="route('admin.verifikasi.index')" class="btn-solid btn-warning-solid" v-if="stats.pendingPayments > 0">
                        <i class="fas fa-bell"></i>
                        <span>{{ stats.pendingPayments }} Perlu Verifikasi</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">
                <!-- 1. METRIC STATS (4 SOLID CLEAN CARDS) -->
                <div class="stats-row">
                    <!-- Total Mahasiswa -->
                    <div class="stat-box">
                        <div class="stat-meta">
                            <div class="stat-label">Total Mahasiswa</div>
                            <div class="stat-number">{{ stats.totalMahasiswa }}</div>
                            <div class="stat-hint">
                                <Link :href="route('admin.mahasiswa.index')" class="stat-link">Lihat semua &rarr;</Link>
                            </div>
                        </div>
                        <div class="stat-icon-wrapper bg-indigo-solid">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                    </div>

                    <!-- Total Pendapatan UKT -->
                    <div class="stat-box">
                        <div class="stat-meta">
                            <div class="stat-label">Penerimaan UKT</div>
                            <div class="stat-number text-success">{{ formatRupiah(stats.totalPendapatan) }}</div>
                            <div class="stat-hint text-success">
                                <i class="fas fa-check-circle"></i> {{ stats.confirmedPayments }} transaksi lunas
                            </div>
                        </div>
                        <div class="stat-icon-wrapper bg-emerald-solid">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>

                    <!-- Antrean Verifikasi -->
                    <div class="stat-box" :class="{ 'highlight-warning': stats.pendingPayments > 0 }">
                        <div class="stat-meta">
                            <div class="stat-label">Menunggu Verifikasi</div>
                            <div class="stat-number" :class="stats.pendingPayments > 0 ? 'text-amber' : ''">
                                {{ stats.pendingPayments }}
                            </div>
                            <div class="stat-hint">
                                <Link v-if="stats.pendingPayments > 0" :href="route('admin.verifikasi.index')" class="text-amber font-bold">
                                    Proses verifikasi &rarr;
                                </Link>
                                <span v-else class="text-muted">Semua bersih</span>
                            </div>
                        </div>
                        <div class="stat-icon-wrapper bg-amber-solid">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>

                    <!-- Tagihan Belum Lunas -->
                    <div class="stat-box">
                        <div class="stat-meta">
                            <div class="stat-label">Tagihan Belum Lunas</div>
                            <div class="stat-number text-danger">{{ stats.unpaidTagihan }}</div>
                            <div class="stat-hint">
                                <span v-if="stats.dispensasiPending > 0" class="text-blue font-semibold">
                                    <i class="fas fa-file-signature"></i> {{ stats.dispensasiPending }} dispen pending
                                </span>
                                <span v-else class="text-muted">dari {{ stats.totalTagihan }} tagihan</span>
                            </div>
                        </div>
                        <div class="stat-icon-wrapper bg-rose-solid">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>
                </div>

                <!-- 2. MAIN 2-COLUMN SECTION -->
                <div class="main-dashboard-grid">
                    <!-- LEFT: PEMBAYARAN TERBARU (7 COLS) -->
                    <div class="custom-card">
                        <div class="card-header-clean">
                            <div class="header-left">
                                <i class="fas fa-history text-primary"></i>
                                <h3 class="card-title">Pembayaran Terbaru</h3>
                            </div>
                            <Link :href="route('admin.pembayaran.index')" class="btn-link-action">
                                Lihat Semua <i class="fas fa-arrow-right"></i>
                            </Link>
                        </div>
                        <div class="card-body p-0">
                            <div v-if="recentPayments.length === 0" class="empty-state-clean">
                                <i class="fas fa-receipt"></i>
                                <p>Belum ada transaksi pembayaran.</p>
                            </div>
                            <div v-else class="table-responsive">
                                <table class="admin-data-table">
                                    <thead>
                                        <tr>
                                            <th>Waktu & ID</th>
                                            <th>Mahasiswa</th>
                                            <th>Nominal</th>
                                            <th>Metode</th>
                                            <th>Status</th>
                                            <th style="text-align:right;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="p in recentPayments" :key="p.id">
                                            <td>
                                                <div style="font-weight:600;color:#0f172a;">{{ formatDate(p.created_at) }}</div>
                                                <div style="font-size:0.6875rem;color:#64748b;">#{{ p.id }} · Smt {{ p.tagihan?.semester }}</div>
                                            </td>
                                            <td>
                                                <div style="font-weight:700;color:#0f172a;">{{ p.tagihan?.mahasiswa?.nama_lengkap }}</div>
                                                <div style="font-size:0.75rem;font-family:monospace;color:#64748b;">{{ p.tagihan?.mahasiswa?.nim }}</div>
                                            </td>
                                            <td>
                                                <div style="font-weight:700;color:#0f172a;">{{ formatRupiah(p.jumlah_bayar) }}</div>
                                            </td>
                                            <td>
                                                <span class="method-tag" :class="p.is_va ? 'tag-va' : 'tag-manual'">
                                                    <i :class="p.is_va ? 'fas fa-bolt' : 'fas fa-money-bill-transfer'"></i>
                                                    {{ p.metode_nama }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="m-badge" :class="statusBadge(p.status).class">
                                                    <i :class="statusBadge(p.status).icon" style="margin-right:0.25rem;"></i>
                                                    {{ statusBadge(p.status).label }}
                                                </span>
                                            </td>
                                            <td style="text-align:right;">
                                                <Link :href="route('admin.pembayaran.show', p.id)" class="btn-icon-detail" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </Link>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: QUICK VERIFICATION & PAYMENT METHOD BREAKDOWN (5 COLS) -->
                    <div class="dashboard-side-stack">
                        <!-- Antrean Verifikasi Manual Cepat -->
                        <div class="custom-card">
                            <div class="card-header-clean">
                                <div class="header-left">
                                    <i class="fas fa-check-double text-amber"></i>
                                    <h3 class="card-title">Antrean Verifikasi</h3>
                                    <span v-if="stats.pendingPayments > 0" class="counter-badge">{{ stats.pendingPayments }}</span>
                                </div>
                                <Link :href="route('admin.verifikasi.index')" class="btn-link-action">
                                    Buka Antrean &rarr;
                                </Link>
                            </div>
                            <div class="card-body" style="padding:0.75rem 1.25rem;">
                                <div v-if="pendingVerifications.length === 0" class="empty-verify-state">
                                    <i class="fas fa-check-circle text-success" style="font-size:1.75rem;margin-bottom:0.25rem;display:block;"></i>
                                    <span style="font-weight:600;color:#1e293b;">Semua Bersih</span>
                                    <p style="font-size:0.75rem;color:#64748b;margin:0;">Tidak ada pembayaran manual yang butuh verifikasi.</p>
                                </div>
                                <div v-else class="pending-list">
                                    <div v-for="item in pendingVerifications" :key="item.id" class="pending-item">
                                        <div class="pending-info">
                                            <div class="pending-name">{{ item.tagihan?.mahasiswa?.nama_lengkap }}</div>
                                            <div class="pending-meta">
                                                <span>{{ item.tagihan?.mahasiswa?.nim }}</span> · 
                                                <span>Smt {{ item.tagihan?.semester }}</span> · 
                                                <strong style="color:#0f172a;">{{ formatRupiah(item.jumlah_bayar) }}</strong>
                                            </div>
                                            <div style="font-size:0.6875rem;color:#64748b;margin-top:0.125rem;">
                                                Pengirim: <em>{{ item.nama_pengirim || '-' }}</em> ({{ item.metode_nama }})
                                            </div>
                                        </div>
                                        <Link :href="route('admin.verifikasi.index')" class="btn-solid-verify">
                                            Periksa
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ringkasan Jalur Pembayaran -->
                        <div class="custom-card">
                            <div class="card-header-clean">
                                <div class="header-left">
                                    <i class="fas fa-chart-pie text-primary"></i>
                                    <h3 class="card-title">Jalur Pembayaran</h3>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="split-bar-wrap">
                                    <div class="split-bar">
                                        <div class="split-segment va-segment" :style="{ width: vaPercent + '%' }" :title="'Virtual Account: ' + vaPercent + '%'"></div>
                                        <div class="split-segment tf-segment" :style="{ width: (100 - vaPercent) + '%' }" :title="'Transfer Manual: ' + (100 - vaPercent) + '%'"></div>
                                    </div>
                                    <div class="split-labels">
                                        <div class="split-item">
                                            <span class="dot dot-va"></span>
                                            <div>
                                                <div class="split-title">Virtual Account ({{ vaPercent }}%)</div>
                                                <div class="split-sub">{{ stats.vaCount }} transaksi · {{ formatRupiah(stats.vaTotal) }}</div>
                                            </div>
                                        </div>
                                        <div class="split-item">
                                            <span class="dot dot-tf"></span>
                                            <div>
                                                <div class="split-title">Transfer Manual ({{ 100 - vaPercent }}%)</div>
                                                <div class="split-sub">{{ stats.manualCount }} transaksi · {{ formatRupiah(stats.manualTotal) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. AKSI CEPAT / SHORTCUT TILES -->
                <div class="quick-actions-section">
                    <h3 class="section-title">Aksi Cepat & Navigasi Modul</h3>
                    <div class="action-tiles-grid">
                        <Link :href="route('admin.mahasiswa.index')" class="action-tile">
                            <div class="tile-icon bg-indigo-light text-indigo">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="tile-content">
                                <div class="tile-title">Data Mahasiswa</div>
                                <div class="tile-sub">Kelola data, angkatan & sinkron Siakad</div>
                            </div>
                        </Link>

                        <Link :href="route('admin.tagihan.index')" class="action-tile">
                            <div class="tile-icon bg-emerald-light text-success">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <div class="tile-content">
                                <div class="tile-title">Tagihan UKT</div>
                                <div class="tile-sub">Daftar tagihan semester & export laporan</div>
                            </div>
                        </Link>

                        <Link :href="route('admin.verifikasi.index')" class="action-tile">
                            <div class="tile-icon bg-amber-light text-amber">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="tile-content">
                                <div class="tile-title">Verifikasi Pembayaran</div>
                                <div class="tile-sub">Pengecekan bukti transfer manual</div>
                            </div>
                        </Link>

                        <Link :href="route('admin.dispensasi.index')" class="action-tile">
                            <div class="tile-icon bg-blue-light text-blue">
                                <i class="fas fa-file-signature"></i>
                            </div>
                            <div class="tile-content">
                                <div class="tile-title">Dispensasi UKT</div>
                                <div class="tile-sub">Kelola perpanjangan jatuh tempo</div>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Page Heading */
.page-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}
.page-heading-title {
    font-size: 1.375rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
}
.page-heading-sub {
    font-size: 0.8125rem;
    color: #64748b;
    margin: 0.125rem 0 0;
}
.header-actions {
    display: flex;
    gap: 0.5rem;
}
.btn-solid {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.875rem;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 700;
    text-decoration: none;
}
.btn-warning-solid {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fcd34d;
}
.btn-warning-solid:hover {
    background: #fde68a;
}

/* Stats Row */
.stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.stat-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.125rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
}
.stat-box.highlight-warning {
    border-color: #fcd34d;
    background: #fffbeb;
}
.stat-meta {
    flex: 1;
}
.stat-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 0.25rem;
}
.stat-number {
    font-size: 1.375rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
    margin-bottom: 0.25rem;
}
.stat-hint {
    font-size: 0.75rem;
}
.stat-link {
    color: #4f46e5;
    font-weight: 600;
    text-decoration: none;
}
.stat-link:hover { text-decoration: underline; }

.stat-icon-wrapper {
    width: 44px;
    height: 44px;
    border-radius: 0.625rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.125rem;
    color: #ffffff;
    flex-shrink: 0;
}
.bg-indigo-solid { background: #4f46e5; }
.bg-emerald-solid { background: #10b981; }
.bg-amber-solid { background: #f59e0b; }
.bg-rose-solid { background: #ef4444; }

.text-success { color: #166534; }
.text-amber { color: #b45309; }
.text-danger { color: #dc2626; }
.text-blue { color: #1d4ed8; }
.text-muted { color: #64748b; }
.font-bold { font-weight: 700; }
.font-semibold { font-weight: 600; }

/* Main Dashboard Grid */
.main-dashboard-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
    gap: 1.25rem;
    margin-bottom: 1.5rem;
}
.dashboard-side-stack {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

/* Custom Card */
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
    padding: 0.875rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
}
.header-left {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.card-title {
    font-size: 0.9375rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}
.counter-badge {
    background: #ef4444;
    color: white;
    font-size: 0.6875rem;
    font-weight: 700;
    padding: 0.125rem 0.5rem;
    border-radius: 1rem;
}
.btn-link-action {
    font-size: 0.75rem;
    font-weight: 700;
    color: #4f46e5;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}
.btn-link-action:hover { text-decoration: underline; }

/* Table */
.admin-data-table {
    width: 100%;
    border-collapse: collapse;
}
.admin-data-table th {
    padding: 0.625rem 1rem;
    font-size: 0.6875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
}
.admin-data-table td {
    padding: 0.75rem 1rem;
    font-size: 0.8125rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.method-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.2rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.6875rem;
    font-weight: 600;
}
.tag-va { background: #eef2ff; color: #4f46e5; }
.tag-manual { background: #f1f5f9; color: #334155; }

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

.btn-icon-detail {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 0.375rem;
    background: #f1f5f9;
    color: #334155;
    text-decoration: none;
    transition: all 0.15s;
}
.btn-icon-detail:hover {
    background: #4f46e5;
    color: white;
}

/* Pending Verifications Widget */
.pending-list {
    display: flex;
    flex-direction: column;
    gap: 0.625rem;
}
.pending-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.625rem 0.75rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    gap: 0.75rem;
}
.pending-name {
    font-size: 0.8125rem;
    font-weight: 700;
    color: #0f172a;
}
.pending-meta {
    font-size: 0.75rem;
    color: #64748b;
}
.btn-solid-verify {
    padding: 0.35rem 0.75rem;
    background: #4f46e5;
    color: white;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
}
.btn-solid-verify:hover { background: #4338ca; }
.empty-verify-state {
    text-align: center;
    padding: 1.5rem 0.5rem;
}

/* Split Bar */
.split-bar-wrap {
    display: flex;
    flex-direction: column;
    gap: 0.875rem;
}
.split-bar {
    height: 10px;
    border-radius: 5px;
    background: #e2e8f0;
    display: flex;
    overflow: hidden;
}
.va-segment { background: #4f46e5; }
.tf-segment { background: #0284c7; }
.split-labels {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.split-item {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}
.dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-top: 0.25rem;
    flex-shrink: 0;
}
.dot-va { background: #4f46e5; }
.dot-tf { background: #0284c7; }
.split-title {
    font-size: 0.8125rem;
    font-weight: 700;
    color: #0f172a;
}
.split-sub {
    font-size: 0.75rem;
    color: #64748b;
}

/* Quick Actions Tiles */
.quick-actions-section {
    margin-top: 1rem;
}
.section-title {
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 0.875rem;
}
.action-tiles-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}
.action-tile {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1rem 1.125rem;
    display: flex;
    align-items: center;
    gap: 0.875rem;
    text-decoration: none;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    transition: all 0.15s;
}
.action-tile:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
    transform: translateY(-2px);
}
.tile-icon {
    width: 40px;
    height: 40px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.125rem;
    flex-shrink: 0;
}
.bg-indigo-light { background: #eef2ff; color: #4f46e5; }
.bg-emerald-light { background: #dcfce7; color: #166534; }
.bg-amber-light { background: #fef3c7; color: #b45309; }
.bg-blue-light { background: #e0f2fe; color: #0284c7; }

.tile-title {
    font-size: 0.875rem;
    font-weight: 700;
    color: #0f172a;
}
.tile-sub {
    font-size: 0.75rem;
    color: #64748b;
    line-height: 1.3;
}

.empty-state-clean {
    text-align: center;
    padding: 3rem 1rem;
    color: #64748b;
}
.empty-state-clean i {
    font-size: 2rem;
    color: #cbd5e1;
    margin-bottom: 0.5rem;
    display: block;
}

/* Responsive */
@media (max-width: 1024px) {
    .stats-row { grid-template-columns: repeat(2, 1fr); }
    .main-dashboard-grid { grid-template-columns: 1fr; }
    .action-tiles-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .stats-row { grid-template-columns: 1fr; }
    .action-tiles-grid { grid-template-columns: 1fr; }
    .page-heading { flex-direction: column; align-items: flex-start; }
}
</style>
