<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: Object,
    filters: Object,
    counts: Object,
});

const editMode = ref(false);
const editId = ref(null);
const search = ref(props.filters?.search || '');
const roleFilter = ref(props.filters?.role || 'admin');
const deleteModal = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
});

const openEdit = (user) => {
    editMode.value = true;
    editId.value = user.id;
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.clearErrors();
};

const resetForm = () => {
    editMode.value = false;
    editId.value = null;
    form.clearErrors();
    form.reset();
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

const confirmDelete = (user) => {
    deleteModal.value = user;
};

const executeDelete = () => {
    if (!deleteModal.value) return;
    router.delete(route('admin.user.destroy', deleteModal.value.id), {
        preserveScroll: true,
        onFinish: () => {
            deleteModal.value = null;
        },
    });
};

const setRoleTab = (role) => {
    roleFilter.value = role;
    doFilter();
};

const doFilter = () => {
    router.get(route('admin.user.index'), {
        search: search.value,
        role: roleFilter.value,
    }, { preserveState: true, replace: true });
};

const clearFilter = () => {
    search.value = '';
    roleFilter.value = 'admin';
    router.get(route('admin.user.index'), { role: 'admin' }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Pengaturan User" />
    <AuthenticatedLayout>
        <template #header>
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div>
                    <h2 class="page-heading" style="font-size:1.375rem;font-weight:700;color:#0f172a;margin-bottom:0.25rem;">Pengaturan User & Administrator</h2>
                    <p style="font-size:0.875rem;color:#64748b;margin:0;">Kelola hak akses administrator dan staf pengelola sistem UKT</p>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">
                <!-- Info Notice -->
                <div class="solid-info-box">
                    <div style="display:flex;align-items:flex-start;gap:0.75rem;">
                        <i class="fas fa-info-circle" style="color:#4f46e5;font-size:1.125rem;margin-top:0.125rem;"></i>
                        <div style="font-size:0.8125rem;color:#334155;line-height:1.5;">
                            <strong>Tentang Akun Mahasiswa:</strong> Akun mahasiswa dibuat dan disinkronkan secara otomatis dari <strong>API SIAKAD</strong> saat mahasiswa login atau saat Anda menekan tombol sinkron di menu <Link :href="route('admin.mahasiswa.index')" style="color:#4f46e5;font-weight:600;text-decoration:underline;">Master Mahasiswa</Link>. Form di bawah ini khusus untuk menambahkan <strong>Staf Administrator</strong> pengelola sistem.
                        </div>
                    </div>
                </div>

                <div class="layout-split">
                    <!-- Kiri: Tabel User -->
                    <div class="panel-table">
                        <div class="solid-card">
                            <!-- Tabs & Search -->
                            <div class="card-top-bar">
                                <div class="role-tabs">
                                    <button
                                        class="tab-btn"
                                        :class="{ 'tab-btn-active': roleFilter === 'admin' }"
                                        @click="setRoleTab('admin')"
                                    >
                                        <i class="fas fa-user-shield"></i> Administrator
                                        <span class="tab-badge">{{ counts?.admin || 0 }}</span>
                                    </button>
                                    <button
                                        class="tab-btn"
                                        :class="{ 'tab-btn-active': roleFilter === 'all' }"
                                        @click="setRoleTab('all')"
                                    >
                                        <i class="fas fa-users"></i> Semua Akun
                                        <span class="tab-badge">{{ counts?.total || 0 }}</span>
                                    </button>
                                </div>

                                <div class="search-box">
                                    <i class="fas fa-search search-icon"></i>
                                    <input
                                        type="search"
                                        class="search-input"
                                        v-model="search"
                                        placeholder="Cari nama / email..."
                                        @keyup.enter="doFilter"
                                    />
                                    <button v-if="search" @click="clearFilter" class="clear-btn" title="Reset filter">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Table -->
                            <div v-if="users.data && users.data.length > 0">
                                <div class="table-responsive">
                                    <table class="solid-table">
                                        <thead>
                                            <tr>
                                                <th style="width:40px;text-align:center;">No</th>
                                                <th>Nama Pengguna</th>
                                                <th>Email</th>
                                                <th style="text-align:center;width:120px;">Role</th>
                                                <th style="width:130px;">Terdaftar</th>
                                                <th style="width:90px;text-align:center;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(u, index) in users.data" :key="u.id" :class="{ 'row-active': editMode && editId === u.id }">
                                                <td style="text-align:center;color:#64748b;font-size:0.8125rem;">
                                                    {{ users.from + index }}
                                                </td>
                                                <td>
                                                    <div style="font-weight:700;color:#0f172a;font-size:0.875rem;">
                                                        {{ u.name }}
                                                        <span v-if="u.id === $page.props.auth.user?.id" style="font-size:0.6875rem;color:#4f46e5;background:#eef2ff;padding:0.1rem 0.35rem;border-radius:0.25rem;margin-left:0.25rem;font-weight:600;">
                                                            (Anda)
                                                        </span>
                                                    </div>
                                                </td>
                                                <td style="font-size:0.8125rem;color:#334155;font-family:monospace;">
                                                    {{ u.email }}
                                                </td>
                                                <td style="text-align:center;">
                                                    <span :class="['solid-badge', u.role === 'admin' ? 'badge-admin' : 'badge-mahasiswa']">
                                                        {{ u.role === 'admin' ? 'Administrator' : 'Mahasiswa' }}
                                                    </span>
                                                </td>
                                                <td style="font-size:0.75rem;color:#64748b;">
                                                    {{ u.created_at ? new Date(u.created_at).toLocaleDateString('id-ID') : '-' }}
                                                </td>
                                                <td style="text-align:center;">
                                                    <div style="display:flex;gap:0.375rem;justify-content:center;">
                                                        <button class="action-btn-edit" @click="openEdit(u)" title="Edit Akun">
                                                            <i class="fas fa-pen"></i>
                                                        </button>
                                                        <button
                                                            class="action-btn-delete"
                                                            @click="confirmDelete(u)"
                                                            :disabled="u.id === $page.props.auth.user?.id"
                                                            :title="u.id === $page.props.auth.user?.id ? 'Tidak dapat menghapus akun sendiri' : 'Hapus User'"
                                                            :style="u.id === $page.props.auth.user?.id ? 'opacity:0.3;cursor:not-allowed;' : ''"
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
                                <div class="pagination-footer">
                                    <span style="font-size:0.8125rem;color:#64748b;">
                                        Menampilkan <strong style="color:#0f172a;">{{ users.from }}-{{ users.to }}</strong> dari <strong style="color:#0f172a;">{{ users.total }}</strong> user
                                    </span>
                                    <div class="pagination-btns">
                                        <template v-for="link in users.links" :key="link.label">
                                            <span v-if="!link.url" class="p-btn p-disabled" v-html="link.label"></span>
                                            <Link v-else :href="link.url" class="p-btn" :class="{ 'p-active': link.active }" v-html="link.label" preserve-state />
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <div v-else style="text-align:center;padding:4rem 2rem;color:#64748b;">
                                <i class="fas fa-users" style="font-size:2.5rem;color:#cbd5e1;margin-bottom:0.75rem;display:block;"></i>
                                <h4 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0 0 0.25rem;">Tidak Ada User</h4>
                                <p style="font-size:0.8125rem;color:#64748b;margin:0;">Tidak ditemukan akun user yang sesuai kriteria.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Kanan: Form Tambah/Edit Admin -->
                    <div class="panel-form">
                        <div class="solid-card">
                            <div class="form-header">
                                <h4 style="margin:0;font-size:0.9375rem;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:0.5rem;">
                                    <i class="fas" :class="editMode ? 'fa-user-edit' : 'fa-user-plus'" style="color:#4f46e5;"></i>
                                    {{ editMode ? 'Edit Administrator' : 'Tambah Administrator' }}
                                </h4>
                            </div>
                            <div class="form-body">
                                <form @submit.prevent="submit">
                                    <div class="m-form-group">
                                        <label class="m-form-label">Nama Lengkap <span style="color:#dc2626;">*</span></label>
                                        <input v-model="form.name" type="text" class="m-form-input" placeholder="Contoh: Budi Santoso, M.Kom" required />
                                        <div v-if="form.errors.name" class="m-form-error">{{ form.errors.name }}</div>
                                    </div>

                                    <div class="m-form-group">
                                        <label class="m-form-label">Email Login <span style="color:#dc2626;">*</span></label>
                                        <input v-model="form.email" type="email" class="m-form-input" placeholder="admin@ubg.ac.id" required />
                                        <div v-if="form.errors.email" class="m-form-error">{{ form.errors.email }}</div>
                                    </div>

                                    <div class="m-form-group">
                                        <label class="m-form-label">
                                            Password <span v-if="!editMode" style="color:#dc2626;">*</span>
                                            <span v-else style="color:#64748b;font-weight:400;font-size:0.75rem;">(kosongkan jika tidak ingin diubah)</span>
                                        </label>
                                        <input
                                            v-model="form.password"
                                            type="password"
                                            class="m-form-input"
                                            :placeholder="editMode ? 'Biarkan kosong' : 'Minimal 8 karakter'"
                                            :required="!editMode"
                                            minlength="8"
                                            autocomplete="new-password"
                                        />
                                        <div v-if="form.errors.password" class="m-form-error">{{ form.errors.password }}</div>
                                    </div>

                                    <div class="form-actions">
                                        <button v-if="editMode" type="button" class="btn-solid-secondary" @click="resetForm">
                                            Batal
                                        </button>
                                        <button type="submit" class="btn-solid-primary" :disabled="form.processing" style="flex:1;">
                                            <i class="fas fa-save"></i>
                                            {{ form.processing ? 'Menyimpan...' : (editMode ? 'Simpan Perubahan' : 'Tambah Admin') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus User -->
        <div v-if="deleteModal" class="modal-overlay" @click.self="deleteModal = null">
            <div class="modal-box">
                <div class="modal-header">
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <span style="width:32px;height:32px;border-radius:0.375rem;background:#fee2e2;color:#dc2626;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-trash-alt"></i>
                        </span>
                        <h4 style="margin:0;font-size:1rem;font-weight:700;color:#0f172a;">Hapus Pengguna</h4>
                    </div>
                    <button class="modal-close" @click="deleteModal = null">&times;</button>
                </div>
                <div class="modal-body">
                    <p style="font-size:0.875rem;color:#334155;margin:0 0 0.75rem;">
                        Apakah Anda yakin ingin menghapus akun user berikut?
                    </p>
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.5rem;padding:0.75rem 1rem;">
                        <div style="font-weight:700;color:#0f172a;">{{ deleteModal.name }}</div>
                        <div style="font-size:0.8125rem;color:#64748b;font-family:monospace;">{{ deleteModal.email }} (Role: {{ deleteModal.role }})</div>
                    </div>
                    <p style="font-size:0.75rem;color:#dc2626;margin:0.75rem 0 0;">
                        Tindakan ini permanen dan tidak dapat dibatalkan.
                    </p>
                </div>
                <div class="modal-footer">
                    <button class="btn-solid-secondary" @click="deleteModal = null">Batal</button>
                    <button class="btn-solid-danger" @click="executeDelete">
                        <i class="fas fa-trash"></i> Ya, Hapus Pengguna
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.solid-info-box {
    background: #ffffff;
    border: 1px solid #e0e7ff;
    border-left: 4px solid #4f46e5;
    border-radius: 0.5rem;
    padding: 0.875rem 1.125rem;
    margin-bottom: 1.25rem;
}
.layout-split {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 1.25rem;
    align-items: start;
}
@media (max-width: 992px) {
    .layout-split {
        grid-template-columns: 1fr;
    }
}
.solid-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    overflow: hidden;
}
.card-top-bar {
    padding: 0.875rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
}
.role-tabs {
    display: flex;
    gap: 0.375rem;
}
.tab-btn {
    border: 1px solid #e2e8f0;
    background: #ffffff;
    padding: 0.375rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    transition: all 0.15s;
}
.tab-btn:hover {
    background: #f1f5f9;
}
.tab-btn-active {
    background: #1e293b !important;
    color: #ffffff !important;
    border-color: #1e293b !important;
}
.tab-badge {
    padding: 0.1rem 0.35rem;
    border-radius: 0.25rem;
    font-size: 0.6875rem;
    background: #f1f5f9;
    color: #334155;
}
.tab-btn-active .tab-badge {
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff;
}

.search-box {
    position: relative;
    display: flex;
    align-items: center;
    min-width: 200px;
}
.search-icon {
    position: absolute;
    left: 0.75rem;
    color: #94a3b8;
    font-size: 0.75rem;
}
.search-input {
    width: 100%;
    padding: 0.4375rem 1.75rem 0.4375rem 2rem;
    font-size: 0.8125rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    background: #ffffff;
    outline: none;
}
.search-input:focus {
    border-color: #4f46e5;
}
.clear-btn {
    position: absolute;
    right: 0.5rem;
    border: none;
    background: none;
    color: #94a3b8;
    cursor: pointer;
    font-size: 0.75rem;
}

/* Table */
.table-responsive {
    overflow-x: auto;
}
.solid-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
}
.solid-table th {
    background: #ffffff;
    color: #475569;
    font-weight: 700;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}
.solid-table td {
    padding: 0.875rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.solid-table tbody tr:hover {
    background: #f8fafc;
}
.row-active {
    background: #eef2ff !important;
}

.solid-badge {
    display: inline-block;
    padding: 0.2rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}
.badge-admin {
    background: #e0e7ff;
    color: #3730a3;
    border: 1px solid #c7d2fe;
}
.badge-mahasiswa {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.action-btn-edit {
    width: 28px;
    height: 28px;
    border-radius: 0.375rem;
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #cbd5e1;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
}
.action-btn-edit:hover {
    background: #e2e8f0;
}
.action-btn-delete {
    width: 28px;
    height: 28px;
    border-radius: 0.375rem;
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
}
.action-btn-delete:hover {
    background: #fee2e2;
}

/* Form */
.form-header {
    padding: 0.875rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}
.form-body {
    padding: 1.25rem;
}
.m-form-group {
    margin-bottom: 1rem;
}
.m-form-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 0.375rem;
}
.m-form-input {
    width: 100%;
    padding: 0.5625rem 0.875rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    color: #1e293b;
    outline: none;
}
.m-form-input:focus {
    border-color: #4f46e5;
}
.m-form-error {
    font-size: 0.75rem;
    color: #dc2626;
    margin-top: 0.25rem;
}
.form-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1.25rem;
}

/* Solid Buttons */
.btn-solid-primary {
    background: #4f46e5;
    color: #ffffff;
    border: none;
    border-radius: 0.5rem;
    padding: 0.5625rem 1rem;
    font-weight: 600;
    font-size: 0.8125rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
}
.btn-solid-primary:hover {
    background: #4338ca;
}
.btn-solid-secondary {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.5625rem 0.875rem;
    font-weight: 600;
    font-size: 0.8125rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
}
.btn-solid-secondary:hover {
    background: #e2e8f0;
}
.btn-solid-danger {
    background: #dc2626;
    color: #ffffff;
    border: none;
    border-radius: 0.5rem;
    padding: 0.5625rem 1rem;
    font-weight: 600;
    font-size: 0.8125rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
}
.btn-solid-danger:hover {
    background: #b91c1c;
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
.modal-box {
    background: #ffffff;
    border-radius: 0.75rem;
    width: 100%;
    max-width: 440px;
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
.modal-close {
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
</style>

