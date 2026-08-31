<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    jurusans: Object,
    fakultas: Array,
});

const editMode = ref(false);
const editId = ref(null);
const search = ref('');
const importInput = ref(null);
const importing = ref(false);

const importForm = useForm({
    file: null,
});

const triggerImport = () => importInput.value?.click();

const handleImportFile = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    importing.value = true;
    importForm.file = file;
    importForm.post(route('admin.jurusan.import'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            importForm.reset('file');
            e.target.value = '';
            importing.value = false;
        },
        onError: () => {
            importing.value = false;
            e.target.value = '';
        },
        onFinish: () => { importing.value = false; },
    });
};

const form = useForm({
    nama: '',
    kode: '',
    kodeps: '',
    fakultas_id: '',
    status_aktif: true,
});

const openEdit = (item) => {
    editMode.value = true;
    editId.value = item.id;
    form.nama = item.nama;
    form.kode = item.kode;
    form.kodeps = item.kodeps || '';
    form.fakultas_id = item.fakultas_id || '';
    form.status_aktif = item.status_aktif;
};

const resetForm = () => {
    editMode.value = false;
    editId.value = null;
    form.clearErrors();
    form.reset();
    form.status_aktif = true;
    form.fakultas_id = '';
};

const submit = () => {
    if (editMode.value) {
        form.put(route('admin.jurusan.update', editId.value), {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });
    } else {
        form.post(route('admin.jurusan.store'), {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });
    }
};

const deleteItem = (item) => {
    if (item.mahasiswas_count > 0) {
        alert('Program studi ini tidak bisa dihapus karena masih memiliki data mahasiswa.');
        return;
    }
    if (confirm(`Yakin ingin menghapus "${item.nama}"?`)) {
        router.delete(route('admin.jurusan.destroy', item.id), {
            preserveScroll: true,
        });
    }
};

const toggleStatus = (item) => {
    router.post(route('admin.jurusan.toggle', item.id), {}, { preserveScroll: true });
};

const doSearch = () => {
    router.get(route('admin.jurusan.index'), { search: search.value }, { preserveState: true });
};
</script>

<template>
    <Head title="Data Program Studi" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="page-heading">Data Program Studi</h2>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <div class="layout-split">
                    <!-- Kiri: Tabel -->
                    <div class="panel-table">
                        <div class="custom-card">
                            <div class="card-header">
                                <h4>Daftar Program Studi</h4>
                                <div class="toolbar-actions">
                                    <div class="input-group input-group--search">
                                        <span class="input-group__text">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11.5" cy="11.5" r="9.5"/><path stroke-linecap="round" d="M18.5 18.5L22 22"/></g></svg>
                                        </span>
                                        <input type="search" class="input" v-model="search" placeholder="Cari program studi..." @keyup.enter="doSearch" />
                                    </div>
                                    <a :href="route('admin.jurusan.export')" class="m-btn m-btn-sm m-btn-secondary">
                                        <i class="fas fa-download"></i> Export
                                    </a>
                                    <button @click="triggerImport" class="m-btn m-btn-sm m-btn-primary" :disabled="importing">
                                        <i class="fas" :class="importing ? 'fa-spinner fa-spin' : 'fa-upload'"></i>
                                        {{ importing ? 'Importing...' : 'Import' }}
                                    </button>
                                    <input ref="importInput" type="file" accept=".xlsx,.xls" class="file-input-hidden" @change="handleImportFile" />
                                </div>
                            </div>
                            <div class="card-body" style="padding:0;">
                                <div v-if="jurusans.data && jurusans.data.length > 0">
                                    <div class="table-responsive">
                                    <table class="m-data-table">
                                        <thead>
                                            <tr>
                                                <th style="width:50px">No</th>
                                                <th>Kode</th>
                                                <th>Kodef (NIM)</th>
                                                <th>Program Studi</th>
                                                <th style="width:80px;text-align:center;">Mahasiswa</th>
                                                <th style="width:100px">Status</th>
                                                <th style="width:100px">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(item, index) in jurusans.data" :key="item.id" :class="{ 'row-active': editMode && editId === item.id }">
                                                <td>{{ jurusans.from + index }}</td>
                                                <td style="font-weight:600;">{{ item.kode }}</td>
                                                <td>{{ item.kodeps || '-' }}</td>
                                                <td>
                                                    <div style="font-weight:600;">{{ item.nama }}</div>
                                                    <div style="font-size:0.75rem;color:var(--gray-500);">{{ item.fakultasRel?.nama || item.fakultas || '-' }}</div>
                                                </td>
                                                <td style="text-align:center;">{{ item.mahasiswas_count }}</td>
                                                <td>
                                                    <span class="m-badge" :class="item.status_aktif ? 'm-badge-success' : 'm-badge-danger'">
                                                        {{ item.status_aktif ? 'Aktif' : 'Nonaktif' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div style="display:flex;gap:0.375rem;">
                                                        <button class="m-btn m-btn-sm" :class="item.status_aktif ? 'm-badge-warning' : 'm-badge-success'" @click="toggleStatus(item)">
                                                            <i :class="item.status_aktif ? 'fas fa-toggle-on' : 'fas fa-toggle-off'"></i>
                                                        </button>
                                                        <button class="m-btn m-btn-sm m-btn-secondary" @click="openEdit(item)">
                                                            <i class="fas fa-pen"></i>
                                                        </button>
                                                        <button class="m-btn m-btn-sm m-btn-danger" @click="deleteItem(item)" :disabled="item.mahasiswas_count > 0" :style="item.mahasiswas_count > 0 ? 'opacity:0.3;cursor:not-allowed;' : ''">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    </div>

                                    <!-- Pagination -->
                                    <div class="pagination-wrap">
                                        <span style="font-size:0.8125rem;color:var(--gray-600);">
                                            Menampilkan {{ jurusans.from }}-{{ jurusans.to }} dari {{ jurusans.total }} data
                                        </span>
                                        <div class="pagination">
                                            <template v-for="link in jurusans.links" :key="link.label">
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
                                    <i class="fas fa-graduation-cap" style="font-size:2.5rem;color:var(--gray-300);margin-bottom:1rem;display:block;"></i>
                                    Tidak ada data program studi ditemukan.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kanan: Form -->
                    <div class="panel-form">
                        <div class="custom-card">
                            <div class="card-header" style="border-bottom:1px solid var(--gray-200)">
                                <h4>{{ editMode ? 'Edit Program Studi' : 'Tambah Program Studi' }}</h4>
                            </div>
                            <div class="card-body">
                                <form @submit.prevent="submit">
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                                        <div class="form-group">
                                            <label class="form-label">Kode <span style="color:var(--danger);">*</span></label>
                                            <input v-model="form.kode" type="text" class="form-control" placeholder="Contoh: PRD009" maxlength="20" required />
                                            <div v-if="form.errors.kode" class="form-error">{{ form.errors.kode }}</div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Kodef (NIM)</label>
                                            <input v-model="form.kodeps" type="text" class="form-control" placeholder="Contoh: 01" maxlength="10" />
                                            <div v-if="form.errors.kodeps" class="form-error">{{ form.errors.kodeps }}</div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Nama Program Studi <span style="color:var(--danger);">*</span></label>
                                        <input v-model="form.nama" type="text" class="form-control" placeholder="Contoh: S1 Teknik Informatika" required />
                                        <div v-if="form.errors.nama" class="form-error">{{ form.errors.nama }}</div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Fakultas</label>
                                        <select v-model="form.fakultas_id" class="form-control">
                                            <option value="">Pilih Fakultas</option>
                                            <option v-for="f in fakultas" :key="f.id" :value="f.id">{{ f.nama }}</option>
                                        </select>
                                        <div v-if="form.errors.fakultas_id" class="form-error">{{ form.errors.fakultas_id }}</div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Status</label>
                                        <label class="toggle-label">
                                            <input type="checkbox" v-model="form.status_aktif" />
                                            <span>Aktif</span>
                                        </label>
                                    </div>
                                    <div class="form-actions">
                                        <button v-if="editMode" type="button" class="m-btn m-btn-secondary" @click="resetForm">Batal</button>
                                        <button type="submit" class="m-btn m-btn-primary" :disabled="form.processing" style="flex:1">
                                            {{ form.processing ? 'Menyimpan...' : (editMode ? 'Simpan Perubahan' : 'Tambah') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.file-input-hidden { display: none; }
.input-group { display: flex; align-items: center; position: relative; width: 100%; }
.input-group--search .input { width: 100%; padding-left: 0.5rem; }
.input-group__text { position: absolute; left: 0.35rem; top: 50%; transform: translateY(-50%); display: flex; align-items: center; justify-content: center; color: var(--gray-400); pointer-events: none; line-height: 1; font-size: 0.9rem; }
.layout-split { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 1.5rem; align-items: start; }
.panel-table { min-width: 0; }
.custom-card { overflow: hidden; }
.card-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; }
.toolbar-actions { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
.input-group--search { max-width: 220px; flex: 0 1 220px; min-width: 0; }
.table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
.table-responsive .m-data-table { min-width: 640px; }
.row-active { background: #eef2ff !important; }
.form-group { margin-bottom: 1rem; }
.form-label { display: block; font-size: 0.875rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.375rem; }
.form-control { width: 100%; padding: 0.625rem 0.875rem; border: 1px solid var(--gray-300); border-radius: 0.75rem; font-size: 0.875rem; transition: border-color 0.2s, box-shadow 0.2s; }
.form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15); }
.form-error { font-size: 0.75rem; color: var(--danger); margin-top: 0.25rem; }
.toggle-label { display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.875rem; }
.toggle-label input[type="checkbox"] { width: 1rem; height: 1rem; accent-color: var(--primary); }
.form-actions { display: flex; gap: 0.5rem; margin-top: 1.25rem; }
.pagination-wrap { padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--gray-100); gap: 1rem; flex-wrap: wrap; }

@media (max-width: 900px) {
    .layout-split { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .container-xl { padding-left: 1rem; padding-right: 1rem; }
    .card-header { flex-direction: column; align-items: stretch; }
    .toolbar-actions { display: grid; grid-template-columns: 1fr 1fr; width: 100%; }
    .toolbar-actions .input-group--search { grid-column: 1 / -1; max-width: 100% !important; flex: 1 1 100% !important; width: 100%; }
    .toolbar-actions .m-btn { flex: 1; justify-content: center; }
    .table-responsive .m-data-table { min-width: 560px; font-size: 0.8125rem; }
    .pagination-wrap { flex-direction: column; align-items: stretch; text-align: center; }
    .pagination { justify-content: center; flex-wrap: wrap; }
}
@media (max-width: 380px) {
    .toolbar-actions { grid-template-columns: 1fr; }
}
</style>
