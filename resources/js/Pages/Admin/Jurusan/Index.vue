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

const showDeleteModal = ref(false);
const showBlockedModal = ref(false);
const itemToDelete = ref(null);

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

const confirmDelete = (item) => {
    itemToDelete.value = item;
    if (item.mahasiswas_count > 0) {
        showBlockedModal.value = true;
    } else {
        showDeleteModal.value = true;
    }
};

const executeDelete = () => {
    if (!itemToDelete.value) return;
    router.delete(route('admin.jurusan.destroy', itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            itemToDelete.value = null;
        }
    });
};

const toggleStatus = (item) => {
    router.post(route('admin.jurusan.toggle', item.id), {}, { preserveScroll: true });
};

const doSearch = () => {
    router.get(route('admin.jurusan.index'), { search: search.value }, { preserveState: true });
};

const clearSearch = () => {
    search.value = '';
    router.get(route('admin.jurusan.index'), {}, { preserveState: true });
};
</script>

<template>
    <Head title="Data Program Studi" />
    <AuthenticatedLayout>
        <template #header>
            <div class="page-title-wrap">
                <h2 class="page-heading">Data Program Studi</h2>
                <p class="page-subheading">Kelola master program studi, fakultas terkait, dan kode spesifik NIM</p>
            </div>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <div class="layout-split">
                    <!-- Kiri: Tabel -->
                    <div class="panel-table">
                        <div class="custom-card">
                            <div class="card-header">
                                <div class="header-title-block">
                                    <h4 class="card-main-title">
                                        <i class="fas fa-graduation-cap" style="color:#2563eb;margin-right:0.5rem;"></i>
                                        Daftar Program Studi
                                    </h4>
                                    <span class="badge-total">{{ jurusans.total || 0 }} Prodi</span>
                                </div>
                                <div class="toolbar-actions">
                                    <div class="clean-search-box">
                                        <i class="fas fa-search search-icon"></i>
                                        <input
                                            type="search"
                                            class="clean-search-input"
                                            v-model="search"
                                            placeholder="Cari program studi..."
                                            @keyup.enter="doSearch"
                                        />
                                        <button v-if="search" type="button" class="search-clear-btn" @click="clearSearch" title="Hapus pencarian">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <a :href="route('admin.jurusan.export')" class="toolbar-btn btn-export">
                                        <i class="fas fa-download"></i> Export
                                    </a>
                                    <button type="button" @click="triggerImport" class="toolbar-btn btn-import" :disabled="importing">
                                        <i class="fas" :class="importing ? 'fa-spinner fa-pulse' : 'fa-upload'"></i>
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
                                                    <th style="width:110px">Kode</th>
                                                    <th style="width:110px">Kodef (NIM)</th>
                                                    <th>Program Studi & Fakultas</th>
                                                    <th style="width:90px;text-align:center;">Mahasiswa</th>
                                                    <th style="width:95px;text-align:center;">Status</th>
                                                    <th style="width:115px;text-align:right;">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(item, index) in jurusans.data" :key="item.id" :class="{ 'row-active': editMode && editId === item.id }">
                                                    <td style="color:#64748b;font-weight:500;">{{ jurusans.from + index }}</td>
                                                    <td style="font-weight:700;color:#0f172a;font-family:monospace;">{{ item.kode }}</td>
                                                    <td style="font-family:monospace;color:#475569;">{{ item.kodeps || '-' }}</td>
                                                    <td>
                                                        <div style="font-weight:700;color:#0f172a;font-size:0.9375rem;">{{ item.nama }}</div>
                                                        <div style="font-size:0.75rem;color:#64748b;margin-top:0.125rem;">
                                                            <i class="fas fa-university" style="margin-right:0.25rem;"></i>
                                                            {{ item.fakultasRel?.nama || item.fakultas || 'Belum diatur' }}
                                                        </div>
                                                    </td>
                                                    <td style="text-align:center;">
                                                        <span class="mhs-counter" :class="item.mahasiswas_count > 0 ? 'mhs-has-data' : 'mhs-zero'">
                                                            {{ item.mahasiswas_count }}
                                                        </span>
                                                    </td>
                                                    <td style="text-align:center;">
                                                        <span class="m-badge" :class="item.status_aktif ? 'm-badge-success' : 'm-badge-danger'">
                                                            {{ item.status_aktif ? 'Aktif' : 'Nonaktif' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="action-btns-wrap">
                                                            <button
                                                                type="button"
                                                                class="m-btn m-btn-sm"
                                                                :class="item.status_aktif ? 'm-btn-warning' : 'm-btn-success'"
                                                                @click="toggleStatus(item)"
                                                                :title="item.status_aktif ? 'Nonaktifkan' : 'Aktifkan'"
                                                            >
                                                                <i :class="item.status_aktif ? 'fas fa-toggle-on' : 'fas fa-toggle-off'"></i>
                                                            </button>
                                                            <button
                                                                type="button"
                                                                class="m-btn m-btn-sm m-btn-secondary"
                                                                @click="openEdit(item)"
                                                                title="Edit"
                                                            >
                                                                <i class="fas fa-pen"></i>
                                                            </button>
                                                            <button
                                                                type="button"
                                                                class="m-btn m-btn-sm m-btn-danger"
                                                                @click="confirmDelete(item)"
                                                                title="Hapus"
                                                            >
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
                                        <span class="pagination-info">
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
                                <div v-else class="empty-state-box">
                                    <i class="fas fa-graduation-cap"></i>
                                    <p>Tidak ada data program studi ditemukan.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kanan: Form -->
                    <div class="panel-form">
                        <div class="custom-card">
                            <div class="card-header">
                                <h4 class="card-main-title">
                                    <i :class="editMode ? 'fas fa-pen' : 'fas fa-plus'" style="color:#2563eb;margin-right:0.5rem;"></i>
                                    {{ editMode ? 'Edit Program Studi' : 'Tambah Program Studi' }}
                                </h4>
                            </div>
                            <div class="card-body">
                                <form @submit.prevent="submit">
                                    <div class="form-grid-2">
                                        <div class="form-group">
                                            <label class="form-label">Kode <span style="color:#dc2626;">*</span></label>
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
                                        <label class="form-label">Nama Program Studi <span style="color:#dc2626;">*</span></label>
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
                                            <span style="font-weight:500;color:#334155;">Aktif</span>
                                        </label>
                                    </div>
                                    <div class="form-actions">
                                        <button v-if="editMode" type="button" class="m-btn m-btn-secondary" @click="resetForm">Batal</button>
                                        <button type="submit" class="m-btn m-btn-primary" :disabled="form.processing" style="flex:1;">
                                            <i class="fas" :class="form.processing ? 'fa-spinner fa-pulse' : (editMode ? 'fa-save' : 'fa-plus')"></i>
                                            {{ form.processing ? 'Menyimpan...' : (editMode ? 'Simpan Perubahan' : 'Tambah Prodi') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teleport Modals -->
        <Teleport to="body">
            <!-- Modal Blocked Deletion -->
            <div v-if="showBlockedModal" class="modal-overlay" @click.self="showBlockedModal = false">
                <div class="modal-box" style="max-width: 440px;">
                    <div class="modal-header">
                        <h3 style="color: #d97706; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-exclamation-triangle"></i> Tidak Dapat Dihapus
                        </h3>
                        <button class="modal-close" @click="showBlockedModal = false"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="modal-body" style="padding: 1.25rem 1.5rem;">
                        <p style="margin: 0 0 1rem; color: #475569; font-size: 0.875rem; line-height: 1.5;">
                            Program studi <strong>{{ itemToDelete?.nama }}</strong> tidak dapat dihapus karena masih memiliki <strong>{{ itemToDelete?.mahasiswas_count }}</strong> data mahasiswa terdaftar.
                        </p>
                        <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 0.5rem; padding: 0.875rem; font-size: 0.8125rem; color: #92400e;">
                            Silakan pindahkan atau sesuaikan data mahasiswa terlebih dahulu jika ingin menghapus prodi ini.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="m-btn m-btn-primary" @click="showBlockedModal = false">Mengerti</button>
                    </div>
                </div>
            </div>

            <!-- Modal Delete Confirmation -->
            <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
                <div class="modal-box" style="max-width: 440px;">
                    <div class="modal-header">
                        <h3 style="color: #991b1b; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-trash-alt"></i> Konfirmasi Hapus
                        </h3>
                        <button class="modal-close" @click="showDeleteModal = false"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="modal-body" style="padding: 1.25rem 1.5rem;">
                        <p style="margin: 0; color: #475569; font-size: 0.875rem; line-height: 1.5;">
                            Apakah Anda yakin ingin menghapus program studi <strong>{{ itemToDelete?.nama }}</strong> ({{ itemToDelete?.kode }})?
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="m-btn m-btn-secondary" @click="showDeleteModal = false">Batal</button>
                        <button type="button" class="m-btn m-btn-danger" @click="executeDelete">
                            <i class="fas fa-trash"></i> Ya, Hapus
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.page-title-wrap { margin-bottom: 0.25rem; }
.page-heading { font-size: 1.375rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-subheading { font-size: 0.875rem; color: #64748b; margin: 0.25rem 0 0; }

.file-input-hidden { display: none; }

.layout-split {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 340px;
    gap: 1.5rem;
    align-items: start;
}
.panel-table { min-width: 0; }
.custom-card {
    overflow: hidden;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
}
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.875rem;
    padding: 1rem 1.25rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.header-title-block {
    display: flex;
    align-items: center;
    gap: 0.625rem;
}
.card-main-title {
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}
.badge-total {
    background: #e2e8f0;
    color: #475569;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
}

.toolbar-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

/* Crisp Aligned Search Box */
.clean-search-box {
    position: relative;
    display: flex;
    align-items: center;
    width: 230px;
    height: 36px;
    box-sizing: border-box;
}
.clean-search-box .search-icon {
    position: absolute;
    left: 0.75rem;
    color: #94a3b8;
    font-size: 0.8125rem;
    pointer-events: none;
}
.clean-search-input {
    width: 100%;
    height: 36px;
    box-sizing: border-box;
    padding: 0 1.875rem 0 2.25rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    background: #ffffff;
    color: #0f172a;
    outline: none;
    line-height: normal;
    transition: all 0.2s ease;
}
.clean-search-input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}
.clean-search-box .search-clear-btn {
    position: absolute;
    right: 0.5rem;
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    font-size: 0.75rem;
    padding: 0.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
}
.clean-search-box .search-clear-btn:hover {
    color: #0f172a;
}

/* Aligned Toolbar Buttons */
.toolbar-btn {
    height: 36px;
    box-sizing: border-box;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    padding: 0 0.875rem;
    font-size: 0.8125rem;
    font-weight: 600;
    border-radius: 0.5rem;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.15s ease;
    border: 1px solid transparent;
    line-height: 1;
}
.btn-export {
    background: #ffffff;
    color: #334155;
    border-color: #cbd5e1;
}
.btn-export:hover {
    background: #f1f5f9;
    color: #0f172a;
    border-color: #94a3b8;
}
.btn-import {
    background: #2563eb;
    color: #ffffff;
    border-color: #2563eb;
}
.btn-import:hover {
    background: #1d4ed8;
}

.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.table-responsive .m-data-table { min-width: 620px; }

.mhs-counter {
    display: inline-block;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 700;
}
.mhs-has-data { background: #eff6ff; color: #1d4ed8; }
.mhs-zero { background: #f1f5f9; color: #94a3b8; }

.action-btns-wrap {
    display: flex;
    gap: 0.375rem;
    justify-content: flex-end;
}

.row-active { background: #eff6ff !important; }

.form-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
.form-group { margin-bottom: 1rem; }
.form-label { display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.375rem; }
.form-control {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    transition: border-color 0.2s, box-shadow 0.2s;
    background: #fff;
}
.form-control:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }
.form-error { font-size: 0.75rem; color: #dc2626; margin-top: 0.25rem; }
.toggle-label { display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.875rem; }
.toggle-label input[type="checkbox"] { width: 1.125rem; height: 1.125rem; accent-color: #2563eb; }
.form-actions { display: flex; gap: 0.5rem; margin-top: 1.25rem; }

.pagination-wrap {
    padding: 0.875rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #f1f5f9;
    gap: 1rem;
    flex-wrap: wrap;
}
.pagination-info { font-size: 0.8125rem; color: #64748b; }
.pagination { display: flex; gap: 0.25rem; }
.page-item .page-link {
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
    border: 1px solid #e2e8f0;
    font-size: 0.8125rem;
    color: #475569;
    background: #ffffff;
    text-decoration: none;
    display: inline-block;
}
.page-item.active .page-link {
    background: #2563eb;
    color: #ffffff;
    border-color: #2563eb;
    font-weight: 600;
}
.page-item.disabled .page-link {
    color: #94a3b8;
    background: #f8fafc;
    border-color: #e2e8f0;
}

.empty-state-box {
    text-align: center;
    padding: 3rem 1.5rem;
    color: #64748b;
}
.empty-state-box i {
    font-size: 2.5rem;
    color: #cbd5e1;
    margin-bottom: 0.75rem;
    display: block;
}
.empty-state-box p { margin: 0; font-size: 0.875rem; }

/* Modal */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
    padding: 1rem;
}
.modal-box {
    background: white;
    border-radius: 1rem;
    width: 100%;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}
.modal-header h3 { margin: 0; font-size: 1.125rem; font-weight: 700; }
.modal-close { background: none; border: none; font-size: 1.125rem; color: #64748b; cursor: pointer; padding: 0.25rem; }
.modal-close:hover { color: #0f172a; }
.modal-body { padding: 1.5rem; }
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
    border-bottom-left-radius: 1rem;
    border-bottom-right-radius: 1rem;
}

@media (max-width: 900px) {
    .layout-split { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .container-xl { padding-left: 1rem; padding-right: 1rem; }
    .card-header { flex-direction: column; align-items: stretch; }
    .toolbar-actions { display: grid; grid-template-columns: 1fr 1fr; width: 100%; }
    .clean-search-box { grid-column: 1 / -1; width: 100%; }
    .toolbar-btn { width: 100%; justify-content: center; }
    .pagination-wrap { flex-direction: column; align-items: stretch; text-align: center; }
    .pagination { justify-content: center; flex-wrap: wrap; }
}
@media (max-width: 420px) {
    .form-grid-2 { grid-template-columns: 1fr; }
    .toolbar-actions { grid-template-columns: 1fr; }
}
</style>
