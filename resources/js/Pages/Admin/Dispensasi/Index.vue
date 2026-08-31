<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { formatRupiah, formatDate } from '@/utils';

const props = defineProps({
    dispensasis: Object,
    template: Object,
});

const templateForm = useForm({
    template: null,
});

const handleTemplateChange = (e) => {
    templateForm.template = e.target.files[0];
};

const uploadTemplate = () => {
    templateForm.post(route('admin.dispensasi.upload-template'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            templateForm.reset('template');
        },
    });
};

const activeModal = ref(null);
const approveNote = ref('');
const rejectNote = ref('');
const processingId = ref(null);

const openApprove = (d) => {
    approveNote.value = '';
    activeModal.value = { type: 'approve', data: d };
};

const openReject = (d) => {
    rejectNote.value = '';
    activeModal.value = { type: 'reject', data: d };
};

const closeModal = () => {
    activeModal.value = null;
};

const submitApprove = () => {
    if (!activeModal.value) return;
    processingId.value = activeModal.value.data.id;
    router.post(route('admin.dispensasi.approve', activeModal.value.data.id), {
        catatan_admin: approveNote.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            processingId.value = null;
            closeModal();
        },
    });
};

const submitReject = () => {
    if (!activeModal.value) return;
    processingId.value = activeModal.value.data.id;
    router.post(route('admin.dispensasi.reject', activeModal.value.data.id), {
        catatan_admin: rejectNote.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            processingId.value = null;
            closeModal();
        },
    });
};

const statusInfo = (status) => {
    const map = {
        pending: { label: 'Menunggu', class: 'm-badge-warning' },
        disetujui: { label: 'Disetujui', class: 'm-badge-success' },
        ditolak: { label: 'Ditolak', class: 'm-badge-danger' },
    };
    return map[status] || { label: status, class: 'm-badge-secondary' };
};
</script>

<template>
    <Head title="Dispensasi" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="page-heading">Dispensasi Pembayaran</h2>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <!-- Template Surat -->
                <div class="custom-card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Template Surat Dispensasi</h3>
                    </div>
                    <div class="card-body">
                        <div class="tpl-row">
                            <div class="tpl-info">
                                <div v-if="template?.template_filename" class="tpl-file">
                                    <i class="fas fa-file-pdf tpl-icon"></i>
                                    <div>
                                        <strong>{{ template.template_filename }}</strong>
                                        <div class="tpl-meta">
                                            Diunggah {{ template.updated_at ? formatDate(template.updated_at) : '-' }}
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="tpl-empty">
                                    <i class="fas fa-info-circle"></i>
                                    Belum ada template. Unggah template surat dispensasi yang akan diunduh mahasiswa.
                                </div>
                            </div>
                            <div class="tpl-actions">
                                <Link
                                    v-if="template?.template_path"
                                    :href="route('admin.dispensasi.download-template')"
                                    class="btn btn-light"
                                >
                                    <i class="fas fa-download"></i> Unduh Template
                                </Link>
                                <label class="btn btn-primary mb-0">
                                    <i class="fas fa-upload"></i> Upload Template
                                    <input
                                        type="file"
                                        accept=".pdf,.doc,.docx"
                                        class="file-input-hidden"
                                        @change="handleTemplateChange"
                                    />
                                </label>
                            </div>
                        </div>
                        <div v-if="templateForm.template" class="tpl-save-row">
                            <span class="tpl-meta">{{ templateForm.template.name }}</span>
                            <button
                                class="btn btn-sm btn-success"
                                :disabled="templateForm.processing"
                                @click="uploadTemplate"
                            >
                                {{ templateForm.processing ? 'Mengunggah...' : 'Simpan Template' }}
                            </button>
                        </div>
                        <div v-if="templateForm.errors.template" class="form-error">
                            {{ templateForm.errors.template }}
                        </div>
                    </div>
                </div>

                <!-- Daftar Pengajuan -->
                <div class="custom-card">
                    <div class="card-header">
                        <h3 class="card-title">Pengajuan Dispensasi Mahasiswa</h3>
                    </div>
                    <div class="card-body">
                        <div v-if="dispensasis.data.length === 0" class="text-center" style="padding:2rem;color:var(--gray-500);">
                            <i class="fas fa-check-circle" style="font-size:2rem;color:var(--success);margin-bottom:0.5rem;display:block;"></i>
                            Belum ada pengajuan dispensasi
                        </div>
                        <div v-else class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Tanggal Ajuan</th>
                                        <th>Mahasiswa</th>
                                        <th>Semester</th>
                                        <th>Tagihan</th>
                                        <th>Tempo Awal</th>
                                        <th>Tempo Baru</th>
                                        <th>Alasan</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="d in dispensasis.data" :key="d.id">
                                        <td>{{ formatDate(d.created_at) }}</td>
                                        <td>
                                            <strong>{{ d.mahasiswa?.nama_lengkap }}</strong>
                                            <div class="text-muted" style="font-size:0.75rem;">{{ d.mahasiswa?.nim }}</div>
                                        </td>
                                        <td>{{ d.tagihan?.semester }}</td>
                                        <td>{{ formatRupiah(d.tagihan?.nominal) }}</td>
                                        <td>{{ formatDate(d.tempo_awal) }}</td>
                                        <td><strong style="color:var(--primary);">{{ formatDate(d.tempo_baru) }}</strong></td>
                                        <td>
                                            <span class="text-muted" style="font-size:0.8125rem;" :title="d.alasan">
                                                {{ d.alasan.length > 40 ? d.alasan.substring(0, 40) + '...' : d.alasan }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="m-badge" :class="statusInfo(d.status).class">{{ statusInfo(d.status).label }}</span>
                                            <div v-if="d.catatan_admin" class="text-muted mt-1" style="font-size:0.75rem;">
                                                {{ d.catatan_admin }}
                                            </div>
                                        </td>
                                        <td>
                                            <template v-if="d.status === 'pending'">
                                                <div class="action-btns">
                                                    <button @click="openApprove(d)" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check"></i> Setujui
                                                    </button>
                                                    <button @click="openReject(d)" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-times"></i> Tolak
                                                    </button>
                                                </div>
                                            </template>
                                            <span v-else class="m-badge badge-blue">Sudah diproses</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="dispensasis.data.length > 0" class="pagination-wrap">
                            <span class="page-info">
                                Menampilkan {{ dispensasis.from }}-{{ dispensasis.to }} dari {{ dispensasis.total }} data
                            </span>
                            <div class="pagination">
                                <template v-for="link in dispensasis.links" :key="link.label">
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
                </div>
            </div>
        </div>

        <!-- Modal Setujui -->
        <div v-if="activeModal?.type === 'approve'" class="modal-overlay" @click.self="closeModal">
            <div class="modal-box">
                <div class="modal-header">
                    <h4>Setujui Dispensasi</h4>
                    <button class="modal-close" @click="closeModal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="modal-alert modal-alert-warning">
                        <i class="fas fa-file-signature"></i>
                        <div>
                            Pastikan <strong>surat fisik bermaterai</strong> dari {{ activeModal.data.mahasiswa?.nama_lengkap }}
                            sudah diterima dan persyaratannya dicek oleh bagian keuangan sebelum menyetujui.
                        </div>
                    </div>
                    <div class="modal-alert modal-alert-info">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            Jatuh tempo tagihan akan diperbarui menjadi
                            <strong>{{ formatDate(activeModal.data.tempo_baru) }}</strong>
                            untuk {{ activeModal.data.mahasiswa?.nama_lengkap }} ({{ activeModal.data.mahasiswa?.nim }}).
                        </div>
                    </div>
                    <div class="m-form-group">
                        <label class="m-form-label">Catatan (opsional)</label>
                        <textarea v-model="approveNote" rows="3" class="m-form-control" placeholder="Catatan persetujuan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" @click="closeModal">Batal</button>
                    <button class="btn btn-success" :disabled="processingId === activeModal.data.id" @click="submitApprove">
                        {{ processingId === activeModal.data.id ? 'Memproses...' : 'Ya, Setujui' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Tolak -->
        <div v-if="activeModal?.type === 'reject'" class="modal-overlay" @click.self="closeModal">
            <div class="modal-box">
                <div class="modal-header">
                    <h4>Tolak Dispensasi</h4>
                    <button class="modal-close" @click="closeModal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="modal-alert modal-alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>Alasan penolakan wajib diisi dan akan terlihat oleh mahasiswa.</div>
                    </div>
                    <div class="m-form-group">
                        <label class="m-form-label">Alasan Penolakan</label>
                        <textarea v-model="rejectNote" rows="3" class="m-form-control" placeholder="Tuliskan alasan penolakan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" @click="closeModal">Batal</button>
                    <button
                        class="btn btn-danger"
                        :disabled="!rejectNote.trim() || processingId === activeModal.data.id"
                        @click="submitReject"
                    >
                        {{ processingId === activeModal.data.id ? 'Memproses...' : 'Tolak Pengajuan' }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.file-input-hidden {
    display: none;
}
.card-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}
.m-badge-secondary {
    background: #f3f4f6;
    color: #374151;
}

/* --- Template Upload Section --- */
.tpl-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}
.tpl-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
    flex: 1;
}
.tpl-file {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
}
.tpl-icon {
    font-size: 1.5rem;
    color: #dc2626;
    flex-shrink: 0;
}
.tpl-file strong {
    color: #1f2937;
    word-break: break-all;
}
.tpl-meta {
    color: #6b7280;
    font-size: 0.8125rem;
}
.tpl-empty {
    color: #6b7280;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.tpl-actions {
    display: flex;
    gap: 0.625rem;
    flex-wrap: wrap;
}
.tpl-save-row {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.625rem;
    margin-top: 0.75rem;
}
.form-error {
    color: #dc2626;
    font-size: 0.8125rem;
    margin-top: 0.375rem;
}

/* --- Pagination --- */
.pagination-wrap {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
    flex-wrap: wrap;
    gap: 0.75rem;
}
.page-info {
    font-size: 0.8125rem;
    color: #6b7280;
}
.pagination {
    display: flex;
    gap: 0.25rem;
}
.page-item {
    display: inline-flex;
}
.page-link {
    padding: 0.375rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    color: #374151;
    text-decoration: none;
    border: 1px solid #e5e7eb;
    transition: all 0.2s;
}
.page-link:hover {
    background: #f9fafb;
}
.page-item.active .page-link {
    background: var(--primary, #4f46e5);
    color: white;
    border-color: var(--primary, #4f46e5);
}
.page-item.disabled .page-link {
    opacity: 0.5;
    cursor: not-allowed;
}

/* --- Table Action Buttons --- */
.action-btns {
    display: flex;
    gap: 0.375rem;
    flex-wrap: wrap;
}
.badge-blue {
    background: #dbeafe;
    border: 1px solid #93c5fd;
    color: #1d4ed8;
}

/* --- Modal Alerts --- */
.modal-alert {
    display: flex;
    align-items: flex-start;
    gap: 0.625rem;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    line-height: 1.55;
}
.modal-alert i {
    margin-top: 0.25rem;
    flex-shrink: 0;
}
.modal-alert + .modal-alert {
    margin-top: 0.75rem;
}
.modal-alert-warning {
    background: #fef3c7;
    border: 1px solid #fcd34d;
    color: #92400e;
}
.modal-alert-info {
    background: #e0f2fe;
    border: 1px solid #7dd3fc;
    color: #0c4a6e;
}
.modal-alert-danger {
    background: #fee2e2;
    border: 1px solid #fca5a5;
    color: #991b1b;
}

/* --- Modal Form --- */
.m-form-group {
    margin-top: 1rem;
    margin-bottom: 0;
}
.m-form-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}
.m-form-control {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 1.5px solid #d1d5db;
    border-radius: 0.625rem;
    font-size: 0.875rem;
    font-family: inherit;
    color: #1f2937;
    background: #ffffff;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.m-form-control:focus {
    outline: none;
    border-color: var(--primary, #4f46e5);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
}
.m-form-control::placeholder {
    color: #9ca3af;
}
textarea.m-form-control {
    resize: vertical;
    min-height: 90px;
}
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1050;
    padding: 1rem;
}
.modal-box {
    background: white;
    border-radius: 0.75rem;
    width: 100%;
    max-width: 480px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    animation: modalIn 0.2s ease;
}
@keyframes modalIn {
    from { opacity: 0; transform: translateY(10px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e5e7eb;
}
.modal-header h4 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: #1f2937;
}
.modal-close {
    border: none;
    background: none;
    font-size: 1.5rem;
    line-height: 1;
    color: #9ca3af;
    cursor: pointer;
}
.modal-close:hover { color: #374151; }
.modal-body { padding: 1.25rem; }
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 1rem 1.25rem;
    border-top: 1px solid #e5e7eb;
}
</style>
