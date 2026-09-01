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

const doFilter = () => {
    const params = {};
    if (search.value) params.search = search.value;
    if (statusFilter.value) params.status = statusFilter.value;
    if (sort.value) { params.sort = sort.value; params.direction = direction.value; }
    router.get(route('admin.pembayaran.index'), params, { preserveState: true, replace: true });
};
const clearFilter = () => {
    search.value = ''; statusFilter.value = ''; sort.value = '';
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
</script>
<template>
    <Head title="Data Pembayaran" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="page-heading">Data Pembayaran</h2>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <div class="custom-card">
                    <div class="card-header">
                        <h4>Daftar Pembayaran</h4>
                        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                            <a :href="exportLunasUrl" class="m-btn m-btn-sm" style="background:#16a34a;color:#fff;"><i class="fas fa-file-excel"></i> Excel</a>
                            <a :href="route('admin.pembayaran.export-lunas-pdf', {search: search || undefined})" target="_blank" class="m-btn m-btn-sm" style="background:#ef4444;color:#fff;"><i class="fas fa-file-pdf"></i> Preview PDF</a>
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
                                <option value="pending">Pending</option>
                                <option value="dikonfirmasi">Dikonfirmasi</option>
                                <option value="ditolak">Ditolak</option>
                            </select>
                            <button @click="doFilter" class="m-btn m-btn-primary m-btn-sm"><i class="fas fa-filter"></i> Filter</button>
                            <button v-if="search || statusFilter" @click="clearFilter" class="m-btn m-btn-secondary m-btn-sm"><i class="fas fa-times"></i> Reset</button>
                        </div>

                        <div v-if="pembayarans.data && pembayarans.data.length">
                            <div class="table-responsive">
                            <table class="m-data-table">
                                <thead>
                                    <tr>
                                        <th style="width:50px">No</th>
                                        <th @click="handleSort('tanggal')" style="cursor:pointer;user-select:none;">Tanggal <i class="fas" :class="sortIcon('tanggal')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th @click="handleSort('mahasiswa')" style="cursor:pointer;user-select:none;">Mahasiswa <i class="fas" :class="sortIcon('mahasiswa')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th @click="handleSort('nim')" style="cursor:pointer;user-select:none;">NIM <i class="fas" :class="sortIcon('nim')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th @click="handleSort('semester')" style="cursor:pointer;user-select:none;">Semester <i class="fas" :class="sortIcon('semester')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th @click="handleSort('jumlah_bayar')" style="cursor:pointer;user-select:none;">Jumlah <i class="fas" :class="sortIcon('jumlah_bayar')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th>Beasiswa</th>
                                        <th>Bukti</th>
                                        <th @click="handleSort('status')" style="cursor:pointer;user-select:none;">Status <i class="fas" :class="sortIcon('status')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th style="width:80px">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(p,i) in pembayarans.data" :key="p.id">
                                        <td>{{ pembayarans.from + i }}</td>
                                        <td style="white-space:nowrap;font-size:0.8125rem;">{{ formatDate(p.created_at) }}</td>
                                        <td style="font-weight:600;">{{ p.tagihan?.mahasiswa?.nama_lengkap }}</td>
                                        <td style="font-family:monospace;font-weight:600;">{{ p.tagihan?.mahasiswa?.nim }}</td>
                                        <td style="text-align:center;"><span class="m-badge m-badge-secondary">{{ p.tagihan?.semester ?? '-' }}</span></td>
                                        <td style="font-weight:700;white-space:nowrap;">{{ formatRupiah(p.jumlah_bayar) }}</td>
                                        <td>
                                            <span v-if="p.beasiswa" class="m-badge m-badge-success" style="font-size:0.7rem;white-space:nowrap;"><i class="fas fa-graduation-cap"></i> {{ p.beasiswa.kode }}</span>
                                            <div v-if="p.beasiswa" style="font-size:0.65rem;color:#059669;margin-top:0.2rem;white-space:nowrap;">{{ p.beasiswa.jenis }} · Rp {{ Number(p.beasiswa.diskon).toLocaleString('id-ID') }}</div>
                                            <span v-else style="color:var(--gray-400);font-size:0.75rem;">-</span>
                                        </td>
                                        <td style="text-align:center;">
                                            <a v-if="p.bukti_pembayaran" :href="`/storage/${p.bukti_pembayaran}`" target="_blank" class="m-btn m-btn-sm m-btn-info"><i class="fas fa-eye"></i></a>
                                            <span v-else style="color:var(--gray-400);font-size:0.75rem;">-</span>
                                        </td>
                                        <td>
                                            <span class="m-badge" :class="p.status==='pending'?'m-badge-warning':p.status==='dikonfirmasi'?'m-badge-success':'m-badge-danger'">{{ p.status }}</span>
                                        </td>
                                        <td>
                                            <Link :href="route('admin.pembayaran.show', p.id)" class="m-btn m-btn-sm m-btn-primary" title="Detail"><i class="fas fa-eye"></i></Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            </div>
                            <div v-if="pembayarans.links && pembayarans.links.length > 3" class="pagination-wrap" style="padding:1rem 1.5rem;display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--gray-100);flex-wrap:wrap;gap:1rem;">
                                <span style="font-size:0.8125rem;color:var(--gray-600);">Menampilkan {{ pembayarans.from }}-{{ pembayarans.to }} dari {{ pembayarans.total }} data</span>
                                <div class="pagination">
                                    <template v-for="link in pembayarans.links" :key="link.label">
                                        <span v-if="!link.url" class="page-item disabled"><span class="page-link" v-html="link.label"></span></span>
                                        <span v-else class="page-item" :class="{ active: link.active }"><Link :href="link.url" class="page-link" v-html="link.label" preserve-state /></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div v-else style="text-align:center;padding:3rem;color:var(--gray-600);">
                            <i class="fas fa-receipt" style="font-size:2.5rem;color:var(--gray-300);margin-bottom:1rem;display:block;"></i>
                            Tidak ada data pembayaran.
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
</style>
