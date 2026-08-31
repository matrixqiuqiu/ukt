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

const deleteItem = (item) => {
    if (item.konfigurasis_count > 0) {
        alert('Komponen ini tidak bisa dihapus karena masih memiliki data konfigurasi biaya.');
        return;
    }
    if (confirm(`Yakin ingin menghapus "${item.nama}"?`)) {
        router.delete(route('admin.komponen-biaya.destroy', item.id), {
            preserveScroll: true,
        });
    }
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
                                                <button class="m-btn m-btn-sm m-btn-danger" @click="deleteItem(item)" title="Hapus" :disabled="item.konfigurasis_count > 0" :style="item.konfigurasis_count > 0 ? 'opacity:0.3;cursor:not-allowed;' : ''">
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
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.form-group { margin-bottom: 1rem; }
.form-label { display: block; font-size: 0.875rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.375rem; }
.form-control { width: 100%; padding: 0.625rem 0.875rem; border: 1px solid var(--gray-300); border-radius: 0.75rem; font-size: 0.875rem; transition: border-color 0.2s, box-shadow 0.2s; }
.form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15); }
.form-error { font-size: 0.75rem; color: var(--danger); margin-top: 0.25rem; }
.toggle-label { display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.875rem; }
.toggle-label input[type="checkbox"] { width: 1rem; height: 1rem; accent-color: var(--primary); }
.modal-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, 0.4); display: flex; align-items: center; justify-content: center; z-index: 100; padding: 1rem; }
.modal-box { background: white; border-radius: 1rem; width: 100%; max-width: 480px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15); }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--gray-200); }
.modal-header h3 { margin: 0; font-size: 1.125rem; font-weight: 700; color: var(--gray-900); }
.modal-close { background: none; border: none; font-size: 1.125rem; color: var(--gray-500); cursor: pointer; padding: 0.25rem; }
.modal-close:hover { color: var(--gray-800); }
.modal-body { padding: 1.5rem; }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.5rem; border-top: 1px solid var(--gray-200); }
.m-btn-danger { background: var(--danger); color: white; }
.m-btn-danger:hover { background: #dc2626; }
.m-btn-warning { background: var(--warning); color: white; }
.m-btn-warning:hover { background: #d97706; }
</style>
