<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { formatRupiah, formatDate } from '@/utils';

const props = defineProps({
    dispensasis: Object,
    template: Object,
    filters: Object,
    counts: Object,
});

const search = ref(props.filters?.search || '');
const currentStatus = ref(props.filters?.status || '');

const setStatusTab = (status) => {
    currentStatus.value = status;
    doFilter();
};

const doFilter = () => {
    const params = {};
    if (search.value) params.search = search.value;
    if (currentStatus.value) params.status = currentStatus.value;
    router.get(route('admin.dispensasi.index'), params, { preserveState: true, replace: true });
};

const clearFilter = () => {
    search.value = '';
    currentStatus.value = '';
    router.get(route('admin.dispensasi.index'), {}, { preserveState: true, replace: true });
};

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
        pending: { label: 'Menunggu', cls: 'badge-solid-warning' },
        disetujui: { label: 'Disetujui', cls: 'badge-solid-success' },
        ditolak: { label: 'Ditolak', cls: 'badge-solid-danger' },
    };
    return map[status] || { label: status, cls: 'badge-solid-secondary' };
};
</script>

<template>
    <Head title="Dispensasi" />
    <AuthenticatedLayout>
        <template #header>
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div>
                    <h2 class="page-heading" style="font-size:1.375rem;font-weight:700;color:#0f172a;margin-bottom:0.25rem;">Dispensasi Pembayaran UKT</h2>
                    <p style="font-size:0.875rem;color:#64748b;margin:0;">Kelola permohonan penundaan pembayaran jatuh tempo mahasiswa</p>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">
                <!-- Template Surat Section -->
                <div class="template-card">
                    <div class="template-icon-wrap">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div class="template-info">
                        <div style="font-size:0.9375rem;font-weight:700;color:#0f172a;margin-bottom:0.25rem;">
                            Template Formulir Dispensasi Resmi
                        </div>
                        <div v-if="template?.template_filename" style="font-size:0.8125rem;color:#475569;display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                            <span style="font-weight:600;color:#1e293b;">{{ template.template_filename }}</span>
                            <span style="color:#94a3b8;">&bull;</span>
                            <span>Diperbarui {{ template.updated_at ? formatDate(template.updated_at) : '-' }}</span>
                        </div>
                        <div v-else style="font-size:0.8125rem;color:#64748b;">
                            Belum ada template. Mahasiswa memerlukan template resmi bertanda tangan dan materai.
                        </div>
                    </div>
                    <div class="template-actions">
                        <a
                            v-if="template?.template_path"
                            :href="route('admin.dispensasi.download-template')"
                            class="solid-btn btn-white-border"
                            title="Unduh template surat yang aktif saat ini"
                        >
                            <i class="fas fa-download"></i> Unduh File
                        </a>
                        <label class="solid-btn btn-indigo-solid" style="cursor:pointer;margin:0;">
                            <i class="fas fa-upload"></i> {{ template?.template_path ? 'Ganti Template' : 'Upload Template' }}
                            <input
                                type="file"
                                accept=".pdf,.doc,.docx"
                                style="display:none;"
                                @change="handleTemplateChange"
                            />
                        </label>
                    </div>
                </div>

                <!-- Template Preview / Upload Confirm -->
                <div v-if="templateForm.template" class="template-confirm-bar">
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <i class="fas fa-paperclip" style="color:#4f46e5;"></i>
                        <span style="font-size:0.8125rem;font-weight:600;color:#1e293b;">File siap diunggah: {{ templateForm.template.name }}</span>
                    </div>
                    <div style="display:flex;gap:0.5rem;">
                        <button class="solid-btn btn-green-solid" :disabled="templateForm.processing" @click="uploadTemplate">
                            <i class="fas fa-check"></i> {{ templateForm.processing ? 'Menyimpan...' : 'Konfirmasi Simpan' }}
                        </button>
                        <button class="solid-btn btn-white-border" @click="templateForm.reset('template')">
                            Batal
                        </button>
                    </div>
                </div>
                <div v-if="templateForm.errors.template" style="color:#dc2626;font-size:0.8125rem;margin-bottom:1rem;padding:0 0.5rem;">
                    {{ templateForm.errors.template }}
                </div>

                <!-- Main Data Card with Filter Tabs -->
                <div class="data-card">
                    <!-- Filter Header -->
                    <div class="tabs-filter-bar">
                        <!-- Status Tabs -->
                        <div class="status-tabs">
                            <button
                                class="tab-btn"
                                :class="{ 'tab-btn-active': !currentStatus }"
                                @click="setStatusTab('')"
                            >
                                Semua
                                <span class="tab-count">{{ counts?.all || 0 }}</span>
                            </button>
                            <button
                                class="tab-btn"
                                :class="{ 'tab-btn-active': currentStatus === 'pending' }"
                                @click="setStatusTab('pending')"
                            >
                                Menunggu Verifikasi
                                <span class="tab-count tab-count-pending">{{ counts?.pending || 0 }}</span>
                            </button>
                            <button
                                class="tab-btn"
                                :class="{ 'tab-btn-active': currentStatus === 'disetujui' }"
                                @click="setStatusTab('disetujui')"
                            >
                                Disetujui
                                <span class="tab-count">{{ counts?.disetujui || 0 }}</span>
                            </button>
                            <button
                                class="tab-btn"
                                :class="{ 'tab-btn-active': currentStatus === 'ditolak' }"
                                @click="setStatusTab('ditolak')"
                            >
                                Ditolak
                                <span class="tab-count">{{ counts?.ditolak || 0 }}</span>
                            </button>
                        </div>

                        <!-- Search Box -->
                        <div class="search-box">
                            <i class="fas fa-search search-icon"></i>
                            <input
                                type="search"
                                v-model="search"
                                placeholder="Cari NIM atau Nama..."
                                class="search-input"
                                @keyup.enter="doFilter"
                            />
                            <button v-if="search" @click="clearFilter" class="search-clear-btn" title="Hapus pencarian">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Table -->
                    <div v-if="dispensasis.data && dispensasis.data.length > 0">
                        <div class="table-responsive">
                            <table class="solid-table">
                                <thead>
                                    <tr>
                                        <th style="width:40px;text-align:center;">No</th>
                                        <th>Mahasiswa</th>
                                        <th>Tagihan</th>
                                        <th>Jatuh Tempo Perpanjangan</th>
                                        <th>Alasan Pengajuan</th>
                                        <th style="text-align:center;">Status</th>
                                        <th style="width:160px;text-align:center;">Aksi / Info</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(d, i) in dispensasis.data" :key="d.id">
                                        <td style="text-align:center;color:#64748b;font-size:0.8125rem;">
                                            {{ dispensasis.from + i }}
                                        </td>
                                        <td>
                                            <div style="font-weight:700;color:#0f172a;font-size:0.875rem;">
                                                {{ d.mahasiswa?.nama_lengkap || '-' }}
                                            </div>
                                            <div style="display:flex;align-items:center;gap:0.375rem;margin-top:0.125rem;">
                                                <span style="font-family:monospace;font-size:0.75rem;background:#f1f5f9;color:#334155;padding:0.15rem 0.35rem;border-radius:0.25rem;font-weight:600;">
                                                    {{ d.mahasiswa?.nim || '-' }}
                                                </span>
                                                <span style="font-size:0.75rem;color:#64748b;">{{ d.mahasiswa?.jurusan || '' }}</span>
                                            </div>
                                            <div style="font-size:0.6875rem;color:#94a3b8;margin-top:0.25rem;">
                                                Diajukan: {{ formatDate(d.created_at) }}
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight:700;color:#0f172a;font-size:0.8125rem;">
                                                {{ formatRupiah(d.tagihan?.nominal) }}
                                            </div>
                                            <div style="font-size:0.75rem;color:#64748b;">
                                                Semester {{ d.tagihan?.semester }} ({{ d.tagihan?.tahun_akademik }})
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display:flex;align-items:center;gap:0.5rem;">
                                                <div>
                                                    <div style="font-size:0.6875rem;color:#94a3b8;text-decoration:line-through;">
                                                        Awal: {{ formatDate(d.tempo_awal) }}
                                                    </div>
                                                    <div style="font-size:0.8125rem;font-weight:700;color:#4f46e5;display:flex;align-items:center;gap:0.25rem;">
                                                        <i class="fas fa-arrow-right" style="font-size:0.6875rem;"></i>
                                                        {{ formatDate(d.tempo_baru) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="max-width:260px;font-size:0.8125rem;color:#334155;line-height:1.4;">
                                                {{ d.alasan }}
                                            </div>
                                        </td>
                                        <td style="text-align:center;">
                                            <span :class="['solid-badge', statusInfo(d.status).cls]">
                                                {{ statusInfo(d.status).label }}
                                            </span>
                                        </td>
                                        <td style="text-align:center;">
                                            <template v-if="d.status === 'pending'">
                                                <div style="display:flex;gap:0.375rem;justify-content:center;">
                                                    <button @click="openApprove(d)" class="action-btn btn-green-solid" title="Setujui permohonan dispensasi">
                                                        <i class="fas fa-check"></i> Setujui
                                                    </button>
                                                    <button @click="openReject(d)" class="action-btn btn-red-solid" title="Tolak permohonan dispensasi">
                                                        <i class="fas fa-times"></i> Tolak
                                                    </button>
                                                </div>
                                            </template>
                                            <div v-else style="font-size:0.75rem;color:#64748b;text-align:left;padding-left:0.5rem;">
                                                <div>Diproses: {{ d.diproses_pada ? formatDate(d.diproses_pada) : '-' }}</div>
                                                <div v-if="d.catatan_admin" style="font-style:italic;color:#334155;margin-top:0.125rem;">
                                                    "{{ d.catatan_admin }}"
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Footer -->
                        <div class="pagination-footer">
                            <span style="font-size:0.8125rem;color:#64748b;">
                                Menampilkan <strong style="color:#0f172a;">{{ dispensasis.from }}-{{ dispensasis.to }}</strong> dari <strong style="color:#0f172a;">{{ dispensasis.total }}</strong> pengajuan
                            </span>
                            <div class="pagination-btns">
                                <template v-for="link in dispensasis.links" :key="link.label">
                                    <span v-if="!link.url" class="p-btn p-disabled" v-html="link.label"></span>
                                    <Link v-else :href="link.url" class="p-btn" :class="{ 'p-active': link.active }" v-html="link.label" preserve-state />
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else style="text-align:center;padding:4rem 2rem;color:#64748b;">
                        <div style="width:64px;height:64px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;color:#94a3b8;font-size:1.75rem;">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <h4 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 0.375rem;">Tidak Ada Pengajuan Dispensasi</h4>
                        <p style="font-size:0.8125rem;color:#64748b;margin:0 0 1rem;max-width:340px;margin-inline:auto;">
                            {{ currentStatus || search ? 'Tidak ada data yang sesuai dengan filter atau kata kunci.' : 'Saat ini belum ada pengajuan dispensasi dari mahasiswa.' }}
                        </p>
                        <button v-if="currentStatus || search" @click="clearFilter" class="solid-btn btn-white-border" style="font-size:0.8125rem;">
                            <i class="fas fa-undo"></i> Reset Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Setujui -->
        <div v-if="activeModal?.type === 'approve'" class="modal-overlay" @click.self="closeModal">
            <div class="modal-card">
                <div class="modal-header">
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <span class="modal-header-icon" style="background:#ecfdf5;color:#059669;">
                            <i class="fas fa-check-circle"></i>
                        </span>
                        <div>
                            <h4 style="margin:0;font-size:1rem;font-weight:700;color:#0f172a;">Setujui Dispensasi</h4>
                            <p style="margin:0;font-size:0.75rem;color:#64748b;">Perpanjangan jatuh tempo tagihan</p>
                        </div>
                    </div>
                    <button class="modal-close-btn" @click="closeModal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="solid-notice solid-notice-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            Pastikan <strong>surat fisik bermaterai</strong> dari <strong>{{ activeModal.data.mahasiswa?.nama_lengkap }}</strong> telah diterima dan diverifikasi oleh bagian keuangan.
                        </div>
                    </div>

                    <div class="detail-box">
                        <div class="detail-row">
                            <span class="detail-label">Mahasiswa:</span>
                            <span class="detail-value">{{ activeModal.data.mahasiswa?.nama_lengkap }} ({{ activeModal.data.mahasiswa?.nim }})</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Tagihan:</span>
                            <span class="detail-value">{{ formatRupiah(activeModal.data.tagihan?.nominal) }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Tempo Baru:</span>
                            <span class="detail-value" style="color:#059669;font-weight:700;">{{ formatDate(activeModal.data.tempo_baru) }}</span>
                        </div>
                    </div>

                    <div style="margin-top:1rem;">
                        <label style="display:block;font-size:0.8125rem;font-weight:600;color:#334155;margin-bottom:0.375rem;">
                            Catatan Persetujuan (Opsional)
                        </label>
                        <textarea
                            v-model="approveNote"
                            rows="2"
                            class="modal-textarea"
                            placeholder="Tambahkan catatan jika ada..."
                        ></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="solid-btn btn-white-border" @click="closeModal">Batal</button>
                    <button
                        class="solid-btn btn-green-solid"
                        :disabled="processingId === activeModal.data.id"
                        @click="submitApprove"
                    >
                        <i class="fas fa-check"></i> {{ processingId === activeModal.data.id ? 'Memproses...' : 'Ya, Setujui Sekarang' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Tolak -->
        <div v-if="activeModal?.type === 'reject'" class="modal-overlay" @click.self="closeModal">
            <div class="modal-card">
                <div class="modal-header">
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <span class="modal-header-icon" style="background:#fef2f2;color:#dc2626;">
                            <i class="fas fa-times-circle"></i>
                        </span>
                        <div>
                            <h4 style="margin:0;font-size:1rem;font-weight:700;color:#0f172a;">Tolak Dispensasi</h4>
                            <p style="margin:0;font-size:0.75rem;color:#64748b;">Berikan alasan penolakan yang jelas</p>
                        </div>
                    </div>
                    <button class="modal-close-btn" @click="closeModal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="solid-notice solid-notice-danger">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            Alasan penolakan wajib diisi dan akan langsung terbaca oleh mahasiswa di portal mereka.
                        </div>
                    </div>

                    <div style="margin-top:1rem;">
                        <label style="display:block;font-size:0.8125rem;font-weight:600;color:#334155;margin-bottom:0.375rem;">
                            Alasan Penolakan <span style="color:#dc2626;">*</span>
                        </label>
                        <textarea
                            v-model="rejectNote"
                            rows="3"
                            class="modal-textarea"
                            placeholder="Contoh: Berkas fisik bermaterai belum diserahkan ke bagian keuangan..."
                        ></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="solid-btn btn-white-border" @click="closeModal">Batal</button>
                    <button
                        class="solid-btn btn-red-solid"
                        :disabled="!rejectNote.trim() || processingId === activeModal.data.id"
                        @click="submitReject"
                    >
                        <i class="fas fa-times"></i> {{ processingId === activeModal.data.id ? 'Memproses...' : 'Tolak Pengajuan' }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Template Card */
.template-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.125rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
}
.template-icon-wrap {
    width: 44px;
    height: 44px;
    border-radius: 0.625rem;
    background: #e0e7ff;
    color: #4338ca;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}
.template-info {
    flex: 1;
    min-width: 200px;
}
.template-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}
.template-confirm-bar {
    background: #eef2ff;
    border: 1px solid #c7d2fe;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
    gap: 0.75rem;
}

/* Data Card */
.data-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    overflow: hidden;
}

/* Tabs & Filter Bar */
.tabs-filter-bar {
    padding: 0.875rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
    background: #f8fafc;
}
.status-tabs {
    display: flex;
    gap: 0.375rem;
    flex-wrap: wrap;
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
    color: #0f172a;
}
.tab-btn-active {
    background: #1e293b !important;
    color: #ffffff !important;
    border-color: #1e293b !important;
}
.tab-count {
    padding: 0.1rem 0.4rem;
    border-radius: 0.375rem;
    font-size: 0.6875rem;
    background: #f1f5f9;
    color: #334155;
}
.tab-btn-active .tab-count {
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff;
}
.tab-count-pending {
    background: #fef3c7;
    color: #b45309;
    font-weight: 700;
}
.tab-btn-active .tab-count-pending {
    background: #f59e0b;
    color: #ffffff;
}

/* Search Box */
.search-box {
    position: relative;
    display: flex;
    align-items: center;
    min-width: 220px;
}
.search-icon {
    position: absolute;
    left: 0.75rem;
    color: #94a3b8;
    font-size: 0.75rem;
}
.search-input {
    width: 100%;
    padding: 0.4375rem 2rem 0.4375rem 2.125rem;
    font-size: 0.8125rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    background: #ffffff;
    outline: none;
    transition: border-color 0.15s;
}
.search-input:focus {
    border-color: #4f46e5;
}
.search-clear-btn {
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

/* Solid Badges */
.solid-badge {
    display: inline-block;
    padding: 0.25rem 0.625rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}
.badge-solid-warning {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
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
.badge-solid-secondary {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

/* Solid Buttons */
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
    transition: opacity 0.15s, background 0.15s;
}
.solid-btn:hover {
    opacity: 0.9;
}
.btn-indigo-solid {
    background: #4f46e5;
    color: #ffffff;
}
.btn-green-solid {
    background: #059669;
    color: #ffffff;
}
.btn-red-solid {
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

.action-btn {
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

/* Pagination */
.pagination-footer {
    padding: 0.875rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #f1f5f9;
    flex-wrap: wrap;
    gap: 1rem;
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
.modal-header-icon {
    width: 36px;
    height: 36px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.125rem;
}
.modal-close-btn {
    border: none;
    background: none;
    font-size: 1.5rem;
    line-height: 1;
    color: #94a3b8;
    cursor: pointer;
}
.modal-close-btn:hover {
    color: #1e293b;
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
.solid-notice {
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    line-height: 1.5;
    display: flex;
    align-items: flex-start;
    gap: 0.625rem;
}
.solid-notice-warning {
    background: #fffbeb;
    border: 1px solid #fde68a;
    color: #92400e;
}
.solid-notice-danger {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}
.detail-box {
    margin-top: 0.875rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
}
.detail-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.8125rem;
    padding: 0.25rem 0;
}
.detail-label {
    color: #64748b;
}
.detail-value {
    font-weight: 600;
    color: #1e293b;
}
.modal-textarea {
    width: 100%;
    padding: 0.625rem 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    font-family: inherit;
    outline: none;
}
.modal-textarea:focus {
    border-color: #4f46e5;
}
</style>

