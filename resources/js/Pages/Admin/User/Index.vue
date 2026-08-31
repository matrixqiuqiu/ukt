<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: Object,
    filters: Object,
});

const editMode = ref(false);
const editId = ref(null);
const search = ref(props.filters?.search || '');
const roleFilter = ref(props.filters?.role || '');

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'mahasiswa',
});

const openEdit = (user) => {
    editMode.value = true;
    editId.value = user.id;
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.role = user.role;
    form.clearErrors();
};

const resetForm = () => {
    editMode.value = false;
    editId.value = null;
    form.clearErrors();
    form.reset();
    form.role = 'mahasiswa';
};

const submit = () => {
    if (editMode.value) {
        form.put(route('admin.user.update', editId.value), {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });
    } else {
        form.post(route('admin.user.store'), {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });
    }
};

const deleteUser = (user) => {
    if (confirm(`Yakin ingin menghapus user "${user.name}" (${user.email})?`)) {
        router.delete(route('admin.user.destroy', user.id), {
            preserveScroll: true,
        });
    }
};

const doFilter = () => {
    router.get(route('admin.user.index'), {
        search: search.value,
        role: roleFilter.value,
    }, { preserveState: true, replace: true });
};

const clearFilter = () => {
    search.value = '';
    roleFilter.value = '';
    router.get(route('admin.user.index'), {}, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Pengaturan User" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="page-heading">Pengaturan User</h2>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <div class="layout-split">
                    <!-- Kiri: Tabel -->
                    <div class="panel-table">
                        <div class="custom-card">
                            <div class="card-header">
                                <h4>Daftar User</h4>
                                <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                                    <div class="input-group input-group--search" style="max-width:220px;">
                                        <span class="input-group__text">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11.5" cy="11.5" r="9.5"/><path stroke-linecap="round" d="M18.5 18.5L22 22"/></g></svg>
                                        </span>
                                        <input type="search" class="input" v-model="search" placeholder="Cari nama / email..." @keyup.enter="doFilter" />
                                    </div>
                                    <select v-model="roleFilter" @change="doFilter" class="filter-select" style="max-width:140px;">
                                        <option value="">Semua Role</option>
                                        <option value="admin">Admin</option>
                                        <option value="mahasiswa">Mahasiswa</option>
                                    </select>
                                    <button v-if="search || roleFilter" @click="clearFilter" class="m-btn m-btn-sm m-btn-secondary">
                                        <i class="fas fa-times"></i> Reset
                                    </button>
                                </div>
                            </div>
                            <div class="card-body" style="padding:0;">
                                <div v-if="users.data && users.data.length > 0">
                                    <table class="m-data-table">
                                        <thead>
                                            <tr>
                                                <th style="width:50px">No</th>
                                                <th>Nama</th>
                                                <th>Email</th>
                                                <th style="width:110px">Role</th>
                                                <th style="width:160px">Terdaftar</th>
                                                <th style="width:100px">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(u, index) in users.data" :key="u.id" :class="{ 'row-active': editMode && editId === u.id }">
                                                <td>{{ users.from + index }}</td>
                                                <td>
                                                    <div style="font-weight:600;">{{ u.name }}</div>
                                                    <div v-if="u.id === $page.props.auth.user?.id" style="font-size:0.7rem;color:var(--primary);font-weight:600;">(Anda)</div>
                                                </td>
                                                <td style="font-size:0.8125rem;">{{ u.email }}</td>
                                                <td>
                                                    <span class="m-badge" :class="u.role === 'admin' ? 'm-badge-primary' : 'm-badge-secondary'">
                                                        {{ u.role === 'admin' ? 'Admin' : 'Mahasiswa' }}
                                                    </span>
                                                </td>
                                                <td style="font-size:0.8125rem;color:var(--gray-600);">{{ u.created_at ? new Date(u.created_at).toLocaleDateString('id-ID') : '-' }}</td>
                                                <td>
                                                    <div style="display:flex;gap:0.375rem;">
                                                        <button class="m-btn m-btn-sm m-btn-secondary" @click="openEdit(u)">
                                                            <i class="fas fa-pen"></i>
                                                        </button>
                                                        <button class="m-btn m-btn-sm m-btn-danger" @click="deleteUser(u)" :disabled="u.id === $page.props.auth.user?.id" :style="u.id === $page.props.auth.user?.id ? 'opacity:0.3;cursor:not-allowed;' : ''">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <!-- Pagination -->
                                    <div style="padding:1rem 1.5rem;display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--gray-100);flex-wrap:wrap;gap:0.5rem;">
                                        <span style="font-size:0.8125rem;color:var(--gray-600);">
                                            Menampilkan {{ users.from }}-{{ users.to }} dari {{ users.total }} data
                                        </span>
                                        <div class="pagination">
                                            <template v-for="link in users.links" :key="link.label">
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
                                    <i class="fas fa-users" style="font-size:2.5rem;color:var(--gray-300);margin-bottom:1rem;display:block;"></i>
                                    Tidak ada user ditemukan.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kanan: Form -->
                    <div class="panel-form">
                        <div class="custom-card">
                            <div class="card-header" style="border-bottom:1px solid var(--gray-200)">
                                <h4>{{ editMode ? 'Edit User' : 'Tambah User' }}</h4>
                            </div>
                            <div class="card-body">
                                <form @submit.prevent="submit">
                                    <div class="form-group">
                                        <label class="form-label">Nama <span style="color:var(--danger);">*</span></label>
                                        <input v-model="form.name" type="text" class="form-control" placeholder="Nama lengkap" required />
                                        <div v-if="form.errors.name" class="form-error">{{ form.errors.name }}</div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Email <span style="color:var(--danger);">*</span></label>
                                        <input v-model="form.email" type="email" class="form-control" placeholder="user@ubg.ac.id" required />
                                        <div v-if="form.errors.email" class="form-error">{{ form.errors.email }}</div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">
                                            Password <span v-if="!editMode" style="color:var(--danger);">*</span>
                                            <span v-else style="color:var(--gray-500);font-weight:400;font-size:0.75rem;">(kosongkan jika tidak diubah)</span>
                                        </label>
                                        <input v-model="form.password" type="password" class="form-control" :placeholder="editMode ? 'Biarkan kosong' : 'Minimal 8 karakter'" :required="!editMode" minlength="8" autocomplete="new-password" />
                                        <div v-if="form.errors.password" class="form-error">{{ form.errors.password }}</div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Role <span style="color:var(--danger);">*</span></label>
                                        <select v-model="form.role" class="form-control">
                                            <option value="mahasiswa">Mahasiswa</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                        <div v-if="form.errors.role" class="form-error">{{ form.errors.role }}</div>
                                    </div>
                                    <div class="form-actions">
                                        <button v-if="editMode" type="button" class="m-btn m-btn-secondary" @click="resetForm">Batal</button>
                                        <button type="submit" class="m-btn m-btn-primary" :disabled="form.processing" style="flex:1">
                                            {{ form.processing ? 'Menyimpan...' : (editMode ? 'Simpan Perubahan' : 'Tambah User') }}
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
.input-group { display: flex; align-items: center; position: relative; }
.input-group--search .input { padding-left: 2.5rem; }
.input-group__text { position: absolute; left: 0.75rem; display: flex; color: var(--gray-400); }
.layout-split { display: grid; grid-template-columns: 1fr 340px; gap: 1.5rem; align-items: start; }
.row-active { background: #eef2ff !important; }
.filter-select {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.75rem;
    font-size: 0.8125rem;
    color: var(--gray-700);
    background: white;
    transition: border-color 0.2s;
}
.filter-select:focus { outline: none; border-color: var(--primary); }
.form-group { margin-bottom: 1rem; }
.form-label { display: block; font-size: 0.875rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.375rem; }
.form-control { width: 100%; padding: 0.625rem 0.875rem; border: 1px solid var(--gray-300); border-radius: 0.75rem; font-size: 0.875rem; transition: border-color 0.2s, box-shadow 0.2s; }
.form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15); }
.form-error { font-size: 0.75rem; color: var(--danger); margin-top: 0.25rem; }
.form-actions { display: flex; gap: 0.5rem; margin-top: 1.25rem; }
.m-badge-primary { background: #eef2ff; color: #4f46e5; }
.m-badge-secondary { background: #f3f4f6; color: #374151; }

@media (max-width: 900px) {
    .layout-split { grid-template-columns: 1fr; }
}
</style>
