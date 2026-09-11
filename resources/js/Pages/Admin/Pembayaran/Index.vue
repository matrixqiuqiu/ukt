<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { formatRupiah, formatDate } from '@/utils';

const props = defineProps({
    pembayarans: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const sort = ref(props.filters?.sort || '');
const direction = ref(props.filters?.direction || 'asc');

const lightboxImage = ref(null);

const doFilter = () => {
    const params = {};
    if (search.value) params.search = search.value;
    if (statusFilter.value) params.status = statusFilter.value;
    if (sort.value) { params.sort = sort.value; params.direction = direction.value; }
    router.get(route('admin.pembayaran.index'), params, { preserveState: true, replace: true });
};

const clearFilter = () => {
    search.value = '';
    statusFilter.value = '';
    sort.value = '';
    router.get(route('admin.pembayaran.index'), {}, { preserveState: true, replace: true });
};

const handleSort = (key) => {
    if (sort.value === key) direction.value = direction.value === 'asc' ? 'desc' : 'asc';
    else { sort.value = key; direction.value = 'asc'; }
    doFilter();
};
const sortIcon = (key) => sort.value !== key ? 'fa-sort' : (direction.value === 'asc' ? 'fa-sort-up' : 'fa-sort-down');

const exportLunasUrl = computed(() => {
    const params = {};
    if (search.value) params.search = search.value;
    if (statusFilter.value) params.status = statusFilter.value;
    return route('admin.pembayaran.export-lunas', params);
});

const statusBadge = (status) => {
    const map = {
        'dikonfirmasi': { label: 'Lunas / Dikonfirmasi', cls: 'badge-solid-success' },
        'pending': { label: 'Menunggu Verifikasi', cls: 'badge-solid-warning' },
        'ditolak': { label: 'Ditolak', cls: 'badge-solid-danger' },
    };
    return map[status] || { label: status, cls: 'badge-solid-secondary' };
};

const openLightbox = (url) => {
    lightboxImage.value = url;
};

const closeLightbox = () => {
    lightboxImage.value = null;
};
</script>

<template>
    <Head title="Data Pembayaran" />
    <AuthenticatedLayout>
        <template #header>
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div>
                    <h2 class="page-heading" style="font-size:1.375rem;font-weight:700;color:#0f172a;margin-bottom:0.25rem;">Data Pembayaran UKT</h2>
                    <p style="font-size:0.875rem;color:#64748b;margin:0;">Riwayat seluruh transaksi pembayaran mahasiswa</p>
                </div>
                <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                    <a :href="exportLunasUrl" class="solid-btn solid-btn-excel" title="Download Excel data lunas">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                    <a :href="route('admin.pembayaran.export-lunas-pdf', {search: search || undefined})" target="_blank" class="solid-btn solid-btn-pdf" title="Cetak laporan PDF">
                        <i class="fas fa-file-pdf"></i> Preview PDF
                    </a>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">
                <!-- Filter Card -->
                <div class="filter-card">
                    <div style="font-size:0.875rem;font-weight:700;color:#1e293b;margin-bottom:0.75rem;display:flex;align-items:center;justify-content:space-between;">
                        <span style="display:flex;align-items:center;gap:0.5rem;">
                            <i class="fas fa-filter" style="color:#4f46e5;"></i> Filter Pembayaran
                        </span>
                        <span v-if="pembayarans?.total !== undefined" style="font-size:0.8125rem;font-weight:500;color:#64748b;">
                            Total: <strong style="color:#0f172a;">{{ pembayarans.total }}</strong> transaksi
                        </span>
                    </div>

                    <div class="filter-grid">
                        <div class="search-box-wrap">
                            <i class="fas fa-search search-icon"></i>
                            <input
                                type="search"
                                class="filter-input search-input"
                                v-model="search"
                                placeholder="Cari NIM atau Nama..."
                                @keyup.enter="doFilter"
                            />
                        </div>

                        <select v-model="statusFilter" @change="doFilter" class="filter-input">
                            <option value="">Semua Status</option>
                            <option value="pending">Menunggu Verifikasi (Pending)</option>
                            <option value="dikonfirmasi">Dikonfirmasi (Lunas)</option>
                            <option value="ditolak">Ditolak</option>
                        </select>

                        <div class="filter-actions">
                            <button @click="doFilter" class="btn-solid-primary" style="padding:0.5625rem 1rem;">
                                <i class="fas fa-search"></i> Terapkan
                            </button>
                            <button v-if="search || statusFilter" @click="clearFilter" class="btn-solid-secondary" style="padding:0.5625rem 0.875rem;">
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
                                        <th @click="handleSort('tanggal')" class="sortable-th">Tanggal <i class="fas" :class="sortIcon('tanggal')"></i></th>
                                        <th @click="handleSort('nim')" class="sortable-th">Mahasiswa <i class="fas" :class="sortIcon('nim')"></i></th>
                                        <th @click="handleSort('semester')" class="sortable-th" style="text-align:center;">Sem <i class="fas" :class="sortIcon('semester')"></i></th>
                                        <th @click="handleSort('jumlah_bayar')" class="sortable-th" style="text-align:right;">Nominal <i class="fas" :class="sortIcon('jumlah_bayar')"></i></th>
                                        <th>Metode / Beasiswa</th>
                                        <th style="text-align:center;">Bukti</th>
                                        <th @click="handleSort('status')" class="sortable-th" style="text-align:center;">Status <i class="fas" :class="sortIcon('status')"></i></th>
                                        <th style="width:70px;text-align:center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(p, i) in pembayarans.data" :key="p.id">
                                        <td style="text-align:center;color:#64748b;font-size:0.8125rem;">
                                            {{ pembayarans.from + i }}
                                        </td>
                                        <td style="white-space:nowrap;font-size:0.8125rem;color:#334155;">
                                            {{ formatDate(p.created_at) }}
                                        </td>
                                        <td>
                                            <div style="font-weight:700;color:#0f172a;font-size:0.875rem;">
                                                {{ p.tagihan?.mahasiswa?.nama_lengkap || '-' }}
                                            </div>
                                            <div style="font-family:monospace;font-size:0.75rem;color:#475569;">
                                                NIM: {{ p.tagihan?.mahasiswa?.nim || '-' }}
                                            </div>
                                        </td>
                                        <td style="text-align:center;">
                                            <span style="display:inline-block;padding:0.15rem 0.5rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.375rem;font-size:0.75rem;font-weight:600;color:#334155;">
                                                {{ p.tagihan?.semester ?? '-' }}
                                            </span>
                                        </td>
                                        <td style="text-align:right;font-weight:700;color:#0f172a;font-size:0.875rem;white-space:nowrap;">
                                            {{ formatRupiah(p.jumlah_bayar) }}
                                        </td>
                                        <td>
                                            <div v-if="p.beasiswa" style="display:inline-flex;align-items:center;gap:0.25rem;background:#ecfdf5;color:#065f46;padding:0.15rem 0.45rem;border-radius:0.375rem;font-size:0.75rem;font-weight:600;border:1px solid #a7f3d0;">
                                                <i class="fas fa-graduation-cap"></i> {{ p.beasiswa.kode }}
                                            </div>
                                            <div v-else-if="p.metode_pembayaran" style="font-size:0.8125rem;color:#334155;font-weight:600;">
                                                {{ p.metode_pembayaran?.nama_metode }}
                                            </div>
                                            <span v-else style="color:#94a3b8;font-size:0.75rem;">-</span>
                                        </td>
                                        <td style="text-align:center;">
                                            <button
                                                v-if="p.bukti_pembayaran"
                                                @click="openLightbox(`/storage/${p.bukti_pembayaran}`)"
                                                class="btn-bukti-thumb"
                                                title="Klik untuk melihat bukti transfer"
                                            >
                                                <i class="fas fa-receipt"></i> Bukti
                                            </button>
                                            <span v-else style="color:#94a3b8;font-size:0.75rem;">-</span>
                                        </td>
                                        <td style="text-align:center;">
                                            <span :class="['solid-badge', statusBadge(p.status).cls]">
                                                {{ statusBadge(p.status).label }}
                                            </span>
                                        </td>
                                        <td style="text-align:center;">
                                            <Link :href="route('admin.pembayaran.show', p.id)" class="action-btn-view" title="Lihat Detail Transaksi">
                                                <i class="fas fa-eye"></i>
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Footer -->
                        <div class="pagination-footer">
                            <span style="font-size:0.8125rem;color:#64748b;">
                                Menampilkan <strong style="color:#0f172a;">{{ pembayarans.from }}-{{ pembayarans.to }}</strong> dari <strong style="color:#0f172a;">{{ pembayarans.total }}</strong> transaksi
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
                        <i class="fas fa-receipt" style="font-size:2.5rem;color:#cbd5e1;margin-bottom:0.75rem;display:block;"></i>
                        <h4 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0 0 0.25rem;">Tidak Ada Data Pembayaran</h4>
                        <p style="font-size:0.8125rem;color:#64748b;margin:0 0 1rem;">Tidak ditemukan transaksi pembayaran yang sesuai kriteria filter.</p>
                        <button v-if="search || statusFilter" @click="clearFilter" class="btn-solid-secondary" style="font-size:0.8125rem;">
                            <i class="fas fa-undo"></i> Reset Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lightbox Bukti Transfer -->
        <Teleport to="body">
            <div v-if="lightboxImage" class="lightbox-overlay" @click.self="closeLightbox">
                <div class="lightbox-content">
                    <div class="lightbox-header">
                        <span style="font-size:0.875rem;font-weight:700;color:#0f172a;">Bukti Pembayaran / Transfer</span>
                        <button class="lightbox-close" @click="closeLightbox">&times;</button>
                    </div>
                    <div class="lightbox-body">
                        <img :src="lightboxImage" alt="Bukti Transfer" class="lightbox-img" />
                    </div>
                    <div class="lightbox-footer">
                        <a :href="lightboxImage" target="_blank" class="solid-btn btn-indigo-solid" style="font-size:0.75rem;">
                            <i class="fas fa-external-link-alt"></i> Buka Ukuran Asli
                        </a>
                        <button class="solid-btn btn-white-border" @click="closeLightbox" style="font-size:0.75rem;">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.filter-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1rem 1.25rem;
    margin-bottom: 1.25rem;
}
.filter-grid {
    display: grid;
    grid-template-columns: minmax(220px, 1.5fr) minmax(180px, 1fr) auto;
    gap: 0.75rem;
    align-items: center;
}
@media (max-width: 768px) {
    .filter-grid {
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
}
.filter-input:focus {
    border-color: #4f46e5;
}
.filter-actions {
    display: flex;
    gap: 0.5rem;
}

/* Data Card */
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
.sortable-th {
    cursor: pointer;
}
.sortable-th i {
    font-size: 0.6875rem;
    opacity: 0.6;
    margin-left: 0.25rem;
}
.sortable-th:hover {
    color: #0f172a;
}

/* Solid Badges */
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
.badge-solid-secondary {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
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
.solid-btn-excel {
    background: #16a34a;
    color: #ffffff;
}
.solid-btn-pdf {
    background: #dc2626;
    color: #ffffff;
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

.btn-bukti-thumb {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
}
.btn-bukti-thumb:hover {
    background: #dbeafe;
}

.action-btn-view {
    width: 28px;
    height: 28px;
    border-radius: 0.375rem;
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    text-decoration: none;
}
.action-btn-view:hover {
    background: #dbeafe;
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

/* Lightbox */
.lightbox-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.75);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1060;
    padding: 1rem;
}
.lightbox-content {
    background: #ffffff;
    border-radius: 0.75rem;
    max-width: 520px;
    width: 100%;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}
.lightbox-header {
    padding: 0.875rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
}
.lightbox-close {
    border: none;
    background: none;
    font-size: 1.5rem;
    line-height: 1;
    color: #94a3b8;
    cursor: pointer;
}
.lightbox-body {
    padding: 1rem;
    background: #f8fafc;
    display: flex;
    justify-content: center;
}
.lightbox-img {
    max-width: 100%;
    max-height: 65vh;
    object-fit: contain;
    border-radius: 0.375rem;
    border: 1px solid #e2e8f0;
}
.lightbox-footer {
    padding: 0.75rem 1.25rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    background: #ffffff;
}
</style>

