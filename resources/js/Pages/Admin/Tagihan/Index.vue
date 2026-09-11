<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { formatRupiah } from '@/utils';

const props = defineProps({
    tagihans: Object,
    filters: Object,
    filterOptions: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const semesterFilter = ref(props.filters?.semester || '');
const jurusanFilter = ref(props.filters?.jurusan || '');
const sort = ref(props.filters?.sort || '');
const direction = ref(props.filters?.direction || 'asc');

const doFilter = () => {
    const params = {};
    if (search.value) params.search = search.value;
    if (statusFilter.value) params.status = statusFilter.value;
    if (semesterFilter.value) params.semester = semesterFilter.value;
    if (jurusanFilter.value) params.jurusan = jurusanFilter.value;
    if (sort.value) { params.sort = sort.value; params.direction = direction.value; }
    router.get(route('admin.tagihan.index'), params, { preserveState: true, replace: true });
};

const clearFilter = () => {
    search.value = '';
    statusFilter.value = '';
    semesterFilter.value = '';
    jurusanFilter.value = '';
    sort.value = '';
    router.get(route('admin.tagihan.index'), {}, { preserveState: true, replace: true });
};

const handleSort = (key) => {
    if (sort.value === key) direction.value = direction.value === 'asc' ? 'desc' : 'asc';
    else { sort.value = key; direction.value = 'asc'; }
    doFilter();
};
const sortIcon = (key) => sort.value !== key ? 'fa-sort' : (direction.value === 'asc' ? 'fa-sort-up' : 'fa-sort-down');

const statusBadge = (status) => {
    const map = {
        'belum_dibayar': { label: 'Belum Dibayar', cls: 'badge-solid-danger' },
        'sudah_dibayar': { label: 'Lunas', cls: 'badge-solid-success' },
        'terlambat': { label: 'Terlambat', cls: 'badge-solid-danger' },
    };
    return map[status] || { label: status, cls: 'badge-solid-secondary' };
};

const hasActiveFilters = () => {
    return !!(search.value || statusFilter.value || semesterFilter.value || jurusanFilter.value);
};
</script>

<template>
    <Head title="Data Tagihan" />
    <AuthenticatedLayout>
        <template #header>
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div>
                    <h2 class="page-heading" style="font-size:1.375rem;font-weight:700;color:#0f172a;margin-bottom:0.25rem;">Data Tagihan UKT</h2>
                    <p style="font-size:0.875rem;color:#64748b;margin:0;">Kelola dan pantau seluruh tagihan perkuliahan mahasiswa</p>
                </div>
                <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                    <a :href="route('admin.tagihan.export', {search: search || undefined, status: statusFilter || undefined, semester: semesterFilter || undefined, jurusan: jurusanFilter || undefined})" 
                       class="solid-btn solid-btn-excel"
                       title="Export data ke file Excel">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                    <a :href="route('admin.tagihan.export-pdf', {search: search || undefined, status: statusFilter || undefined, semester: semesterFilter || undefined, jurusan: jurusanFilter || undefined})" 
                       target="_blank" 
                       class="solid-btn solid-btn-pdf"
                       title="Cetak atau preview PDF">
                        <i class="fas fa-file-pdf"></i> Preview PDF
                    </a>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">
                <div class="filter-card">
                    <div style="font-size:0.875rem;font-weight:700;color:#1e293b;margin-bottom:0.75rem;display:flex;align-items:center;justify-content:space-between;">
                        <span style="display:flex;align-items:center;gap:0.5rem;">
                            <i class="fas fa-filter" style="color:#4f46e5;"></i> Filter & Pencarian
                        </span>
                        <span v-if="tagihans?.total !== undefined" style="font-size:0.8125rem;font-weight:500;color:#64748b;">
                            Total: <strong style="color:#0f172a;">{{ tagihans.total }}</strong> tagihan
                        </span>
                    </div>

                    <div class="filter-grid">
                        <!-- Search Box -->
                        <div class="search-box-wrap">
                            <i class="fas fa-search search-icon"></i>
                            <input type="search" class="filter-input search-input" v-model="search" placeholder="Cari NIM atau Nama..." @keyup.enter="doFilter" />
                        </div>

                        <!-- Status Filter -->
                        <div class="filter-select-wrap">
                            <select v-model="statusFilter" @change="doFilter" class="filter-input">
                                <option value="">Semua Status</option>
                                <option value="belum_dibayar">Belum Dibayar</option>
                                <option value="sudah_dibayar">Lunas (Sudah Dibayar)</option>
                                <option value="terlambat">Terlambat</option>
                            </select>
                        </div>

                        <!-- Semester Filter -->
                        <div class="filter-select-wrap">
                            <select v-model="semesterFilter" @change="doFilter" class="filter-input">
                                <option value="">Semua Semester</option>
                                <option v-for="sem in (filterOptions?.semesters || [])" :key="sem" :value="sem">
                                    Semester {{ sem }}
                                </option>
                            </select>
                        </div>

                        <!-- Jurusan Filter -->
                        <div class="filter-select-wrap">
                            <select v-model="jurusanFilter" @change="doFilter" class="filter-input">
                                <option value="">Semua Program Studi</option>
                                <option v-for="jur in (filterOptions?.jurusans || [])" :key="jur" :value="jur">
                                    {{ jur }}
                                </option>
                            </select>
                        </div>

                        <!-- Action Buttons -->
                        <div class="filter-actions">
                            <button @click="doFilter" class="btn-solid-primary" style="padding:0.625rem 1rem;">
                                <i class="fas fa-search"></i> Terapkan
                            </button>
                            <button v-if="hasActiveFilters()" @click="clearFilter" class="btn-solid-secondary" style="padding:0.625rem 0.875rem;" title="Reset filter">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="data-card">
                    <div v-if="tagihans.data && tagihans.data.length > 0">
                        <div class="table-responsive">
                            <table class="solid-table">
                                <thead>
                                    <tr>
                                        <th style="width:50px;text-align:center;">No</th>
                                        <th @click="handleSort('nim')" class="sortable-th">NIM <i class="fas" :class="sortIcon('nim')"></i></th>
                                        <th @click="handleSort('nama_lengkap')" class="sortable-th">Mahasiswa <i class="fas" :class="sortIcon('nama_lengkap')"></i></th>
                                        <th>Jurusan</th>
                                        <th @click="handleSort('semester')" class="sortable-th" style="text-align:center;">Sem <i class="fas" :class="sortIcon('semester')"></i></th>
                                        <th @click="handleSort('tahun_akademik')" class="sortable-th">Tahun Akademik <i class="fas" :class="sortIcon('tahun_akademik')"></i></th>
                                        <th @click="handleSort('nominal')" class="sortable-th" style="text-align:right;">Nominal <i class="fas" :class="sortIcon('nominal')"></i></th>
                                        <th @click="handleSort('status')" class="sortable-th" style="text-align:center;">Status <i class="fas" :class="sortIcon('status')"></i></th>
                                        <th style="width:110px;text-align:center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(t, i) in tagihans.data" :key="t.id" :class="{'row-lunas': t.status==='sudah_dibayar'}">
                                        <td style="text-align:center;color:#64748b;font-size:0.8125rem;">{{ tagihans.from + i }}</td>
                                        <td>
                                            <span style="font-family:monospace;font-weight:700;color:#0f172a;background:#f1f5f9;padding:0.2rem 0.4rem;border-radius:0.375rem;font-size:0.8125rem;">
                                                {{ t.mahasiswa?.nim || '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-weight:600;color:#0f172a;font-size:0.875rem;">{{ t.mahasiswa?.nama_lengkap || '-' }}</div>
                                            <div style="font-size:0.75rem;color:#64748b;">Angkatan {{ t.mahasiswa?.angkatan || '-' }}</div>
                                        </td>
                                        <td>
                                            <span style="font-size:0.8125rem;color:#334155;">{{ t.mahasiswa?.jurusan || '-' }}</span>
                                        </td>
                                        <td style="text-align:center;">
                                            <span style="display:inline-block;padding:0.15rem 0.5rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.375rem;font-size:0.75rem;font-weight:600;color:#334155;">
                                                {{ t.semester }}
                                            </span>
                                        </td>
                                        <td style="font-size:0.8125rem;color:#334155;">{{ t.tahun_akademik }}</td>
                                        <td style="text-align:right;font-weight:700;color:#0f172a;font-size:0.875rem;">
                                            {{ formatRupiah(t.nominal) }}
                                        </td>
                                        <td style="text-align:center;">
                                            <span :class="['solid-badge', statusBadge(t.status).cls]">
                                                {{ statusBadge(t.status).label }}
                                            </span>
                                        </td>
                                        <td style="text-align:center;">
                                            <div style="display:flex;gap:0.375rem;justify-content:center;">
                                                <Link :href="route('admin.tagihan.show', t.id)" class="action-btn action-btn-view" title="Lihat Detail Tagihan">
                                                    <i class="fas fa-eye"></i>
                                                </Link>
                                                <Link v-if="t.status === 'sudah_dibayar'" :href="route('admin.tagihan.invoice', t.id)" class="action-btn action-btn-invoice" title="Cetak Invoice">
                                                    <i class="fas fa-file-invoice"></i>
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="pagination-footer">
                            <span style="font-size:0.8125rem;color:#64748b;">
                                Menampilkan <strong style="color:#0f172a;">{{ tagihans.from }}-{{ tagihans.to }}</strong> dari <strong style="color:#0f172a;">{{ tagihans.total }}</strong> data
                            </span>
                            <div class="pagination-btns">
                                <template v-for="link in tagihans.links" :key="link.label">
                                    <span v-if="!link.url" class="p-btn p-disabled" v-html="link.label"></span>
                                    <Link v-else :href="link.url" class="p-btn" :class="{ 'p-active': link.active }" v-html="link.label" preserve-state />
                                </template>
                            </div>
                        </div>
                    </div>

                    <div v-else style="text-align:center;padding:4rem 2rem;color:#64748b;">
                        <div style="width:64px;height:64px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;color:#94a3b8;font-size:1.75rem;">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <h4 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 0.375rem;">Tidak Ada Data Tagihan</h4>
                        <p style="font-size:0.8125rem;color:#64748b;margin:0 0 1rem;max-width:340px;margin-inline:auto;">Tidak ditemukan tagihan yang sesuai dengan filter atau kata kunci pencarian Anda.</p>
                        <button v-if="hasActiveFilters()" @click="clearFilter" class="btn-solid-secondary" style="font-size:0.8125rem;padding:0.5rem 1rem;">
                            <i class="fas fa-undo"></i> Reset Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>
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
    grid-template-columns: minmax(200px, 1.5fr) minmax(140px, 1fr) minmax(130px, 1fr) minmax(160px, 1.2fr) auto;
    gap: 0.75rem;
    align-items: center;
}
@media (max-width: 992px) {
    .filter-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 576px) {
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
    transition: border-color 0.15s, box-shadow 0.15s;
}
.filter-input:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}
.filter-actions {
    display: flex;
    gap: 0.5rem;
}

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
.row-lunas {
    background: #fcfdfd;
}

.solid-badge {
    display: inline-block;
    padding: 0.25rem 0.625rem;
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
.badge-solid-secondary {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.solid-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.875rem;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 600;
    text-decoration: none;
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
    transition: background 0.15s;
}
.btn-solid-primary:hover {
    background: #4338ca;
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
.btn-solid-secondary:hover {
    background: #e2e8f0;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 0.375rem;
    text-decoration: none;
    font-size: 0.75rem;
    transition: opacity 0.15s;
}
.action-btn-view {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
}
.action-btn-view:hover {
    background: #dbeafe;
}
.action-btn-invoice {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
}
.action-btn-invoice:hover {
    background: #dcfce7;
}

.pagination-footer {
    padding: 0.875rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #f1f5f9;
    flex-wrap: wrap;
    gap: 1rem;
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
    transition: all 0.15s;
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
</style>

