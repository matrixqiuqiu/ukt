<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { formatRupiah } from '@/utils';

const props = defineProps({
    tagihans: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const sort = ref(props.filters?.sort || '');
const direction = ref(props.filters?.direction || 'asc');

const doFilter = () => {
    const params = {};
    if (search.value) params.search = search.value;
    if (statusFilter.value) params.status = statusFilter.value;
    if (sort.value) { params.sort = sort.value; params.direction = direction.value; }
    router.get(route('admin.tagihan.index'), params, { preserveState: true, replace: true });
};

const clearFilter = () => {
    search.value = '';
    statusFilter.value = '';
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
        'belum_dibayar': { label: 'Belum Dibayar', cls: 'm-badge-danger' },
        'sudah_dibayar': { label: 'Lunas', cls: 'm-badge-success' },
        'terlambat': { label: 'Terlambat', cls: 'm-badge-danger' },
    };
    return map[status] || { label: status, cls: 'm-badge-secondary' };
};
</script>

<template>
    <Head title="Data Tagihan" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="page-heading">Data Tagihan UKT</h2>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <div class="custom-card">
                    <div class="card-header">
                        <h4>Daftar Tagihan UKT</h4>
                        <div style="display:flex;gap:0.5rem;">
                            <a :href="route('admin.tagihan.export', {search: search || undefined, status: statusFilter || undefined})" class="m-btn m-btn-sm" style="background:#16a34a;color:#fff;"><i class="fas fa-file-excel"></i> Excel</a>
                            <a :href="route('admin.tagihan.export-pdf', {search: search || undefined, status: statusFilter || undefined})" target="_blank" class="m-btn m-btn-sm" style="background:#ef4444;color:#fff;"><i class="fas fa-file-pdf"></i> Preview PDF</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filter -->
                        <div style="display:flex;gap:0.75rem;margin-bottom:1.25rem;flex-wrap:wrap;align-items:center;">
                            <div class="input-group input-group--search" style="max-width:260px;">
                                <span class="input-group__text">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11.5" cy="11.5" r="9.5"/><path stroke-linecap="round" d="M18.5 18.5L22 22"/></g></svg>
                                </span>
                                <input type="search" class="input" v-model="search" placeholder="Cari NIM atau Nama..." @keyup.enter="doFilter" />
                            </div>
                            <select v-model="statusFilter" @change="doFilter" class="form-control" style="max-width:180px;">
                                <option value="">Semua Status</option>
                                <option value="belum_dibayar">Belum Dibayar</option>
                                <option value="sudah_dibayar">Sudah Dibayar</option>
                                <option value="terlambat">Terlambat</option>
                            </select>
                            <button @click="doFilter" class="m-btn m-btn-primary m-btn-sm">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <button v-if="search || statusFilter" @click="clearFilter" class="m-btn m-btn-secondary m-btn-sm">
                                <i class="fas fa-times"></i> Reset
                            </button>
                        </div>

                        <!-- Table -->
                        <div v-if="tagihans.data && tagihans.data.length > 0">
                            <div class="table-responsive">
                            <table class="m-data-table">
                                <thead>
                                    <tr>
                                        <th style="width:50px">No</th>
                                        <th @click="handleSort('nim')" style="cursor:pointer;user-select:none;">NIM <i class="fas" :class="sortIcon('nim')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th @click="handleSort('nama_lengkap')" style="cursor:pointer;user-select:none;">Nama Mahasiswa <i class="fas" :class="sortIcon('nama_lengkap')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th @click="handleSort('semester')" style="cursor:pointer;user-select:none;">Semester <i class="fas" :class="sortIcon('semester')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th @click="handleSort('tahun_akademik')" style="cursor:pointer;user-select:none;">Tahun Akademik <i class="fas" :class="sortIcon('tahun_akademik')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th @click="handleSort('nominal')" style="cursor:pointer;user-select:none;">Nominal <i class="fas" :class="sortIcon('nominal')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th @click="handleSort('status')" style="cursor:pointer;user-select:none;">Status <i class="fas" :class="sortIcon('status')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th style="width:130px">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(t, i) in tagihans.data" :key="t.id" :class="{'row-lunas': t.status==='sudah_dibayar'}">
                                        <td>{{ tagihans.from + i }}</td>
                                        <td style="font-weight:600;font-family:monospace;">{{ t.mahasiswa?.nim }}</td>
                                        <td>{{ t.mahasiswa?.nama_lengkap }}</td>
                                        <td style="text-align:center;">{{ t.semester }}</td>
                                        <td>{{ t.tahun_akademik }}</td>
                                        <td style="font-weight:600;">{{ formatRupiah(t.nominal) }}</td>
                                        <td>
                                            <span class="m-badge" :class="statusBadge(t.status).cls">
                                                {{ statusBadge(t.status).label }}
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                                                <Link :href="route('admin.tagihan.show', t.id)" class="m-btn m-btn-sm m-btn-primary" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </Link>
                                                <Link v-if="t.status === 'sudah_dibayar'" :href="route('admin.tagihan.invoice', t.id)" class="m-btn m-btn-sm m-btn-success" title="Cetak Invoice">
                                                    <i class="fas fa-file-invoice"></i>
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            </div>

                            <!-- Pagination -->
                            <div class="pagination-wrap" style="padding:1rem 1.5rem;display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--gray-100);flex-wrap:wrap;gap:1rem;">
                                <span style="font-size:0.8125rem;color:var(--gray-600);">
                                    Menampilkan {{ tagihans.from }}-{{ tagihans.to }} dari {{ tagihans.total }} data
                                </span>
                                <div class="pagination">
                                    <template v-for="link in tagihans.links" :key="link.label">
                                        <span v-if="!link.url" class="page-item disabled">
                                            <span class="page-link" v-html="link.label"></span>
                                        </span>
                                        <span v-else class="page-item" :class="{ active: link.active }">
                                            <Link :href="link.url" class="page-link" v-html="link.label" preserve-state />
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div v-else style="text-align:center;padding:3rem;color:var(--gray-600);">
                            <i class="fas fa-file-invoice-dollar" style="font-size:2.5rem;color:var(--gray-300);margin-bottom:1rem;display:block;"></i>
                            Tidak ada data tagihan ditemukan.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.input-group { display: flex; align-items: center; position: relative; }
.input-group--search .input { padding-left: 2.5rem; }
.input-group__text { position: absolute; left: 0.75rem; display: flex; color: var(--gray-400); }
.form-control { width: 100%; padding: 0.625rem 0.875rem; border: 1px solid var(--gray-300); border-radius: 0.75rem; font-size: 0.875rem; transition: border-color 0.2s; }
.form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15); }
.pagination { display: flex; gap: 0.25rem; }
.page-item { display: inline-flex; }
.page-link { padding: 0.375rem 0.75rem; border-radius: 0.5rem; font-size: 0.8125rem; color: var(--gray-700); text-decoration: none; border: 1px solid var(--gray-200); transition: all 0.2s; }
.page-link:hover { background: var(--gray-50); }
.page-item.active .page-link { background: var(--primary); color: white; border-color: var(--primary); }
.page-item.disabled .page-link { opacity: 0.5; cursor: not-allowed; }
.row-lunas { background: #f0fdf4 !important; }
.row-lunas td { border-color: #bbf7d0 !important; }
</style>
