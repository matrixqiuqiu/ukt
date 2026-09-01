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
    router.get(route('admin.pembayaran.index'), params, { preserveState: true });
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
                        <h3 class="card-title">Daftar Pembayaran</h3>
                        <a :href="exportLunasUrl" class="m-btn m-btn-sm" style="background:#16a34a;color:#fff;"><i class="fas fa-file-excel"></i> Excel</a>
                        <a :href="route('admin.pembayaran.export-lunas-pdf', {search: search || undefined})" target="_blank" class="m-btn m-btn-sm" style="background:#ef4444;color:#fff;"><i class="fas fa-file-pdf"></i> Preview PDF</a>
                    </div>
                    <div class="card-body">
                        <div class="filter-section mb-4">
                            <div class="filter-grid">
                                <input v-model="search" @keyup.enter="doFilter" type="text" class="form-control" placeholder="Cari NIM atau Nama..." />
                                <select v-model="statusFilter" @change="doFilter" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="dikonfirmasi">Dikonfirmasi</option>
                                    <option value="ditolak">Ditolak</option>
                                </select>
                                <button @click="doFilter" class="btn btn-primary filter-btn">Filter</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th @click="handleSort('tanggal')" style="cursor:pointer;user-select:none;">Tanggal <i class="fas" :class="sortIcon('tanggal')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th @click="handleSort('mahasiswa')" style="cursor:pointer;user-select:none;">Mahasiswa <i class="fas" :class="sortIcon('mahasiswa')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th @click="handleSort('nim')" style="cursor:pointer;user-select:none;">NIM <i class="fas" :class="sortIcon('nim')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th @click="handleSort('semester')" style="cursor:pointer;user-select:none;">Semester <i class="fas" :class="sortIcon('semester')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th @click="handleSort('jumlah_bayar')" style="cursor:pointer;user-select:none;">Jumlah Bayar <i class="fas" :class="sortIcon('jumlah_bayar')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th>Beasiswa</th>
                                        <th>Bukti</th>
                                        <th @click="handleSort('status')" style="cursor:pointer;user-select:none;">Status <i class="fas" :class="sortIcon('status')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in pembayarans.data" :key="p.id">
                                        <td>{{ formatDate(p.created_at) }}</td>
                                        <td>{{ p.tagihan?.mahasiswa?.nama_lengkap }}</td>
                                        <td>{{ p.tagihan?.mahasiswa?.nim }}</td>
                                        <td>{{ p.tagihan?.semester }}</td>
                                        <td>{{ formatRupiah(p.jumlah_bayar) }}</td>
                                        <td>
                                            <span v-if="p.beasiswa" class="m-badge m-badge-success" style="font-size:0.7rem;white-space:nowrap;"><i class="fas fa-graduation-cap"></i> {{ p.beasiswa.kode }}</span>
                                            <div v-if="p.beasiswa" style="font-size:0.65rem;color:#059669;margin-top:0.2rem;white-space:nowrap;">{{ p.beasiswa.jenis }} · Rp {{ Number(p.beasiswa.diskon).toLocaleString('id-ID') }}</div>
                                            <div v-if="p.beasiswa" style="font-size:0.6rem;color:var(--gray-500);">{{ p.beasiswa.sumber }} · {{ p.beasiswa.status }}</div>
                                            <span v-else style="color:var(--gray-400);font-size:0.75rem;">-</span>
                                        </td>
                                        <td>
                                            <a v-if="p.bukti_pembayaran" :href="`/storage/${p.bukti_pembayaran}`" target="_blank" class="btn btn-sm btn-info">Lihat</a>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td>
                                            <span :class="{
                                                'badge-custom': true,
                                                'badge-warning': p.status === 'pending',
                                                'badge-success': p.status === 'dikonfirmasi',
                                                'badge-danger': p.status === 'ditolak'
                                            }">{{ p.status }}</span>
                                        </td>
                                        <td>
                                            <Link :href="route('admin.pembayaran.show', p.id)" class="btn btn-sm btn-primary">Detail</Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="pembayarans.links && pembayarans.links.length > 3" class="pagination-wrap">
                            <span class="pagination-info">Menampilkan {{ pembayarans.from }}-{{ pembayarans.to }} dari {{ pembayarans.total }} data</span>
                            <div class="pagination">
                                <template v-for="link in pembayarans.links" :key="link.label">
                                    <span v-if="!link.url" class="page-item disabled"><span class="page-link" v-html="link.label"></span></span>
                                    <span v-else class="page-item" :class="{ active: link.active }"><Link :href="link.url" class="page-link" v-html="link.label" /></span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-card { overflow: hidden; }
.card-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; }
.filter-grid { display: grid; grid-template-columns: 1fr 200px 100px; gap: 0.75rem; align-items: end; }
.table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
.table-responsive .data-table { min-width: 860px; }
.pagination-wrap { padding: 1rem 0 0; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--gray-100); margin-top: 1rem; gap: 1rem; flex-wrap: wrap; }
.pagination-info { font-size: 0.8125rem; color: var(--gray-600); }
@media (max-width: 900px) {
    .filter-grid { grid-template-columns: 1fr 1fr; }
    .filter-btn { grid-column: 1 / -1; }
}
@media (max-width: 640px) {
    .container-xl { padding-left: 1rem; padding-right: 1rem; }
    .filter-grid { grid-template-columns: 1fr; }
    .table-responsive .data-table { min-width: 600px; font-size: 0.8125rem; }
    .pagination-wrap { flex-direction: column; align-items: stretch; text-align: center; }
    .pagination { justify-content: center; flex-wrap: wrap; }
}
</style>
