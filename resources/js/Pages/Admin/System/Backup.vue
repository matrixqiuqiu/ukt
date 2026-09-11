<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ backups: Array, tables: Array, dbName: String });

const showRunBackupModal = ref(false);
const showDeleteModal = ref(false);
const showRestoreModal = ref(false);
const fileToDelete = ref('');
const pendingRestoreFile = ref(null);

const executeRunBackup = () => {
    showRunBackupModal.value = false;
    router.post(route('admin.system.backup.run'), {}, { preserveScroll:false });
};

const download = (name) => { window.location.href = route('admin.system.backup.download', name); };

const confirmDelete = (name) => {
    fileToDelete.value = name;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (!fileToDelete.value) return;
    router.delete(route('admin.system.backup.delete', fileToDelete.value), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            fileToDelete.value = '';
        }
    });
};

const importInput = ref(null);
const importForm = useForm({ file: null });
const importing = ref(false);

const triggerImport = () => importInput.value?.click();

const handleImport = (e) => {
    const file = e.target.files[0];
    if(!file) return;
    pendingRestoreFile.value = file;
    showRestoreModal.value = true;
};

const executeRestore = () => {
    if (!pendingRestoreFile.value) return;
    showRestoreModal.value = false;
    importing.value = true;
    importForm.file = pendingRestoreFile.value;
    importForm.post(route('admin.system.backup.import'), {
        forceFormData:true,
        onFinish:()=>{
            importing.value=false;
            pendingRestoreFile.value = null;
            if (importInput.value) importInput.value.value = '';
        }
    });
};

const cancelRestore = () => {
    showRestoreModal.value = false;
    pendingRestoreFile.value = null;
    if (importInput.value) importInput.value.value = '';
};
</script>

<template>
    <Head title="Backup Data" />
    <AuthenticatedLayout>
        <template #header><h2 class="page-heading">System — Backup Data</h2></template>
        <div class="page-body"><div class="container-xl">
            <div class="custom-card" style="margin-bottom:1.5rem;">
                <div class="card-header">
                    <h4><i class="fas fa-database" style="margin-right:0.5rem;"></i> Database: {{ dbName }}</h4>
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                        <button @click="showRunBackupModal = true" class="m-btn m-btn-primary"><i class="fas fa-download"></i> Buat Backup</button>
                        <button @click="triggerImport" class="m-btn m-btn-secondary" :disabled="importing"><i :class="importing ? 'fas fa-spinner fa-spin' : 'fas fa-upload'"></i> {{ importing ? 'Mengimpor...' : 'Restore SQL' }}</button>
                        <input ref="importInput" type="file" accept=".sql,.txt" style="display:none" @change="handleImport" />
                    </div>
                </div>
                <div class="card-body">
                    <div style="font-size:0.8125rem;color:var(--gray-600);margin-bottom:0.75rem;">{{ tables.length }} tabel: {{ tables.slice(0,10).join(', ') }}{{ tables.length>10 ? '...' : '' }}</div>
                    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:0.5rem;padding:0.75rem;font-size:0.8125rem;color:#1e40af;"><i class="fas fa-info-circle"></i> Backup disimpan di <code>storage/app/private/backups</code>. Gunakan mysqldump jika tersedia, fallback ke dump PHP.</div>
                </div>
            </div>

            <div class="custom-card">
                <div class="card-header"><h4><i class="fas fa-archive" style="margin-right:0.5rem;"></i> Daftar Arsip Backup ({{ backups.length }})</h4></div>
                <div class="card-body" style="padding:0;">
                    <div v-if="backups.length===0" style="text-align:center;padding:2rem;color:var(--gray-500);">Belum ada file backup.</div>
                    <div v-else class="table-responsive">
                        <table class="m-data-table">
                            <thead><tr><th>Nama File</th><th>Ukuran</th><th>Tanggal</th><th style="width:140px;">Aksi</th></tr></thead>
                            <tbody>
                                <tr v-for="b in backups" :key="b.name">
                                    <td style="font-weight:600;font-family:monospace;font-size:0.8125rem;">{{ b.name }}</td>
                                    <td>{{ b.size_label }}</td>
                                    <td>{{ b.modified }}</td>
                                    <td>
                                        <div style="display:flex;gap:0.375rem;">
                                            <button class="m-btn m-btn-sm m-btn-secondary" @click="download(b.name)" title="Download"><i class="fas fa-download"></i></button>
                                            <button class="m-btn m-btn-sm m-btn-danger" @click="confirmDelete(b.name)" title="Hapus"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div></div>

        <!-- Teleport Modals -->
        <Teleport to="body">
            <!-- Modal Run Backup -->
            <div v-if="showRunBackupModal" class="modal-overlay" @click.self="showRunBackupModal = false">
                <div class="modal-box" style="max-width: 440px;">
                    <div class="modal-header">
                        <h3 style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-database" style="color:#2563eb;"></i> Buat Backup Database
                        </h3>
                        <button class="modal-close" @click="showRunBackupModal = false"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="modal-body" style="padding: 1.25rem 1.5rem;">
                        <p style="margin: 0; color: #475569; font-size: 0.875rem; line-height: 1.5;">
                            Apakah Anda ingin membuat file backup database <strong>{{ dbName }}</strong> sekarang? File dump SQL akan tersimpan di sistem dan dapat diunduh.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="m-btn m-btn-secondary" @click="showRunBackupModal = false">Batal</button>
                        <button type="button" class="m-btn m-btn-primary" @click="executeRunBackup">
                            <i class="fas fa-download"></i> Mulai Backup
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Delete Backup File -->
            <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
                <div class="modal-box" style="max-width: 440px;">
                    <div class="modal-header">
                        <h3 style="color: #991b1b; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-trash-alt"></i> Konfirmasi Hapus
                        </h3>
                        <button class="modal-close" @click="showDeleteModal = false"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="modal-body" style="padding: 1.25rem 1.5rem;">
                        <p style="margin: 0 0 0.5rem; color: #475569; font-size: 0.875rem;">
                            Apakah Anda yakin ingin menghapus file backup ini?
                        </p>
                        <div style="font-family: monospace; font-size: 0.8125rem; font-weight: 700; color: #0f172a; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 0.75rem;">
                            {{ fileToDelete }}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="m-btn m-btn-secondary" @click="showDeleteModal = false">Batal</button>
                        <button type="button" class="m-btn m-btn-danger" @click="executeDelete">
                            <i class="fas fa-trash"></i> Ya, Hapus
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Restore Confirmation -->
            <div v-if="showRestoreModal" class="modal-overlay" @click.self="cancelRestore">
                <div class="modal-box" style="max-width: 480px;">
                    <div class="modal-header">
                        <h3 style="color: #d97706; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-exclamation-triangle"></i> Peringatan Restore Database
                        </h3>
                        <button class="modal-close" @click="cancelRestore"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="modal-body" style="padding: 1.25rem 1.5rem;">
                        <p style="margin: 0 0 0.75rem; color: #475569; font-size: 0.875rem; line-height: 1.5;">
                            Anda akan me-restore database dari file <strong>{{ pendingRestoreFile?.name }}</strong>.
                        </p>
                        <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 0.5rem; padding: 0.875rem; font-size: 0.8125rem; color: #92400e;">
                            <strong>PERHATIAN:</strong> Data sistem saat ini akan ditimpa dengan data dari file backup yang dipilih. Tindakan ini tidak dapat dibatalkan!
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="m-btn m-btn-secondary" @click="cancelRestore">Batal</button>
                        <button type="button" class="m-btn m-btn-danger" @click="executeRestore">
                            <i class="fas fa-upload"></i> Ya, Restore Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-card { overflow: hidden; background: #fff; border: 1px solid var(--gray-200); border-radius: 1rem; }
.card-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--gray-100); background: #f8fafc; }
.table-responsive { overflow-x: auto; width: 100%; }
.m-data-table { min-width: 520px; }

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
</style>
