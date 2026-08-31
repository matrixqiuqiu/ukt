<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useToast } from '@/composables/useToast';

const props = defineProps({ items: Array });
const { success } = useToast();

const editMode = ref(false);
const editId = ref(null);
const form = ref({ nama: '', semester: 'Ganjil' });

function openEdit(item) {
    editMode.value = true;
    editId.value = item.id;
    form.value = { nama: item.nama, semester: item.semester };
}

function resetForm() {
    editMode.value = false;
    editId.value = null;
    form.value = { nama: '', semester: 'Ganjil' };
}

function submit() {
    if (editMode.value) {
        router.put(route('admin.tahun-akademik.update', editId.value), form.value, {
            onSuccess: () => { resetForm(); success('Tahun akademik berhasil diperbarui.'); },
        });
    } else {
        router.post(route('admin.tahun-akademik.store'), form.value, {
            onSuccess: () => { resetForm(); success('Tahun akademik berhasil ditambahkan.'); },
        });
    }
}

function destroy(id) {
    if (confirm('Yakin hapus tahun akademik ini?')) {
        router.delete(route('admin.tahun-akademik.destroy', id), {
            onSuccess: () => success('Tahun akademik berhasil dihapus.'),
        });
    }
}

function toggle(id) {
    router.post(route('admin.tahun-akademik.toggle', id), {}, {
        onSuccess: () => success('Status tahun akademik berhasil diubah.'),
    });
}
</script>

<template>
    <Head title="Tahun Akademik" />
    <AuthenticatedLayout>
        <div class="page-header">
            <div>
                <h1 class="page-title">Pengaturan Tahun Akademik</h1>
                <p class="page-subtitle">Kelola tahun akademik aktif</p>
            </div>
        </div>

        <div class="layout-split">
            <!-- Kiri: Tabel -->
            <div class="panel-table">
                <div class="custom-card">
                    <div class="card-header">
                        <h4><i class="fas fa-calendar-alt" style="margin-right:0.5rem;"></i> Daftar Tahun Akademik</h4>
                        <span style="font-size:0.75rem;color:var(--gray-500);">{{ items.length }} data</span>
                    </div>
                    <div class="card-body" style="padding:0">
                        <div class="table-responsive">
                        <table class="m-data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tahun Akademik</th>
                                    <th>Semester</th>
                                    <th>Status</th>
                                    <th style="width:140px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="items.length === 0">
                                    <td colspan="5" style="text-align:center;color:var(--gray-400);padding:2.5rem">
                                        <i class="fas fa-calendar" style="font-size:1.75rem;color:var(--gray-300);display:block;margin-bottom:0.5rem;"></i> Belum ada data tahun akademik
                                    </td>
                                </tr>
                                <tr v-for="(item, i) in items" :key="item.id" :class="{ 'row-active': editMode && editId === item.id }">
                                    <td style="font-weight:500;color:var(--gray-500);">{{ i + 1 }}</td>
                                    <td><span style="font-weight:700;color:var(--gray-900);">{{ item.nama }}</span></td>
                                    <td>
                                        <span class="m-badge" :class="item.semester === 'Ganjil' ? 'm-badge-info' : 'm-badge-purple'">
                                            {{ item.semester }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="m-badge" :class="item.is_aktif ? 'm-badge-success' : 'm-badge-secondary'">{{ item.is_aktif ? 'Aktif' : 'Non-aktif' }}</span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="m-btn m-btn-sm" :class="item.is_aktif ? 'm-badge-warning' : 'm-badge-success'" @click="toggle(item.id)" :title="item.is_aktif ? 'Nonaktifkan' : 'Aktifkan'">
                                                <i :class="item.is_aktif ? 'fas fa-toggle-off' : 'fas fa-toggle-on'"></i>
                                            </button>
                                            <button class="m-btn m-btn-sm m-btn-secondary" @click="openEdit(item)" title="Edit">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button class="m-btn m-btn-sm m-btn-danger" @click="destroy(item.id)" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kanan: Form -->
            <div class="panel-form">
                <div class="custom-card">
                    <div class="card-header" style="border-bottom:1px solid var(--gray-200)">
                        <h4 style="margin:0;font-size:1rem;font-weight:700"><i :class="editMode ? 'fas fa-pen' : 'fas fa-plus'" style="margin-right:0.5rem;"></i> {{ editMode ? 'Edit Tahun Akademik' : 'Tambah Tahun Akademik' }}</h4>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <div class="form-group">
                                <label class="form-label">Tahun Akademik</label>
                                <input v-model="form.nama" type="text" class="form-control" placeholder="Contoh: 2025/2026" required />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Semester</label>
                                <select v-model="form.semester" class="form-control" required>
                                    <option value="Ganjil">Ganjil</option>
                                    <option value="Genap">Genap</option>
                                </select>
                            </div>
                            <div class="form-actions">
                                <button v-if="editMode" type="button" class="btn btn-light" @click="resetForm">Batal</button>
                                <button type="submit" class="btn btn-primary" style="flex:1">
                                    {{ editMode ? 'Simpan Perubahan' : 'Tambah' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
.page-title { font-size: 1.5rem; font-weight: 700; margin: 0; letter-spacing: -0.02em; }
.page-subtitle { font-size: 0.875rem; color: var(--gray-500); margin: 0.25rem 0 0; }
.layout-split { display: grid; grid-template-columns: minmax(0,1fr) 340px; gap: 1.5rem; align-items: start; }
.panel-table { min-width: 0; }
.custom-card { overflow: hidden; border: 1px solid var(--gray-200); border-radius: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.card-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--gray-100); background: linear-gradient(to bottom, #fff, #f9fafb); }
.table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
.table-responsive .m-data-table { min-width: 580px; }
.action-btns { display: flex; gap: 0.375rem; }
.m-badge-info { background: #dbeafe; color: #1e40af; }
.m-badge-purple { background: #ede9fe; color: #7c3aed; }
.m-badge-success { background: #d1fae5; color: #065f46; }
.m-badge-secondary { background: var(--gray-100); color: var(--gray-600); }
.m-badge-warning { background: #fef3c7; color: #92400e; }
.m-badge-danger { background: #fee2e2; color: #991b1b; }
.row-active { background: #eef2ff !important; }
.form-group { margin-bottom: 1rem; }
.form-label { display: block; font-size: 0.875rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.375rem; }
.form-control { width: 100%; padding: 0.625rem 0.875rem; border: 1px solid var(--gray-300); border-radius: 0.75rem; font-size: 0.875rem; transition: border-color 0.2s, box-shadow 0.2s; background: #fff; }
.form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15); }
.form-actions { display: flex; gap: 0.5rem; margin-top: 1.25rem; }

@media (max-width: 900px) {
    .layout-split { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .container-xl { padding-left: 1rem; padding-right: 1rem; }
    .page-title { font-size: 1.25rem; }
    .table-responsive .m-data-table { min-width: 540px; font-size: 0.8125rem; }
    .card-header { padding: 0.875rem 1rem; }
}
</style>
