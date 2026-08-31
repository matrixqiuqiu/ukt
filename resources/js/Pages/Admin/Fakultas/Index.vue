<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    fakultas: Array,
});

const editMode = ref(false);
const editId = ref(null);
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
    importForm.post(route('admin.fakultas.import'), {
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
    kode: '',
    kodef: '',
    nama: '',
    status_aktif: true,
});

const openEdit = (item) => {
    editMode.value = true;
    editId.value = item.id;
    form.kode = item.kode;
    form.kodef = item.kodef || '';
    form.nama = item.nama;
    form.status_aktif = item.status_aktif;
};

const resetForm = () => {
    editMode.value = false;
    editId.value = null;
    form.clearErrors();
    form.reset();
    form.status_aktif = true;
};

const submit = () => {
    if (editMode.value) {
        form.put(route('admin.fakultas.update', editId.value), {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });
    } else {
        form.post(route('admin.fakultas.store'), {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });
    }
};

const deleteItem = (item) => {
    if (item.jurusans_count > 0) {
        alert('Fakultas ini tidak bisa dihapus karena masih memiliki data jurusan.');
        return;
    }
    if (confirm(`Yakin ingin menghapus "${item.nama}"?`)) {
        router.delete(route('admin.fakultas.destroy', item.id), {
            preserveScroll: true,
        });
    }
};

const toggleStatus = (item) => {
    router.post(route('admin.fakultas.toggle', item.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Data Fakultas" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="page-heading">Data Fakultas</h2>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <div class="layout-split">
                    <!-- Kiri: Tabel -->
                    <div class="panel-table">
                        <div class="custom-card">
                            <div class="card-header">
                                <h4>Daftar Fakultas</h4>
                                <div style="display:flex;gap:0.5rem;">
                                    <a :href="route('admin.fakultas.export')" class="m-btn m-btn-sm m-btn-secondary">
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
                                <div v-if="fakultas && fakultas.length > 0">
                                    <table class="m-data-table">
                                        <thead>
                                            <tr>
                                                <th style="width:50px">No</th>
                                                <th>Kode</th>
                                                <th>Kodef (NIM)</th>
                                                <th>Fakultas</th>
                                                <th style="width:80px">Jurusan</th>
                                                <th style="width:100px">Status</th>
                                                <th style="width:100px">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(item, index) in fakultas" :key="item.id" :class="{ 'row-active': editMode && editId === item.id }">
                                                <td>{{ index + 1 }}</td>
                                                <td style="font-weight:600;">{{ item.kode }}</td>
                                                <td>{{ item.kodef || '-' }}</td>
                                                <td>{{ item.nama }}</td>
                                                <td style="text-align:center;">{{ item.jurusans_count }}</td>
                                                <td>
                                                    <span class="m-badge" :class="item.status_aktif ? 'm-badge-success' : 'm-badge-danger'">
                                                        {{ item.status_aktif ? 'Aktif' : 'Nonaktif' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div style="display:flex;gap:0.375rem;">
                                                        <button class="m-btn m-btn-sm" :class="item.status_aktif ? 'm-badge-warning' : 'm-badge-success'" @click="toggleStatus(item)" :title="item.status_aktif ? 'Nonaktifkan' : 'Aktifkan'">
                                                            <i :class="item.status_aktif ? 'fas fa-toggle-on' : 'fas fa-toggle-off'"></i>
                                                        </button>
                                                        <button class="m-btn m-btn-sm m-btn-secondary" @click="openEdit(item)" title="Edit">
                                                            <i class="fas fa-pen"></i>
                                                        </button>
                                                        <button class="m-btn m-btn-sm m-btn-danger" @click="deleteItem(item)" title="Hapus" :disabled="item.jurusans_count > 0" :style="item.jurusans_count > 0 ? 'opacity:0.3;cursor:not-allowed;' : ''">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-else style="text-align:center;padding:3rem;color:var(--gray-600);">
                                    <i class="fas fa-university" style="font-size:2.5rem;color:var(--gray-300);margin-bottom:1rem;display:block;"></i>
                                    Belum ada data fakultas.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kanan: Form -->
                    <div class="panel-form">
                        <div class="custom-card">
                            <div class="card-header" style="border-bottom:1px solid var(--gray-200)">
                                <h4>{{ editMode ? 'Edit Fakultas' : 'Tambah Fakultas' }}</h4>
                            </div>
                            <div class="card-body">
                                <form @submit.prevent="submit">
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                                        <div class="form-group">
                                            <label class="form-label">Kode <span style="color:var(--danger);">*</span></label>
                                            <input v-model="form.kode" type="text" class="form-control" placeholder="Contoh: FK001" maxlength="20" required />
                                            <div v-if="form.errors.kode" class="form-error">{{ form.errors.kode }}</div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Kodef (NIM)</label>
                                            <input v-model="form.kodef" type="text" class="form-control" placeholder="Contoh: 01" maxlength="10" />
                                            <div v-if="form.errors.kodef" class="form-error">{{ form.errors.kodef }}</div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Nama Fakultas <span style="color:var(--danger);">*</span></label>
                                        <input v-model="form.nama" type="text" class="form-control" placeholder="Contoh: Fakultas Teknik" required />
                                        <div v-if="form.errors.nama" class="form-error">{{ form.errors.nama }}</div>
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
.layout-split { display: grid; grid-template-columns: 1fr 340px; gap: 1.5rem; align-items: start; }
.row-active { background: #eef2ff !important; }
.form-group { margin-bottom: 1rem; }
.form-label { display: block; font-size: 0.875rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.375rem; }
.form-control { width: 100%; padding: 0.625rem 0.875rem; border: 1px solid var(--gray-300); border-radius: 0.75rem; font-size: 0.875rem; transition: border-color 0.2s, box-shadow 0.2s; }
.form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15); }
.form-error { font-size: 0.75rem; color: var(--danger); margin-top: 0.25rem; }
.toggle-label { display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.875rem; }
.toggle-label input[type="checkbox"] { width: 1rem; height: 1rem; accent-color: var(--primary); }
.form-actions { display: flex; gap: 0.5rem; margin-top: 1.25rem; }

@media (max-width: 900px) {
    .layout-split { grid-template-columns: 1fr; }
}
</style>
