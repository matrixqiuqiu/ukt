<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { formatRupiah, formatDate } from '@/utils';
import { ref, computed } from 'vue';

const props = defineProps({
    mahasiswa: Object,
    tagihans: Array,
});

const showImpersonateModal = ref(false);

const totalTagihan = computed(() => {
    return props.tagihans?.reduce((sum, t) => sum + Number(t.nominal || 0), 0) || 0;
});

const totalLunas = computed(() => {
    return props.tagihans?.filter(t => t.status === 'sudah_dibayar').reduce((sum, t) => sum + Number(t.nominal || 0), 0) || 0;
});

const totalTunggakan = computed(() => {
    return props.tagihans?.filter(t => t.status !== 'sudah_dibayar').reduce((sum, t) => sum + Number(t.nominal || 0), 0) || 0;
});

const executeImpersonate = () => {
    showImpersonateModal.value = false;
    router.post(route('admin.mahasiswa.impersonate', props.mahasiswa.id), {}, { preserveScroll: false });
};
</script>

<template>
    <Head :title="`Profil Mahasiswa - ${mahasiswa.nama_lengkap}`" />
    <AuthenticatedLayout>
        <template #header>
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div style="display:flex;align-items:center;gap:0.75rem;">
                    <Link :href="route('admin.mahasiswa.index')" class="solid-btn btn-white-border" title="Kembali ke Daftar Mahasiswa">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </Link>
                    <div>
                        <h2 class="page-heading" style="font-size:1.375rem;font-weight:700;color:#0f172a;margin-bottom:0.125rem;">
                            {{ mahasiswa.nama_lengkap }}
                        </h2>
                        <p style="font-size:0.8125rem;color:#64748b;margin:0;">
                            NIM: <strong style="font-family:monospace;color:#0f172a;">{{ mahasiswa.nim }}</strong> &bull; Program Studi: {{ mahasiswa.jurusan || '-' }}
                        </p>
                    </div>
                </div>

                <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                    <span :class="['solid-badge', mahasiswa.status_aktif ? 'badge-solid-success' : 'badge-solid-danger']" style="font-size:0.8125rem;padding:0.4rem 0.75rem;">
                        <i :class="mahasiswa.status_aktif ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
                        <span style="margin-left:0.35rem;">{{ mahasiswa.status_aktif ? 'Mahasiswa Aktif' : 'Status Nonaktif' }}</span>
                    </span>

                    <button
                        type="button"
                        class="solid-btn btn-indigo-solid"
                        @click="showImpersonateModal = true"
                        title="Masuk ke portal mahasiswa ini"
                    >
                        <i class="fas fa-sign-in-alt"></i> Login Portal Mahasiswa
                    </button>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">
                <!-- 3 Top Statistic Cards -->
                <div class="mhs-stats-grid">
                    <div class="metric-card">
                        <div class="metric-icon-box bg-blue-light text-blue-dark">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div class="metric-info">
                            <div class="metric-label">Total Tagihan Terbit</div>
                            <div class="metric-value">{{ formatRupiah(totalTagihan) }}</div>
                            <div class="metric-desc">{{ tagihans?.length || 0 }} semester tagihan</div>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-icon-box bg-emerald-light text-emerald-dark">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <div class="metric-info">
                            <div class="metric-label">Sudah Dibayar (Lunas)</div>
                            <div class="metric-value" style="color:#059669;">{{ formatRupiah(totalLunas) }}</div>
                            <div class="metric-desc">Pembayaran tervalidasi</div>
                        </div>
                    </div>

                    <div class="metric-card">
                        <div class="metric-icon-box bg-rose-light text-rose-dark">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="metric-info">
                            <div class="metric-label">Sisa Tunggakan UKT</div>
                            <div class="metric-value" :style="{ color: totalTunggakan > 0 ? '#dc2626' : '#059669' }">
                                {{ formatRupiah(totalTunggakan) }}
                            </div>
                            <div class="metric-desc">{{ totalTunggakan > 0 ? 'Belum diselesaikan' : 'Semua tagihan lunas' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Main Layout Grid (Left: Profile Identity, Right: Tagihan History) -->
                <div class="mhs-profile-layout">
                    <!-- Left: Identity & Academic Details -->
                    <div class="mhs-left-col">
                        <div class="solid-card">
                            <div class="card-title-bar">
                                <h3><i class="fas fa-id-card text-primary"></i> Data Induk Mahasiswa</h3>
                            </div>
                            <div class="card-content">
                                <div class="mhs-avatar-header">
                                    <div class="mhs-avatar-circle">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                    <div class="mhs-avatar-title">{{ mahasiswa.nama_lengkap }}</div>
                                    <div class="mhs-avatar-nim">{{ mahasiswa.nim }}</div>
                                </div>

                                <div class="mhs-detail-list">
                                    <div class="detail-row">
                                        <span class="detail-k">Program Studi</span>
                                        <span class="detail-v">{{ mahasiswa.jurusan || '-' }}</span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-k">Tahun Angkatan</span>
                                        <span class="detail-v">Angkatan {{ mahasiswa.angkatan || '-' }}</span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-k">Semester Aktif</span>
                                        <span class="detail-v">
                                            Semester {{ mahasiswa.semester }}
                                            <span style="font-size:0.75rem;color:#64748b;">({{ mahasiswa.semester_label || 'Ganjil' }})</span>
                                        </span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-k">Status Mahasiswa</span>
                                        <span class="detail-v">
                                            <span :class="['solid-badge', mahasiswa.status_aktif ? 'badge-solid-success' : 'badge-solid-danger']">
                                                {{ mahasiswa.status_aktif ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Action Card -->
                        <div class="solid-card" style="margin-top:1.25rem;">
                            <div class="card-title-bar">
                                <h3><i class="fas fa-tools text-primary"></i> Aksi Cepat</h3>
                            </div>
                            <div class="card-content" style="display:grid;gap:0.5rem;">
                                <button
                                    type="button"
                                    class="solid-btn btn-indigo-solid"
                                    style="width:100%;justify-content:center;"
                                    @click="showImpersonateModal = true"
                                >
                                    <i class="fas fa-user-secret"></i> Masuk Sebagai Mahasiswa
                                </button>
                                <Link
                                    :href="route('admin.tagihan.index', { search: mahasiswa.nim })"
                                    class="solid-btn btn-white-border"
                                    style="width:100%;justify-content:center;"
                                >
                                    <i class="fas fa-search"></i> Cari Semua Tagihan NIM Ini
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Right: History of UKT Bills & Payments -->
                    <div class="mhs-right-col">
                        <div class="data-card">
                            <div style="padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;">
                                <div style="font-weight:700;color:#0f172a;font-size:0.9375rem;display:flex;align-items:center;gap:0.5rem;">
                                    <i class="fas fa-history" style="color:#4f46e5;"></i> Riwayat Tagihan & Status Pembayaran UKT
                                </div>
                                <span style="font-size:0.8125rem;font-weight:600;color:#64748b;">
                                    Total: <strong style="color:#0f172a;">{{ tagihans?.length || 0 }}</strong> semester
                                </span>
                            </div>

                            <div v-if="tagihans && tagihans.length > 0">
                                <div class="table-responsive">
                                    <table class="solid-table">
                                        <thead>
                                            <tr>
                                                <th style="width:40px;text-align:center;">No</th>
                                                <th>Semester & Periode</th>
                                                <th>Nominal UKT</th>
                                                <th>Status Tagihan</th>
                                                <th>Metode / No VA</th>
                                                <th style="width:120px;text-align:center;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(t, idx) in tagihans" :key="t.id">
                                                <td style="text-align:center;color:#64748b;font-size:0.8125rem;">
                                                    {{ idx + 1 }}
                                                </td>
                                                <td>
                                                    <div style="font-weight:700;color:#0f172a;font-size:0.875rem;">
                                                        Semester {{ t.semester }}
                                                    </div>
                                                    <div style="font-size:0.75rem;color:#64748b;">
                                                        {{ t.tahun_akademik }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <div style="font-weight:800;color:#0f172a;font-size:0.9375rem;">
                                                        {{ formatRupiah(t.nominal) }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <span :class="['solid-badge', {
                                                        'badge-solid-success': t.status === 'sudah_dibayar',
                                                        'badge-solid-danger': t.status === 'belum_dibayar',
                                                        'badge-solid-warning': t.status === 'terlambat'
                                                    }]">
                                                        <i :class="t.status === 'sudah_dibayar' ? 'fas fa-check-circle' : 'fas fa-clock'"></i>
                                                        <span style="margin-left:0.25rem;">
                                                            {{ t.status === 'sudah_dibayar' ? 'Lunas' : t.status === 'terlambat' ? 'Terlambat' : 'Belum Bayar' }}
                                                        </span>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div v-if="t.pembayarans && t.pembayarans.length > 0">
                                                        <div style="font-size:0.8125rem;font-weight:600;color:#1e293b;">
                                                            {{ t.pembayarans[0].metode_pembayaran?.nama_metode || 'Transfer Bank' }}
                                                        </div>
                                                        <div v-if="t.pembayarans[0].va_number" style="font-size:0.75rem;font-family:monospace;color:#2563eb;">
                                                            VA: {{ t.pembayarans[0].va_number }}
                                                        </div>
                                                    </div>
                                                    <span v-else style="font-size:0.75rem;color:#94a3b8;font-style:italic;">
                                                        Belum ada transaksi
                                                    </span>
                                                </td>
                                                <td style="text-align:center;">
                                                    <div style="display:flex;gap:0.375rem;justify-content:center;">
                                                        <Link
                                                            :href="route('admin.tagihan.show', t.id)"
                                                            class="action-btn-custom btn-view"
                                                            title="Buka Detail Tagihan"
                                                        >
                                                            <i class="fas fa-file-invoice"></i>
                                                        </Link>

                                                        <Link
                                                            v-if="t.status === 'sudah_dibayar'"
                                                            :href="route('admin.tagihan.invoice', t.id)"
                                                            class="action-btn-custom btn-invoice"
                                                            title="Unduh Invoice PDF"
                                                        >
                                                            <i class="fas fa-file-pdf"></i>
                                                        </Link>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div v-else style="text-align:center;padding:4rem 2rem;color:#64748b;">
                                <i class="fas fa-file-invoice" style="font-size:2.5rem;color:#cbd5e1;margin-bottom:0.75rem;display:block;"></i>
                                <h4 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0 0 0.25rem;">Belum Ada Tagihan UKT</h4>
                                <p style="font-size:0.8125rem;color:#64748b;margin:0;">Mahasiswa ini belum memiliki riwayat tagihan yang digenerate oleh sistem.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teleport Modal Impersonate -->
        <Teleport to="body">
            <div v-if="showImpersonateModal" class="modal-overlay" @click.self="showImpersonateModal = false">
                <div class="modal-card" style="max-width:480px;">
                    <div class="modal-header">
                        <div style="display:flex;align-items:center;gap:0.625rem;">
                            <span style="width:36px;height:36px;border-radius:0.5rem;background:#e0f2fe;color:#0284c7;display:flex;align-items:center;justify-content:center;font-size:1.125rem;">
                                <i class="fas fa-user-secret"></i>
                            </span>
                            <div>
                                <h4 style="margin:0;font-size:1rem;font-weight:700;color:#0f172a;">Masuk Sebagai Mahasiswa</h4>
                                <p style="margin:0;font-size:0.75rem;color:#64748b;">Diagnostik dan panduan pembayaran</p>
                            </div>
                        </div>
                        <button class="modal-close-btn" @click="showImpersonateModal = false">&times;</button>
                    </div>

                    <div class="modal-body" style="display:grid;gap:0.875rem;">
                        <p style="margin:0;color:#334155;font-size:0.875rem;line-height:1.5;">
                            Anda akan dialihkan ke antarmuka portal mahasiswa sebagai:
                        </p>
                        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.5rem;padding:0.75rem 1rem;">
                            <div style="font-weight:700;color:#0f172a;font-size:0.9375rem;">{{ mahasiswa.nama_lengkap }}</div>
                            <div style="font-size:0.8125rem;color:#64748b;font-family:monospace;margin-top:0.125rem;">
                                NIM: {{ mahasiswa.nim }} &bull; {{ mahasiswa.jurusan }}
                            </div>
                        </div>
                        <p style="font-size:0.75rem;color:#64748b;margin:0;">
                            Anda dapat kembali ke akun admin kapan saja melalui tombol *"Kembali ke Admin"* di header portal.
                        </p>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="solid-btn btn-white-border" @click="showImpersonateModal = false">Batal</button>
                        <button type="button" class="solid-btn btn-indigo-solid" @click="executeImpersonate">
                            <i class="fas fa-sign-in-alt"></i> Ya, Masuk Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
/* 3 Metric Cards Grid */
.mhs-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}
@media (max-width: 900px) {
    .mhs-stats-grid {
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
.bg-blue-light { background: #eff6ff; }
.text-blue-dark { color: #2563eb; }
.bg-emerald-light { background: #ecfdf5; }
.text-emerald-dark { color: #059669; }
.bg-rose-light { background: #fef2f2; }
.text-rose-dark { color: #dc2626; }

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
.metric-desc {
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 0.125rem;
}

/* Layout Grid */
.mhs-profile-layout {
    display: grid;
    grid-template-columns: 340px minmax(0, 1fr);
    gap: 1.25rem;
    align-items: start;
}
@media (max-width: 900px) {
    .mhs-profile-layout {
        grid-template-columns: 1fr;
    }
}

/* Left Profile Card */
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

.mhs-avatar-header {
    text-align: center;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 1rem;
}
.mhs-avatar-circle {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #e0e7ff;
    color: #4338ca;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    margin: 0 auto 0.75rem;
}
.mhs-avatar-title {
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
}
.mhs-avatar-nim {
    font-family: monospace;
    font-weight: 700;
    font-size: 0.8125rem;
    color: #4f46e5;
    background: #eef2ff;
    display: inline-block;
    padding: 0.15rem 0.5rem;
    border-radius: 0.25rem;
    margin-top: 0.25rem;
}

.mhs-detail-list {
    display: grid;
    gap: 0.75rem;
}
.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8125rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px dashed #f1f5f9;
}
.detail-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.detail-k {
    color: #64748b;
    font-weight: 500;
}
.detail-v {
    color: #0f172a;
    font-weight: 600;
    text-align: right;
}

/* Data Card (Right Column) */
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
    text-align: left;
}
.solid-table td {
    padding: 0.875rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.solid-table tbody tr:hover {
    background: #f8fafc;
}

/* Badges */
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
.btn-invoice {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fecaca;
}
.btn-invoice:hover {
    background: #fee2e2;
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
</style>
