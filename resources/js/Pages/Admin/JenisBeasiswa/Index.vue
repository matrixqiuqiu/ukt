<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    items: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');

const showModal = ref(false);
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const editMode = ref(false);
const editId = ref(null);

const form = ref({
    kode: '',
    nama: '',
    deskripsi: '',
    status_aktif: true,
});

const doFilter = () => {
    const p = {};
    if (search.value) p.search = search.value;
    if (status.value) p.status = status.value;
    router.get(route('admin.jenis-beasiswa.index'), p, { preserveState: true, replace: true });
};

const clearFilter = () => {
    search.value = '';
    status.value = '';
    router.get(route('admin.jenis-beasiswa.index'), {}, { preserveState: true, replace: true });
};

const hasFilter = () => search.value || status.value;

const openCreate = () => {
    editMode.value = false;
    editId.value = null;
    form.value = {
        kode: '',
        nama: '',
        deskripsi: '',
        status_aktif: true,
    };
    showModal.value = true;
};

const openEdit = (row) => {
    editMode.value = true;
    editId.value = row.id;
    form.value = {
        kode: row.kode,
        nama: row.nama,
        deskripsi: row.deskripsi || '',
        status_aktif: !!row.status_aktif,
    };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const submit = () => {
    const url = editMode.value ? route('admin.jenis-beasiswa.update', editId.value) : route('admin.jenis-beasiswa.store');
    const method = editMode.value ? 'put' : 'post';
    router[method](url, form.value, {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const confirmDestroy = (row) => {
    itemToDelete.value = row;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (!itemToDelete.value) return;
    router.delete(route('admin.jenis-beasiswa.destroy', itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            itemToDelete.value = null;
        }
    });
};

const toggle = (row) => {
    router.post(route('admin.jenis-beasiswa.toggle', row.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Data Jenis Beasiswa" />
    <AuthenticatedLayout>
        <template #header>
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div>
                    <h2 class="page-heading" style="font-size:1.375rem;font-weight:700;color:#0f172a;margin-bottom:0.25rem;">Data Jenis Beasiswa</h2>
                    <p style="font-size:0.875rem;color:#64748b;margin:0;">Kelola master kategori dan klasifikasi beasiswa kampus</p>
                </div>
                <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                    <Link :href="route('admin.beasiswa.index')" class="solid-btn btn-white-border" title="Buka Daftar Program Beasiswa">
                        <i class="fas fa-graduation-cap"></i> Daftar Beasiswa
                    </Link>
                    <a :href="route('admin.jenis-beasiswa.export')" class="solid-btn btn-white-border" title="Unduh data jenis beasiswa dalam format Excel">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                    <button @click="openCreate" class="solid-btn btn-indigo-solid">
                        <i class="fas fa-plus"></i> Tambah Jenis
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
                            <i class="fas fa-filter" style="color:#4f46e5;"></i> Filter Jenis Beasiswa
                        </span>
                        <span v-if="items?.total !== undefined" style="font-size:0.8125rem;font-weight:500;color:#64748b;">
                            Total: <strong style="color:#0f172a;">{{ items.total }}</strong> jenis beasiswa
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
                                placeholder="Cari kode atau nama jenis beasiswa..."
                                @keyup.enter="doFilter"
                            />
                        </div>

                        <!-- Status Filter -->
                        <select v-model="status" @change="doFilter" class="filter-input">
                            <option value="">Semua Status</option>
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
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
                    <div v-if="items.data && items.data.length > 0">
                        <div class="table-responsive">
                            <table class="solid-table">
                                <thead>
                                    <tr>
                                        <th style="width:40px;text-align:center;">No</th>
                                        <th style="width:120px;">Kode</th>
                                        <th>Nama Kategori Jenis</th>
                                        <th>Deskripsi Kategori</th>
                                        <th style="text-align:center;width:100px;">Status</th>
                                        <th style="width:110px;text-align:center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, i) in items.data" :key="row.id">
                                        <td style="text-align:center;color:#64748b;font-size:0.8125rem;">
                                            {{ (items.from || 1) + i }}
                                        </td>
                                        <td>
                                            <span style="font-family:monospace;font-weight:700;color:#0f172a;background:#f1f5f9;padding:0.2rem 0.45rem;border-radius:0.375rem;font-size:0.8125rem;">
                                                {{ row.kode }}
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-weight:700;color:#0f172a;font-size:0.875rem;">
                                                {{ row.nama }}
                                            </div>
                                        </td>
                                        <td>
                                            <span style="color:#475569;font-size:0.8125rem;">
                                                {{ row.deskripsi || '-' }}
                                            </span>
                                        </td>
                                        <td style="text-align:center;">
                                            <span :class="['solid-badge', row.status_aktif ? 'badge-solid-success' : 'badge-solid-danger']">
                                                {{ row.status_aktif ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td style="text-align:center;">
                                            <div style="display:flex;gap:0.375rem;justify-content:center;">
                                                <button
                                                    @click="toggle(row)"
                                                    class="action-btn-custom"
                                                    :class="row.status_aktif ? 'btn-toggle-on' : 'btn-toggle-off'"
                                                    :title="row.status_aktif ? 'Nonaktifkan Jenis' : 'Aktifkan Jenis'"
                                                >
                                                    <i :class="row.status_aktif ? 'fas fa-toggle-on' : 'fas fa-toggle-off'"></i>
                                                </button>
                                                <button
                                                    @click="openEdit(row)"
                                                    class="action-btn-custom btn-edit"
                                                    title="Edit Data Jenis"
                                                >
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <button
                                                    @click="confirmDestroy(row)"
                                                    class="action-btn-custom btn-delete"
                                                    title="Hapus Jenis Beasiswa"
                                                >
                                                    <i class="fas fa-trash-alt"></i>
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
                                Menampilkan <strong style="color:#0f172a;">{{ items.from || 1 }}-{{ items.to || items.total }}</strong> dari <strong style="color:#0f172a;">{{ items.total }}</strong> jenis beasiswa
                            </span>
                            <div class="pagination-btns">
                                <template v-for="link in items.links" :key="link.label">
                                    <span v-if="!link.url" class="p-btn p-disabled" v-html="link.label"></span>
                                    <Link v-else :href="link.url" class="p-btn" :class="{ 'p-active': link.active }" v-html="link.label" preserve-state />
                                </template>
                            </div>
                        </div>
                    </div>

                    <div v-else style="text-align:center;padding:4rem 2rem;color:#64748b;">
                        <i class="fas fa-tags" style="font-size:2.5rem;color:#cbd5e1;margin-bottom:0.75rem;display:block;"></i>
                        <h4 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0 0 0.25rem;">Tidak Ada Data Jenis Beasiswa</h4>
                        <p style="font-size:0.8125rem;color:#64748b;margin:0 0 1rem;">Tidak ditemukan jenis beasiswa yang sesuai dengan filter atau kata kunci pencarian.</p>
                        <button v-if="hasFilter()" @click="clearFilter" class="btn-solid-secondary" style="font-size:0.8125rem;">
                            <i class="fas fa-undo"></i> Reset Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teleport Modals -->
        <Teleport to="body">
            <!-- Modal Form Tambah / Edit Jenis Beasiswa -->
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                <div class="modal-card">
                    <div class="modal-header">
                        <div style="display:flex;align-items:center;gap:0.625rem;">
                            <span style="width:36px;height:36px;border-radius:0.5rem;background:#e0e7ff;color:#4338ca;display:flex;align-items:center;justify-content:center;font-size:1.125rem;">
                                <i class="fas" :class="editMode ? 'fa-edit' : 'fa-tags'"></i>
                            </span>
                            <div>
                                <h4 style="margin:0;font-size:1rem;font-weight:700;color:#0f172a;">{{ editMode ? 'Edit Jenis Beasiswa' : 'Tambah Jenis Beasiswa Baru' }}</h4>
                                <p style="margin:0;font-size:0.75rem;color:#64748b;">Kategori klasifikasi untuk pengelompokan program beasiswa</p>
                            </div>
                        </div>
                        <button class="modal-close-btn" @click="closeModal">&times;</button>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="modal-body" style="display:grid;gap:0.875rem;">
                            <div>
                                <label class="modal-label">Kode Jenis <span style="color:#dc2626;">*</span></label>
                                <input v-model="form.kode" class="modal-input" placeholder="Contoh: JB006" required />
                            </div>

                            <div>
                                <label class="modal-label">Nama Kategori Jenis <span style="color:#dc2626;">*</span></label>
                                <input v-model="form.nama" class="modal-input" placeholder="Contoh: Beasiswa Prestasi Non-Akademik" required />
                            </div>

                            <div>
                                <label class="modal-label">Deskripsi / Penjelasan Singkat</label>
                                <textarea v-model="form.deskripsi" class="modal-input" rows="3" placeholder="Keterangan kategori beasiswa..."></textarea>
                            </div>

                            <div style="display:flex;align-items:center;gap:0.5rem;padding-top:0.25rem;">
                                <label class="toggle-checkbox-wrap">
                                    <input type="checkbox" v-model="form.status_aktif" class="checkbox-input" />
                                    <span class="toggle-text">Status Jenis Aktif</span>
                                </label>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="solid-btn btn-white-border" @click="closeModal">Batal</button>
                            <button type="submit" class="solid-btn btn-indigo-solid">
                                <i class="fas fa-save"></i> {{ editMode ? 'Simpan Perubahan' : 'Tambah Jenis' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Hapus Jenis Beasiswa -->
            <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
                <div class="modal-card" style="max-width:440px;">
                    <div class="modal-header">
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <span style="width:36px;height:36px;border-radius:0.5rem;background:#fee2e2;color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:1.125rem;">
                                <i class="fas fa-trash-alt"></i>
                            </span>
                            <div>
                                <h4 style="margin:0;font-size:1rem;font-weight:700;color:#991b1b;">Konfirmasi Hapus</h4>
                                <p style="margin:0;font-size:0.75rem;color:#64748b;">Tindakan ini tidak dapat dibatalkan</p>
                            </div>
                        </div>
                        <button class="modal-close-btn" @click="showDeleteModal = false">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p style="margin:0 0 0.75rem;color:#334155;font-size:0.875rem;line-height:1.5;">
                            Apakah Anda yakin ingin menghapus data jenis beasiswa <strong>{{ itemToDelete?.nama }}</strong> ({{ itemToDelete?.kode }})?
                        </p>
                        <div class="solid-notice" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>Jenis beasiswa yang masih digunakan oleh program beasiswa aktif tidak dapat dihapus.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="solid-btn btn-white-border" @click="showDeleteModal = false">Batal</button>
                        <button type="button" class="solid-btn btn-danger-solid" @click="executeDelete">
                            <i class="fas fa-trash-alt"></i> Ya, Hapus Jenis
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
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
    grid-template-columns: minmax(200px, 1.8fr) minmax(140px, 1.2fr) auto;
    gap: 0.75rem;
    align-items: center;
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

/* Badges */
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
.btn-danger-solid {
    background: #dc2626;
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

/* Action Icon Buttons */
.action-btn-custom {
    width: 28px;
    height: 28px;
    border-radius: 0.375rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    cursor: pointer;
    border: 1px solid transparent;
    text-decoration: none;
    transition: background 0.15s;
}
.btn-toggle-on {
    background: #ecfdf5;
    color: #059669;
    border-color: #a7f3d0;
}
.btn-toggle-off {
    background: #f1f5f9;
    color: #64748b;
    border-color: #cbd5e1;
}
.btn-edit {
    background: #eff6ff;
    color: #1d4ed8;
    border-color: #bfdbfe;
}
.btn-edit:hover {
    background: #dbeafe;
}
.btn-delete {
    background: #fef2f2;
    color: #b91c1c;
    border-color: #fecaca;
}
.btn-delete:hover {
    background: #fee2e2;
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
.modal-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 0.375rem;
}
.modal-input {
    width: 100%;
    padding: 0.5625rem 0.875rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    outline: none;
    background: #ffffff;
    color: #1e293b;
    box-sizing: border-box;
}
.modal-input:focus {
    border-color: #4f46e5;
}
.toggle-checkbox-wrap {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}
.checkbox-input {
    width: 1rem;
    height: 1rem;
    accent-color: #4f46e5;
    cursor: pointer;
}
.toggle-text {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #1e293b;
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
</style>
