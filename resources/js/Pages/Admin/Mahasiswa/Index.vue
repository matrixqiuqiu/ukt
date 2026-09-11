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

const syncModalOpen = ref(false);
const syncAngkatan = ref('');
const syncBatch = ref('100');
const syncing = ref(false);

const impersonateModal = ref(null);

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
    sort.value = '';
    router.get(route('admin.mahasiswa.index'), {}, { preserveState: true, replace: true });
};

const hasFilter = () => search.value || status.value || jurusan.value || semester.value || angkatan.value;

const openSyncModal = () => {
    if (!syncAngkatan.value && props.filterOptions?.angkatan?.length > 0) {
        syncAngkatan.value = props.filterOptions.angkatan[0];
    }
    syncModalOpen.value = true;
};

const executeSync = () => {
    if (!syncAngkatan.value) return;
    syncing.value = true;
    router.post(route('admin.siakad.sync-mahasiswa'), { 
        angkatan: syncAngkatan.value, 
        batch: syncBatch.value 
    }, {
        onFinish: () => { 
            syncing.value = false;
            syncModalOpen.value = false;
        },
    });
};

const openImpersonateModal = (m) => {
    impersonateModal.value = m;
};

const executeImpersonate = () => {
    if (!impersonateModal.value) return;
    router.post(route('admin.mahasiswa.impersonate', impersonateModal.value.id), {}, { preserveScroll: false });
};
</script>

<template>
    <Head title="Data Mahasiswa" />
    <AuthenticatedLayout>
        <template #header>
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div>
                    <h2 class="page-heading" style="font-size:1.375rem;font-weight:700;color:#0f172a;margin-bottom:0.25rem;">Data Mahasiswa</h2>
                    <p style="font-size:0.875rem;color:#64748b;margin:0;">Kelola data induk mahasiswa dan sinkronisasi dengan SIAKAD</p>
                </div>
                <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                    <a :href="route('admin.siakad.test-connection')" target="_blank" class="solid-btn btn-white-border" title="Uji koneksi ke endpoint API Siakad">
                        <i class="fas fa-plug"></i> Test API SIAKAD
                    </a>
                    <button @click="openSyncModal" class="solid-btn btn-indigo-solid">
                        <i class="fas fa-sync-alt"></i> Sinkronisasi SIAKAD
                    </button>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">
                <!-- Filter Card -->
                <div class="filter-card">
                    <div style="font-size:0.875rem;font-weight:700;color:#1e293b;margin-bottom:0.75rem;display:flex;align-items:center;justify-content:space-between;">
                        <span style="display:flex;align-items:center;gap:0.5rem;">
                            <i class="fas fa-filter" style="color:#4f46e5;"></i> Filter Mahasiswa
                        </span>
                        <span v-if="mahasiswas?.total !== undefined" style="font-size:0.8125rem;font-weight:500;color:#64748b;">
                            Total: <strong style="color:#0f172a;">{{ mahasiswas.total }}</strong> mahasiswa
                        </span>
                    </div>

                    <div class="filter-grid">
                        <!-- Search Box -->
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

                        <!-- Status Filter -->
                        <select v-model="status" @change="doFilter" class="filter-input">
                            <option value="">Semua Status</option>
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>

                        <!-- Jurusan Filter -->
                        <select v-model="jurusan" @change="doFilter" class="filter-input">
                            <option value="">Semua Program Studi</option>
                            <option v-for="j in filterOptions.jurusan" :key="j" :value="j">{{ j }}</option>
                        </select>

                        <!-- Angkatan Filter -->
                        <select v-model="angkatan" @change="doFilter" class="filter-input">
                            <option value="">Semua Angkatan</option>
                            <option v-for="a in filterOptions.angkatan" :key="a" :value="a">Angkatan {{ a }}</option>
                        </select>

                        <!-- Semester Filter -->
                        <select v-model="semester" @change="doFilter" class="filter-input">
                            <option value="">Semua Semester</option>
                            <option v-for="s in filterOptions.semester" :key="s" :value="s">Semester {{ s }}</option>
                        </select>

                        <!-- Action Buttons -->
                        <div class="filter-actions">
                            <button @click="doFilter" class="btn-solid-primary" style="padding:0.5625rem 1rem;">
                                <i class="fas fa-search"></i> Terapkan
                            </button>
                            <button v-if="hasFilter()" @click="clearFilter" class="btn-solid-secondary" style="padding:0.5625rem 0.875rem;" title="Reset filter">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="data-card">
                    <div v-if="mahasiswas.data && mahasiswas.data.length > 0">
                        <div class="table-responsive">
                            <table class="solid-table">
                                <thead>
                                    <tr>
                                        <th style="width:40px;text-align:center;">No</th>
                                        <th @click="handleSort('nim')" class="sortable-th">NIM <i class="fas" :class="sortIcon('nim')"></i></th>
                                        <th @click="handleSort('nama_lengkap')" class="sortable-th">Nama Mahasiswa <i class="fas" :class="sortIcon('nama_lengkap')"></i></th>
                                        <th @click="handleSort('jurusan')" class="sortable-th">Program Studi <i class="fas" :class="sortIcon('jurusan')"></i></th>
                                        <th @click="handleSort('angkatan')" class="sortable-th" style="text-align:center;">Angkatan <i class="fas" :class="sortIcon('angkatan')"></i></th>
                                        <th @click="handleSort('semester')" class="sortable-th" style="text-align:center;">Sem <i class="fas" :class="sortIcon('semester')"></i></th>
                                        <th style="text-align:center;">Status</th>
                                        <th style="width:90px;text-align:center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(m, i) in mahasiswas.data" :key="m.id">
                                        <td style="text-align:center;color:#64748b;font-size:0.8125rem;">
                                            {{ mahasiswas.from + i }}
                                        </td>
                                        <td>
                                            <span style="font-family:monospace;font-weight:700;color:#0f172a;background:#f1f5f9;padding:0.2rem 0.4rem;border-radius:0.375rem;font-size:0.8125rem;">
                                                {{ m.nim }}
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-weight:700;color:#0f172a;font-size:0.875rem;">
                                                {{ m.nama_lengkap }}
                                            </div>
                                            <div style="font-size:0.75rem;color:#64748b;">
                                                {{ m.email || m.telepon || '-' }}
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-size:0.8125rem;color:#334155;">{{ m.jurusan || '-' }}</span>
                                        </td>
                                        <td style="text-align:center;font-size:0.8125rem;color:#334155;">
                                            {{ m.angkatan || '-' }}
                                        </td>
                                        <td style="text-align:center;">
                                            <span style="display:inline-block;padding:0.15rem 0.5rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.375rem;font-size:0.75rem;font-weight:600;color:#334155;">
                                                {{ m.semester_hitung ?? m.semester }}
                                            </span>
                                        </td>
                                        <td style="text-align:center;">
                                            <span :class="['solid-badge', m.status_aktif ? 'badge-solid-success' : 'badge-solid-danger']">
                                                {{ m.status_aktif ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td style="text-align:center;">
                                            <div style="display:flex;gap:0.375rem;justify-content:center;">
                                                <Link :href="route('admin.mahasiswa.show', m.id)" class="action-btn-view" title="Lihat Profil Mahasiswa">
                                                    <i class="fas fa-eye"></i>
                                                </Link>
                                                <button
                                                    @click="openImpersonateModal(m)"
                                                    class="action-btn-login"
                                                    title="Login ke portal sebagai mahasiswa ini"
                                                >
                                                    <i class="fas fa-sign-in-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Footer -->
                        <div class="pagination-footer">
                            <span style="font-size:0.8125rem;color:#64748b;">
                                Menampilkan <strong style="color:#0f172a;">{{ mahasiswas.from }}-{{ mahasiswas.to }}</strong> dari <strong style="color:#0f172a;">{{ mahasiswas.total }}</strong> mahasiswa
                            </span>
                            <div class="pagination-btns">
                                <template v-for="link in mahasiswas.links" :key="link.label">
                                    <span v-if="!link.url" class="p-btn p-disabled" v-html="link.label"></span>
                                    <Link v-else :href="link.url" class="p-btn" :class="{ 'p-active': link.active }" v-html="link.label" preserve-state />
                                </template>
                            </div>
                        </div>
                    </div>

                    <div v-else style="text-align:center;padding:4rem 2rem;color:#64748b;">
                        <i class="fas fa-users" style="font-size:2.5rem;color:#cbd5e1;margin-bottom:0.75rem;display:block;"></i>
                        <h4 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0 0 0.25rem;">Tidak Ada Data Mahasiswa</h4>
                        <p style="font-size:0.8125rem;color:#64748b;margin:0 0 1rem;">Tidak ditemukan mahasiswa yang sesuai dengan filter atau kata kunci pencarian.</p>
                        <button v-if="hasFilter()" @click="clearFilter" class="btn-solid-secondary" style="font-size:0.8125rem;">
                            <i class="fas fa-undo"></i> Reset Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Sinkronisasi Siakad -->
        <div v-if="syncModalOpen" class="modal-overlay" @click.self="!syncing && (syncModalOpen = false)">
            <div class="modal-card">
                <div class="modal-header">
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <span style="width:36px;height:36px;border-radius:0.5rem;background:#e0e7ff;color:#4338ca;display:flex;align-items:center;justify-content:center;font-size:1.125rem;">
                            <i class="fas fa-sync-alt" :class="{ 'fa-spin': syncing }"></i>
                        </span>
                        <div>
                            <h4 style="margin:0;font-size:1rem;font-weight:700;color:#0f172a;">Sinkronisasi Data SIAKAD</h4>
                            <p style="margin:0;font-size:0.75rem;color:#64748b;">Tarik data mahasiswa aktif secara bertahap</p>
                        </div>
                    </div>
                    <button v-if="!syncing" class="modal-close-btn" @click="syncModalOpen = false">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="solid-notice solid-notice-info">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            Proses ini akan mengimpor atau memperbarui data mahasiswa dari database SIAKAD kampus ke database sistem UKT.
                        </div>
                    </div>

                    <div style="margin-top:1rem;">
                        <label style="display:block;font-size:0.8125rem;font-weight:600;color:#334155;margin-bottom:0.375rem;">
                            Pilih Angkatan Mahasiswa <span style="color:#dc2626;">*</span>
                        </label>
                        <select v-model="syncAngkatan" class="modal-select" :disabled="syncing">
                            <option value="">-- Pilih Angkatan --</option>
                            <option v-for="a in filterOptions.angkatan" :key="a" :value="a">Angkatan {{ a }}</option>
                        </select>
                    </div>

                    <div style="margin-top:1rem;">
                        <label style="display:block;font-size:0.8125rem;font-weight:600;color:#334155;margin-bottom:0.375rem;">
                            Ukuran Batch Sinkronisasi
                        </label>
                        <select v-model="syncBatch" class="modal-select" :disabled="syncing">
                            <option value="100">100 Data / Batch (Direkomendasikan)</option>
                            <option value="200">200 Data / Batch</option>
                            <option value="500">500 Data / Batch</option>
                            <option value="semua">Semua Sekaligus</option>
                        </select>
                        <span style="font-size:0.75rem;color:#64748b;display:block;margin-top:0.25rem;">
                            Jika data angkatan banyak, gunakan 100 data/batch agar koneksi API tetap stabil.
                        </span>
                    </div>

                    <div v-if="syncing" style="margin-top:1.25rem;padding:0.75rem 1rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.5rem;text-align:center;">
                        <i class="fas fa-spinner fa-spin" style="color:#4f46e5;font-size:1.5rem;margin-bottom:0.5rem;display:block;"></i>
                        <div style="font-weight:600;color:#1e293b;font-size:0.875rem;">Sedang menghubungkan ke API SIAKAD...</div>
                        <div style="font-size:0.75rem;color:#64748b;">Mohon tunggu, jangan menutup browser Anda.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="solid-btn btn-white-border" :disabled="syncing" @click="syncModalOpen = false">Batal</button>
                    <button class="solid-btn btn-indigo-solid" :disabled="syncing || !syncAngkatan" @click="executeSync">
                        <i class="fas fa-play"></i> {{ syncing ? 'Sinkronisasi Berjalan...' : 'Mulai Sinkronisasi' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Impersonate (Login As) -->
        <div v-if="impersonateModal" class="modal-overlay" @click.self="impersonateModal = null">
            <div class="modal-card">
                <div class="modal-header">
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <span style="width:36px;height:36px;border-radius:0.5rem;background:#e0f2fe;color:#0284c7;display:flex;align-items:center;justify-content:center;font-size:1.125rem;">
                            <i class="fas fa-user-secret"></i>
                        </span>
                        <div>
                            <h4 style="margin:0;font-size:1rem;font-weight:700;color:#0f172a;">Masuk Sebagai Mahasiswa</h4>
                            <p style="margin:0;font-size:0.75rem;color:#64748b;">Fitur Diagnostik & Panduan Bantuan Mahasiswa</p>
                        </div>
                    </div>
                    <button class="modal-close-btn" @click="impersonateModal = null">&times;</button>
                </div>
                <div class="modal-body">
                    <p style="font-size:0.875rem;color:#334155;margin:0 0 0.75rem;">
                        Anda akan dialihkan dan login sebagai mahasiswa berikut:
                    </p>
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.5rem;padding:0.75rem 1rem;">
                        <div style="font-weight:700;color:#0f172a;font-size:0.9375rem;">{{ impersonateModal.nama_lengkap }}</div>
                        <div style="font-size:0.8125rem;color:#64748b;font-family:monospace;">NIM: {{ impersonateModal.nim }} &bull; {{ impersonateModal.jurusan }}</div>
                    </div>
                    <p style="font-size:0.75rem;color:#64748b;margin:0.75rem 0 0;">
                        Anda dapat kembali ke akun admin kapan saja melalui tombol *"Kembali ke Admin"* di header atas.
                    </p>
                </div>
                <div class="modal-footer">
                    <button class="solid-btn btn-white-border" @click="impersonateModal = null">Batal</button>
                    <button class="solid-btn btn-indigo-solid" @click="executeImpersonate">
                        <i class="fas fa-sign-in-alt"></i> Ya, Masuk Sekarang
                    </button>
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
    grid-template-columns: minmax(180px, 1.4fr) minmax(120px, 1fr) minmax(160px, 1.2fr) minmax(130px, 1fr) minmax(130px, 1fr) auto;
    gap: 0.75rem;
    align-items: center;
}
@media (max-width: 1100px) {
    .filter-grid {
        grid-template-columns: 1fr 1fr 1fr;
    }
}
@media (max-width: 640px) {
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
.badge-solid-danger {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
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
.action-btn-login {
    width: 28px;
    height: 28px;
    border-radius: 0.375rem;
    background: #e0f2fe;
    color: #0284c7;
    border: 1px solid #bae6fd;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    cursor: pointer;
}
.action-btn-login:hover {
    background: #bae6fd;
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
.solid-notice {
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    line-height: 1.5;
    display: flex;
    align-items: flex-start;
    gap: 0.625rem;
}
.solid-notice-info {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1e40af;
}
.modal-select {
    width: 100%;
    padding: 0.5625rem 0.875rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    outline: none;
    background: #ffffff;
}
.modal-select:focus {
    border-color: #4f46e5;
}
</style>

