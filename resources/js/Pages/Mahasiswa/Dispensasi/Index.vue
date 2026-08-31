<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { formatRupiah, formatDate } from '@/utils';

const props = defineProps({
    dispensasis: Array,
    tagihans: Array,
    template: Object,
});

const form = useForm({
    tagihan_id: '',
    alasan: '',
    tempo_baru: '',
});

const submit = () => {
    form.post(route('mahasiswa.dispensasi.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const statusInfo = (status) => {
    const map = {
        pending: { label: 'Menunggu Verifikasi', class: 'm-badge-warning' },
        disetujui: { label: 'Disetujui', class: 'm-badge-success' },
        ditolak: { label: 'Ditolak', class: 'm-badge-danger' },
    };
    return map[status] || { label: status, class: 'm-badge-secondary' };
};

const selectedTagihan = computed(() => {
    return props.tagihans.find(t => t.id === form.tagihan_id) || null;
});

const minTempoBaru = computed(() => {
    if (!selectedTagihan.value?.jatuh_tempo) return '';
    const d = new Date(selectedTagihan.value.jatuh_tempo);
    d.setDate(d.getDate() + 1);
    return d.toISOString().split('T')[0];
});

const canSubmit = computed(() => {
    return form.tagihan_id && form.alasan.trim() && form.tempo_baru;
});
</script>

<template>
    <Head title="Dispensasi Pembayaran" />
    <AuthenticatedLayout>
        <template #header>
            <div class="page-heading">
                <h2 class="page-heading">Dispensasi Pembayaran</h2>
            </div>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <!-- Cara Kerja -->
                <div class="custom-card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Panduan Pengajuan Dispensasi</h3>
                    </div>
                    <div class="card-body">
                        <div class="steps-grid">
                            <div class="steps-item">
                                <div class="steps-num">1</div>
                                <div class="steps-text">
                                    <strong>Unduh &amp; Cetak Template</strong>
                                    <p>Unduh template surat dispensasi, lalu cetak.</p>
                                </div>
                            </div>
                            <div class="steps-item">
                                <div class="steps-num">2</div>
                                <div class="steps-text">
                                    <strong>Lengkapi Surat</strong>
                                    <p>Isi tangan, tandatangani, dan tempel materai pada template.</p>
                                </div>
                            </div>
                            <div class="steps-item">
                                <div class="steps-num">3</div>
                                <div class="steps-text">
                                    <strong>Ajukan di Sistem</strong>
                                    <p>Isi form di bawah: pilih tagihan, tentukan tempo baru, dan tulis alasan.</p>
                                </div>
                            </div>
                            <div class="steps-item">
                                <div class="steps-num">4</div>
                                <div class="steps-text">
                                    <strong>Serahkan ke Bagian Keuangan</strong>
                                    <p>Serahkan surat fisik bermaterai ke bagian keuangan untuk pengecekan persyaratan.</p>
                                </div>
                            </div>
                            <div class="steps-item">
                                <div class="steps-num">5</div>
                                <div class="steps-text">
                                    <strong>Tunggu Persetujuan</strong>
                                    <p>Keuangan menyetujui setelah persyaratan terpenuhi. Jatuh tempo tagihan diperbarui.</p>
                                </div>
                            </div>
                        </div>
                        <div class="guide-note">
                            <i class="fas fa-info-circle"></i>
                            <span>Catatan: pengajuan hanya dianggap valid setelah surat fisik diterima oleh bagian keuangan.</span>
                        </div>
                    </div>
                </div>

                <!-- Form Pengajuan -->
                <div class="custom-card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Form Pengajuan Dispensasi</h3>
                    </div>
                    <div class="card-body">
                        <div v-if="!template?.template_path" class="alert-box warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>Template surat dispensasi belum tersedia. Silakan hubungi bagian administrasi.</div>
                        </div>

                        <div v-else>
                            <!-- Unduh / cetak template -->
                            <div class="template-download-box">
                                <div class="d-box-row">
                                    <div class="d-box-info">
                                        <i class="fas fa-file-pdf d-box-icon"></i>
                                        <div>
                                            <strong>{{ template.template_filename }}</strong>
                                            <div class="d-box-sub">
                                                Cetak / unduh, lalu isi tangan, tanda tangani, dan tempel materai.
                                            </div>
                                        </div>
                                    </div>
                                    <Link :href="route('mahasiswa.dispensasi.download-template')" class="btn btn-primary" target="_blank">
                                        <i class="fas fa-download"></i> Unduh &amp; Cetak Template
                                    </Link>
                                </div>
                            </div>

                            <form @submit.prevent="submit">
                                <div class="row form-row">
                                    <div class="col-md-6">
                                        <div class="m-form-group">
                                            <label>Pilih Tagihan</label>
                                            <select v-model="form.tagihan_id" class="m-form-control m-form-select" :disabled="tagihans.length === 0">
                                                <option value="">-- Pilih Tagihan --</option>
                                                <option
                                                    v-for="t in tagihans"
                                                    :key="t.id"
                                                    :value="t.id"
                                                    :disabled="t.hasPending"
                                                >
                                                    Semester {{ t.semester }} ({{ t.tahun_akademik }}) — {{ formatRupiah(t.nominal) }}
                                                    {{ t.hasPending ? ' — Menunggu verifikasi' : '' }}
                                                </option>
                                            </select>
                                            <div v-if="form.errors.tagihan_id" class="form-error">{{ form.errors.tagihan_id }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="m-form-group">
                                            <label>Tempo Pembayaran Baru</label>
                                            <input
                                                v-model="form.tempo_baru"
                                                type="date"
                                                class="m-form-control"
                                                :min="minTempoBaru"
                                                :disabled="!selectedTagihan"
                                            />
                                            <div v-if="selectedTagihan" class="form-hint">
                                                Tempo saat ini: {{ formatDate(selectedTagihan.jatuh_tempo) }}
                                            </div>
                                            <div v-if="form.errors.tempo_baru" class="form-error">{{ form.errors.tempo_baru }}</div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="m-form-group">
                                            <label>Alasan Pengajuan</label>
                                            <textarea
                                                v-model="form.alasan"
                                                rows="3"
                                                class="m-form-control"
                                                placeholder="Jelaskan alasan mengajukan perpanjangan tempo pembayaran..."
                                            ></textarea>
                                            <div v-if="form.errors.alasan" class="form-error">{{ form.errors.alasan }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                        :disabled="!canSubmit || form.processing"
                                    >
                                        <i class="fas fa-paper-plane"></i>
                                        {{ form.processing ? 'Mengirim...' : 'Ajukan Dispensasi' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Riwayat Pengajuan -->
                <div class="custom-card">
                    <div class="card-header">
                        <h3 class="card-title">Riwayat Pengajuan Dispensasi</h3>
                    </div>
                    <div class="card-body">
                        <div v-if="dispensasis.length === 0" class="text-center" style="padding:2rem;color:var(--gray-500);">
                            <i class="fas fa-folder-open" style="font-size:2rem;margin-bottom:0.5rem;display:block;"></i>
                            Belum ada pengajuan dispensasi
                        </div>
                        <div v-else class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Semester</th>
                                        <th>Tempo Awal</th>
                                        <th>Tempo Baru</th>
                                        <th>Alasan</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="d in dispensasis" :key="d.id">
                                        <td>{{ formatDate(d.created_at) }}</td>
                                        <td>{{ d.tagihan?.semester }}</td>
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
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.card-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

/* --- Steps Guide (responsive grid) --- */
.steps-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.steps-item {
    text-align: center;
    padding: 1.25rem 1rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.steps-num {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    color: white;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.75rem;
    flex-shrink: 0;
}
.steps-text strong {
    display: block;
    color: #1f2937;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}
.steps-text p {
    font-size: 0.75rem;
    color: #6b7280;
    margin: 0;
    line-height: 1.5;
}
@media (max-width: 768px) {
    .steps-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 480px) {
    .steps-grid {
        grid-template-columns: 1fr;
    }
}

/* --- Alert --- */
.alert-box {
    display: flex;
    align-items: flex-start;
    gap: 0.625rem;
    padding: 0.875rem 1.125rem;
    border-radius: 0.625rem;
    font-size: 0.875rem;
    line-height: 1.5;
}
.alert-box i {
    margin-top: 0.125rem;
}
.alert-box.warning {
    background: #fef3c7;
    border: 1px solid #fcd34d;
    color: #92400e;
}

/* --- Guide Note --- */
.guide-note {
    display: flex;
    align-items: flex-start;
    gap: 0.625rem;
    background: #e0f2fe;
    border: 1px solid #7dd3fc;
    color: #0c4a6e;
    border-radius: 0.625rem;
    padding: 0.875rem 1.125rem;
    font-size: 0.8125rem;
    line-height: 1.5;
    margin-top: 0.5rem;
}
.guide-note i {
    margin-top: 0.125rem;
}

/* --- Template Download Box --- */
.template-download-box {
    background: #f9fafb;
    border: 1.5px dashed #d1d5db;
    border-radius: 0.75rem;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}
.d-box-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}
.d-box-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
}
.d-box-icon {
    font-size: 1.5rem;
    color: #dc2626;
    flex-shrink: 0;
}
.d-box-info strong {
    color: #1f2937;
    word-break: break-all;
}
.d-box-sub {
    color: #6b7280;
    font-size: 0.8125rem;
}

/* --- Form --- */
.form-row {
    row-gap: 0.5rem;
}
.m-form-group {
    margin-bottom: 1.25rem;
}
.m-form-group label {
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
.m-form-control:disabled {
    background: #f3f4f6;
    color: #9ca3af;
    cursor: not-allowed;
}
.m-form-select {
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236b7280' viewBox='0 0 16 16'%3E%3Cpath d='M4.646 6.146a.5.5 0 0 1 .708 0L8 8.793l2.646-2.647a.5.5 0 0 1 .708.708l-3 3a.5.5 0 0 1-.708 0l-3-3a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.875rem center;
    padding-right: 2.5rem;
}
textarea.m-form-control {
    resize: vertical;
    min-height: 90px;
}
.form-hint {
    color: #6b7280;
    font-size: 0.75rem;
    margin-top: 0.375rem;
}
.form-error {
    color: #dc2626;
    font-size: 0.8125rem;
    margin-top: 0.375rem;
}
.form-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 1rem;
    padding-top: 1.25rem;
    border-top: 1px solid #e5e7eb;
}
@media (max-width: 480px) {
    .form-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
