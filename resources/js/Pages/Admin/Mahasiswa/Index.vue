<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    mahasiswas: Object,
    filters: Object,
    filterOptions: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const jurusan = ref(props.filters?.jurusan || '');
const semester = ref(props.filters?.semester || '');
const angkatan = ref(props.filters?.angkatan || '');
const sort = ref(props.filters?.sort || '');
const direction = ref(props.filters?.direction || 'asc');
const syncAngkatan = ref('');
const syncBatch = ref('100');
const syncing = ref(false);

const doFilter = () => {
    const params = {};
    if (search.value) params.search = search.value;
    if (status.value) params.status = status.value;
    if (jurusan.value) params.jurusan = jurusan.value;
    if (semester.value) params.semester = semester.value;
    if (angkatan.value) params.angkatan = angkatan.value;
    if (sort.value) { params.sort = sort.value; params.direction = direction.value; }
    router.get(route('admin.mahasiswa.index'), params, { preserveState: true, replace: true });
};

const handleSort = (key) => {
    if (sort.value === key) {
        direction.value = direction.value === 'asc' ? 'desc' : 'asc';
    } else {
        sort.value = key;
        direction.value = 'asc';
    }
    doFilter();
};
const sortIcon = (key) => {
    if (sort.value !== key) return 'fa-sort';
    return direction.value === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
};

const clearFilter = () => {
    search.value = '';
    status.value = '';
    jurusan.value = '';
    semester.value = '';
    angkatan.value = '';
    router.get(route('admin.mahasiswa.index'), {}, { preserveState: true, replace: true });
};

const hasFilter = () => search.value || status.value || jurusan.value || semester.value || angkatan.value;

const doSync = () => {
    if (!syncAngkatan.value) {
        alert('Pilih angkatan terlebih dahulu untuk sinkronisasi.');
        return;
    }
    const jumlah = syncBatch.value === 'semua' ? 'semua data' : syncBatch.value + ' data';
    if (window.confirm(`Sinkronisasi ${jumlah} mahasiswa angkatan ${syncAngkatan.value} dari Siakad? Klik berulang untuk melanjutkan batch berikutnya.`)) {
        syncing.value = true;
        router.post(route('admin.siakad.sync-mahasiswa'), { angkatan: syncAngkatan.value, batch: syncBatch.value }, {
            onFinish: () => { syncing.value = false; },
        });
    }
};

const loginAs = (id, nama) => {
    if (confirm(`Login sebagai ${nama}? Anda akan dialihkan ke dashboard mahasiswa.`)) {
        router.post(route('admin.mahasiswa.impersonate', id), {}, { preserveScroll: false });
    }
};
</script>

<template>
    <Head title="Data Mahasiswa" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="page-heading">Data Mahasiswa</h2>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <div class="custom-card">
                    <div class="card-header">
                        <h4><i class="fas fa-users" style="margin-right:0.5rem;"></i> Daftar Mahasiswa</h4>
                        <div class="toolbar-actions">
                            <select v-model="syncAngkatan" class="filter-select">
                                <option value="">-- Pilih Angkatan --</option>
                                <option v-for="a in filterOptions.angkatan" :key="a" :value="a">Angkatan {{ a }}</option>
                            </select>
                            <select v-model="syncBatch" class="filter-select" title="Jumlah data per sinkron">
                                <option value="100">100 / kali</option>
                                <option value="200">200 / kali</option>
                                <option value="500">500 / kali</option>
                                <option value="semua">Semua</option>
                            </select>
                            <a :href="route('admin.siakad.test-connection')" target="_blank" class="m-btn m-btn-sm m-btn-secondary">
                                <i class="fas fa-plug"></i> Test API
                            </a>
                            <button @click="doSync" class="m-btn m-btn-sm m-btn-primary" :disabled="syncing">
                                <i class="fas" :class="syncing ? 'fa-spinner fa-spin' : 'fa-sync-alt'"></i>
                                {{ syncing ? 'Sinkronisasi...' : 'Sinkron Siakad' }}
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filter Bar -->
                        <div class="filter-bar">
                            <div class="input-group input-group--search">
                                <span class="input-group__text">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11.5" cy="11.5" r="9.5"/><path stroke-linecap="round" d="M18.5 18.5L22 22"/></g></svg>
                                </span>
                                <input type="search" class="input" v-model="search" placeholder="Cari NIM atau Nama..." @keyup.enter="doFilter" />
                            </div>
                            <select v-model="status" @change="doFilter" class="filter-select">
                                <option value="">Semua Status</option>
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                            <select v-model="jurusan" @change="doFilter" class="filter-select">
                                <option value="">Semua Jurusan</option>
                                <option v-for="j in filterOptions.jurusan" :key="j" :value="j">{{ j }}</option>
                            </select>
                            <select v-model="angkatan" @change="doFilter" class="filter-select" style="max-width:130px;">
                                <option value="">Semua Angkatan</option>
                                <option v-for="a in filterOptions.angkatan" :key="a" :value="a">{{ a }}</option>
                            </select>
                            <select v-model="semester" @change="doFilter" class="filter-select" style="max-width:130px;">
                                <option value="">Semua Semester</option>
                                <option v-for="s in filterOptions.semester" :key="s" :value="s">Semester {{ s }}</option>
                            </select>
                            <div class="filter-actions">
                                <button @click="doFilter" class="m-btn m-btn-sm m-btn-primary filter-btn">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <button v-if="hasFilter()" @click="clearFilter" class="m-btn m-btn-sm m-btn-secondary filter-btn">
                                    <i class="fas fa-times"></i> Reset
                                </button>
                            </div>
                        </div>

                        <!-- Table -->
                        <div v-if="mahasiswas.data && mahasiswas.data.length > 0">
                            <div class="table-responsive">
                            <table class="m-data-table">
                                <thead>
                                    <tr>
                                        <th style="width:50px">No</th>
                                        <th @click="handleSort('nim')" style="cursor:pointer;user-select:none;">NIM <i class="fas" :class="sortIcon('nim')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th @click="handleSort('nama_lengkap')" style="cursor:pointer;user-select:none;">Nama Lengkap <i class="fas" :class="sortIcon('nama_lengkap')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th>Email</th>
                                        <th>No. HP</th>
                                        <th @click="handleSort('jurusan')" style="cursor:pointer;user-select:none;">Jurusan <i class="fas" :class="sortIcon('jurusan')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th @click="handleSort('angkatan')" style="cursor:pointer;user-select:none;text-align:center;">Angkatan <i class="fas" :class="sortIcon('angkatan')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th @click="handleSort('semester')" style="cursor:pointer;user-select:none;text-align:center;">Semester <i class="fas" :class="sortIcon('semester')" style="font-size:0.65rem;opacity:0.5;margin-left:0.25rem;"></i></th>
                                        <th style="text-align:center;">Status</th>
                                        <th style="width:110px">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(m, i) in mahasiswas.data" :key="m.id">
                                        <td>{{ mahasiswas.from + i }}</td>
                                        <td style="font-weight:600;font-family:monospace;">{{ m.nim }}</td>
                                        <td>{{ m.nama_lengkap }}</td>
                                        <td>{{ m.email || '-' }}</td>
                                        <td>{{ m.telepon || '-' }}</td>
                                        <td>{{ m.jurusan }}</td>
                                        <td style="text-align:center;">{{ m.angkatan }}</td>
                                        <td style="text-align:center;">
                                            <span class="m-badge" :class="(m.semester_label || (m.semester %2===0 ? 'Genap':'Ganjil')) === 'Genap' ? 'm-badge-info' : 'm-badge-success'" :title="`Tersimpan: ${m.semester}`">
                                                {{ m.semester_hitung ?? m.semester }}
                                            </span>
                                            <div style="font-size:0.6rem;color:var(--gray-500);">{{ m.semester_label || '-' }}</div>
                                        </td>
                                        <td style="text-align:center;">
                                            <span class="m-badge" :class="m.status_aktif ? 'm-badge-success' : 'm-badge-danger'">
                                                {{ m.status_aktif ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display:flex;gap:0.375rem;">
                                                <Link :href="route('admin.mahasiswa.show', m.id)" class="m-btn m-btn-sm m-btn-primary" title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </Link>
                                                <button @click="loginAs(m.id, m.nama_lengkap)" class="m-btn m-btn-sm" style="background:#0ea5e9;color:#fff;" title="Login sebagai mahasiswa">
                                                    <i class="fas fa-sign-in-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            </div>

                            <!-- Pagination -->
                            <div class="pagination-wrap">
                                <span class="pagination-info">
                                    Menampilkan {{ mahasiswas.from }}-{{ mahasiswas.to }} dari {{ mahasiswas.total }} data
                                </span>
                                <div class="pagination">
                                    <template v-for="link in mahasiswas.links" :key="link.label">
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
                        <div v-else class="empty-state">
                            <i class="fas fa-users" style="font-size:2.5rem;color:var(--gray-300);margin-bottom:1rem;display:block;"></i>
                            Tidak ada data mahasiswa ditemukan.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-card { overflow:hidden; border:1px solid var(--gray-200); border-radius:1rem; box-shadow:0 1px 3px rgba(0,0,0,0.05); }
.card-header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem; padding:1rem 1.25rem; border-bottom:1px solid var(--gray-100); background:linear-gradient(to bottom,#fff,#f9fafb); }
.filter-bar {
    display: grid;
    grid-template-columns: 240px 140px 160px 130px 130px auto;
    gap: 0.625rem;
    align-items: center;
    margin-bottom: 1.25rem;
}
.filter-actions { display:flex; gap:0.5rem; align-items:center; }
.filter-btn { white-space:nowrap; flex:0 0 auto; padding:0.625rem 1rem; }
.input-group { display:flex; align-items:center; position:relative; width:100%; }
.input-group--search .input { width:100%; padding-left:2.25rem; }
.input-group__text { position:absolute; left:0.875rem; top:50%; transform:translateY(-50%); display:flex; align-items:center; color:var(--gray-400); pointer-events:none; line-height:1; }
.filter-select {
    padding: 0.625rem 0.75rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.75rem;
    font-size: 0.8125rem;
    color: var(--gray-700);
    background: white;
    width:100%;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.filter-select:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(79,70,229,0.15); }
.toolbar-actions { display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap; }
.toolbar-actions .filter-select { max-width:150px; width:auto; }
.table-responsive { width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch; }
.table-responsive .m-data-table { min-width:900px; }
.empty-state { text-align:center; padding:3rem; color:var(--gray-500); }
.empty-state i { display:block; }
.pagination-wrap {
    display:flex; justify-content:space-between; align-items:center;
    padding:1rem 1.25rem; border-top:1px solid var(--gray-100); gap:1rem; flex-wrap:wrap;
}
.pagination-info { font-size:0.8125rem; color:var(--gray-600); }
.pagination { display:flex; gap:0.25rem; flex-wrap:wrap; }
.page-item { display:inline-flex; }
.page-link { padding:0.375rem 0.75rem; border-radius:0.5rem; font-size:0.8125rem; color:var(--gray-700); text-decoration:none; border:1px solid var(--gray-200); transition:all 0.2s; }
.page-link:hover { background:var(--gray-50); }
.page-item.active .page-link { background:var(--primary); color:white; border-color:var(--primary); }
.page-item.disabled .page-link { opacity:0.5; cursor:not-allowed; }
@media (max-width: 1100px) { .filter-bar { grid-template-columns: 1fr 1fr 1fr; } .filter-actions { grid-column: 1 / -1; justify-content:flex-start; } }
@media (max-width: 900px) { .filter-bar { grid-template-columns: 1fr 1fr; } }
@media (max-width: 640px) {
    .container-xl { padding-left:1rem; padding-right:1rem; }
    .card-header { flex-direction:column; align-items:stretch; }
    .toolbar-actions { display:grid; grid-template-columns:1fr 1fr; width:100%; }
    .toolbar-actions .filter-select { max-width:100%; }
    .filter-bar { grid-template-columns: 1fr; }
    .filter-actions { justify-content:stretch; }
    .filter-actions .filter-btn { flex:1; justify-content:center; }
    .table-responsive .m-data-table { min-width:720px; font-size:0.8125rem; }
    .pagination-wrap { flex-direction:column; align-items:stretch; text-align:center; }
    .pagination { justify-content:center; }
}
</style>
