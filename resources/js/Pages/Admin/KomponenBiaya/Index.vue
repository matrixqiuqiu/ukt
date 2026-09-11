<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    komponens: Array,
});

const showModal = ref(false);
const editMode = ref(false);
const editId = ref(null);

const showDeleteModal = ref(false);
const showBlockedModal = ref(false);
const itemToDelete = ref(null);

const form = useForm({
    nama: '',
    kode: '',
    deskripsi: '',
    status_aktif: true,
});

const openCreate = () => {
    editMode.value = false;
    editId.value = null;
    form.reset();
    form.status_aktif = true;
    showModal.value = true;
};

const openEdit = (item) => {
    editMode.value = true;
    editId.value = item.id;
    form.nama = item.nama;
    form.kode = item.kode;
    form.deskripsi = item.deskripsi || '';
    form.status_aktif = item.status_aktif;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.clearErrors();
    form.reset();
};

const submit = () => {
    if (editMode.value) {
        form.put(route('admin.komponen-biaya.update', editId.value), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.komponen-biaya.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    if (item.konfigurasis_count > 0) {
        showBlockedModal.value = true;
    } else {
        showDeleteModal.value = true;
    }
};

const executeDelete = () => {
    if (!itemToDelete.value) return;
    router.delete(route('admin.komponen-biaya.destroy', itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            itemToDelete.value = null;
        }
    });
};

const toggleStatus = (item) => {
    router.post(route('admin.komponen-biaya.toggle', item.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Jenis Komponen Biaya" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="page-heading">Jenis Komponen Biaya</h2>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <div class="custom-card">
                    <div class="card-header">
                        <h4>Daftar Komponen Biaya</h4>
                        <button class="m-btn m-btn-primary" @click="openCreate">
                            <i class="fas fa-plus"></i> Tambah Komponen
                        </button>
                    </div>
                    <div class="card-body">
                        <div v-if="komponens && komponens.length > 0">
                            <table class="m-data-table">
                                <thead>
                                    <tr>
                                        <th style="width:50px">No</th>
                                        <th>Nama Komponen</th>
                                        <th>Kode</th>
                                        <th>Deskripsi</th>
                                        <th style="width:100px">Konfigurasi</th>
                                        <th style="width:100px">Status</th>
                                        <th style="width:120px">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in komponens" :key="item.id">
                                        <td>{{ index + 1 }}</td>
                                        <td style="font-weight:600;">{{ item.nama }}</td>
                                        <td><span class="m-badge" :class="item.status_aktif ? 'm-badge-success' : 'm-badge-danger'">{{ item.kode }}</span></td>
                                        <td style="color:var(--gray-600);font-size:0.8125rem;">{{ item.deskripsi || '-' }}</td>
                                        <td style="text-align:center;">{{ item.konfigurasis_count }}</td>
                                        <td>
                                            <span class="m-badge" :class="item.status_aktif ? 'm-badge-success' : 'm-badge-danger'">
                                                {{ item.status_aktif ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display:flex;gap:0.375rem;">
                                                <button class="m-btn m-btn-sm" :class="item.status_aktif ? 'm-btn-warning' : 'm-btn-success'" @click="toggleStatus(item)" :title="item.status_aktif ? 'Nonaktifkan' : 'Aktifkan'">
                                                    <i :class="item.status_aktif ? 'fas fa-toggle-on' : 'fas fa-toggle-off'"></i>
                                                </button>
                                                <button class="m-btn m-btn-sm m-btn-secondary" @click="openEdit(item)" title="Edit">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <button class="m-btn m-btn-sm m-btn-danger" @click="confirmDelete(item)" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else style="text-align:center;padding:3rem;color:var(--gray-600);">
                            <i class="fas fa-cubes" style="font-size:2.5rem;color:var(--gray-300);margin-bottom:1rem;display:block;"></i>
                            Belum ada komponen biaya. Klik "Tambah Komponen" untuk menambahkan.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Form -->
        <Teleport to="body">
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                <div class="modal-box">
                    <div class="modal-header">
                        <h3>{{ editMode ? 'Edit Komponen Biaya' : 'Tambah Komponen Biaya' }}</h3>
                        <button class="modal-close" @click="closeModal"><i class="fas fa-times"></i></button>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="modal-body">
                            <div class="form-group">
                                <label class="form-label">Nama Komponen <span style="color:var(--danger);">*</span></label>
                                <input v-model="form.nama" type="text" class="form-control" placeholder="Contoh: SPP, UKT, Biaya Praktikum" required />
                                <div v-if="form.errors.nama" class="form-error">{{ form.errors.nama }}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Kode <span style="color:var(--danger);">*</span></label>
                                <input v-model="form.kode" type="text" class="form-control" placeholder="Contoh: SPP, UKT, PRAKTIKUM" maxlength="20" required />
                                <div v-if="form.errors.kode" class="form-error">{{ form.errors.kode }}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Deskripsi</label>
                                <textarea v-model="form.deskripsi" class="form-control" rows="3" placeholder="Deskripsi singkat komponen biaya (opsional)"></textarea>
                                <div v-if="form.errors.deskripsi" class="form-error">{{ form.errors.deskripsi }}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <label class="toggle-label">
                                    <input type="checkbox" v-model="form.status_aktif" />
                                    <span>Aktif</span>
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="m-btn m-btn-secondary" @click="closeModal">Batal</button>
                            <button type="submit" class="m-btn m-btn-primary" :disabled="form.processing">
                                {{ form.processing ? 'Menyimpan...' : (editMode ? 'Simpan Perubahan' : 'Tambah') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

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
                        <p style="margin: 0 0 1rem; color: #475569; font-size: 0.875rem;">
                            Komponen <strong>{{ itemToDelete?.nama }}</strong> tidak dapat dihapus karena masih digunakan dalam <strong>{{ itemToDelete?.konfigurasis_count }}</strong> konfigurasi biaya UKT aktif.
                        </p>
                        <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 0.5rem; padding: 0.875rem; font-size: 0.8125rem; color: #92400e;">
                            Silakan hapus atau ubah konfigurasi biaya terkait terlebih dahulu sebelum menghapus jenis komponen ini.
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
                        <p style="margin: 0 0 1rem; color: #475569; font-size: 0.875rem;">
                            Apakah Anda yakin ingin menghapus komponen <strong>{{ itemToDelete?.nama }}</strong> ({{ itemToDelete?.kode }})?
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
.form-group { margin-bottom: 1rem; }
.form-label { display: block; font-size: 0.875rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.375rem; }
.form-control { width: 100%; padding: 0.625rem 0.875rem; border: 1px solid var(--gray-300); border-radius: 0.75rem; font-size: 0.875rem; transition: border-color 0.2s, box-shadow 0.2s; background: #fff; }
.form-control:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }
.form-error { font-size: 0.75rem; color: var(--danger); margin-top: 0.25rem; }
.toggle-label { display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.875rem; }
.toggle-label input[type="checkbox"] { width: 1rem; height: 1rem; accent-color: #2563eb; }
.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); display: flex; align-items: center; justify-content: center; z-index: 100; padding: 1rem; }
.modal-box { background: white; border-radius: 1rem; width: 100%; max-width: 480px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0; }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--gray-200); }
.modal-header h3 { margin: 0; font-size: 1.125rem; font-weight: 700; color: var(--gray-900); }
.modal-close { background: none; border: none; font-size: 1.125rem; color: var(--gray-500); cursor: pointer; padding: 0.25rem; }
.modal-close:hover { color: var(--gray-800); }
.modal-body { padding: 1.5rem; }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.5rem; border-top: 1px solid var(--gray-200); background: #f8fafc; border-bottom-left-radius: 1rem; border-bottom-right-radius: 1rem; }
.m-btn-danger { background: #dc2626; color: white; }
.m-btn-danger:hover { background: #b91c1c; }
.m-btn-warning { background: #d97706; color: white; }
.m-btn-warning:hover { background: #b45309; }
</style>
