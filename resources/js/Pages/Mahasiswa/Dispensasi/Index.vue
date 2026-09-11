<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { formatRupiah, formatDate } from '@/utils';

const props = defineProps({
    dispensasis: {
        type: Array,
        default: () => [],
    },
    tagihans: {
        type: Array,
        default: () => [],
    },
    template: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    tagihan_id: '',
    alasan: '',
    tempo_baru: '',
});

const selectedStatusFilter = ref('all');
const selectedDispensasiDetail = ref(null);
const showDetailModal = ref(false);

const submit = () => {
    form.post(route('mahasiswa.dispensasi.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};

const openDetailModal = (dispensasi) => {
    selectedDispensasiDetail.value = dispensasi;
    showDetailModal.value = true;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedDispensasiDetail.value = null;
};

const statusInfo = (status) => {
    const map = {
        pending: {
            label: 'Menunggu Verifikasi',
            cls: 'badge-solid-warning',
            icon: 'fas fa-clock',
            dot: '#f59e0b',
        },
        disetujui: {
            label: 'Disetujui',
            cls: 'badge-solid-success',
            icon: 'fas fa-check-circle',
            dot: '#10b981',
        },
        ditolak: {
            label: 'Ditolak',
            cls: 'badge-solid-danger',
            icon: 'fas fa-times-circle',
            dot: '#ef4444',
        },
    };
    return map[status] || {
        label: status,
        cls: 'badge-solid-neutral',
        icon: 'fas fa-info-circle',
        dot: '#64748b',
    };
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

const maxTempoBaru = computed(() => {
    if (!selectedTagihan.value?.jatuh_tempo) return '';
    const d = new Date(selectedTagihan.value.jatuh_tempo);
    d.setDate(d.getDate() + 45); // Batas maksimal dispensasi 45 hari dari jatuh tempo
    return d.toISOString().split('T')[0];
});

const diffDays = computed(() => {
    if (!selectedTagihan.value?.jatuh_tempo || !form.tempo_baru) return 0;
    const oldDate = new Date(selectedTagihan.value.jatuh_tempo).getTime();
    const newDate = new Date(form.tempo_baru).getTime();
    const diff = Math.round((newDate - oldDate) / (1000 * 60 * 60 * 24));
    return diff > 0 ? diff : 0;
});

const canSubmit = computed(() => {
    return form.tagihan_id && form.alasan.trim().length >= 10 && form.tempo_baru;
});

// KPI Metrics
const totalPending = computed(() => {
    return props.dispensasis.filter(d => d.status === 'pending').length;
});

const totalDisetujui = computed(() => {
    return props.dispensasis.filter(d => d.status === 'disetujui').length;
});

const availableTagihansCount = computed(() => {
    return props.tagihans.filter(t => !t.hasPending).length;
});

// Filtered History
const filteredDispensasis = computed(() => {
    if (selectedStatusFilter.value === 'all') return props.dispensasis;
    return props.dispensasis.filter(d => d.status === selectedStatusFilter.value);
});
</script>

<template>
    <Head title="Dispensasi Pembayaran UKT" />

    <AuthenticatedLayout>
        <template #header>
            <div class="dispensasi-page-header">
                <div>
                    <h2 style="font-size:1.25rem;font-weight:700;color:#0f172a;margin:0;">
                        Layanan Dispensasi Pembayaran UKT
                    </h2>
                    <p style="font-size:0.8125rem;color:#64748b;margin:0.25rem 0 0;">
                        Permohonan penyesuaian tenggat waktu pembayaran bagi mahasiswa dengan kendala finansial mendesak.
                    </p>
                </div>

                <div v-if="template?.template_path" style="display:flex;gap:0.5rem;align-items:center;">
                    <a
                        :href="route('mahasiswa.dispensasi.download-template')"
                        target="_blank"
                        class="solid-btn btn-white-border"
                        title="Unduh Format Blanko Surat Fisik"
                    >
                        <i class="fas fa-file-download text-indigo"></i> Unduh Format Surat (PDF)
                    </a>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">

                <!-- 2. ALUR PROSEDUR PENGAJUAN (STEPPER CARD) -->
                <div class="solid-card mb-4">
                    <div class="card-header-row">
                        <div style="display:flex;align-items:center;gap:0.6rem;">
                            <div class="header-icon-badge">
                                <i class="fas fa-list-ol"></i>
                            </div>
                            <div>
                                <h3 class="card-heading">Alur & Ketentuan Pengajuan Dispensasi</h3>
                                <p class="card-subheading">Ikuti 5 tahapan resmi agar pengajuan Anda dapat diproses oleh Biro Administrasi Keuangan (BAK)</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body-content">
                        <div class="stepper-grid">
                            <div class="step-card">
                                <div class="step-header">
                                    <span class="step-badge">1</span>
                                    <i class="fas fa-file-download step-icon"></i>
                                </div>
                                <h4 class="step-title">Unduh Format Surat</h4>
                                <p class="step-desc">Unduh format template surat pernyataan resmi permohonan dispensasi.</p>
                            </div>

                            <div class="step-card">
                                <div class="step-header">
                                    <span class="step-badge">2</span>
                                    <i class="fas fa-signature step-icon"></i>
                                </div>
                                <h4 class="step-title">Isi &amp; Tempel Materai</h4>
                                <p class="step-desc">Isi lengkap data diri, tuliskan alasan, tanda tangani di atas materai Rp 10.000.</p>
                            </div>

                            <div class="step-card">
                                <div class="step-header">
                                    <span class="step-badge">3</span>
                                    <i class="fas fa-laptop-code step-icon"></i>
                                </div>
                                <h4 class="step-title">Ajukan di Sistem</h4>
                                <p class="step-desc">Pilih semester tagihan, tentukan tanggal tempo baru, dan isi form online di bawah.</p>
                            </div>

                            <div class="step-card">
                                <div class="step-header">
                                    <span class="step-badge">4</span>
                                    <i class="fas fa-building step-icon"></i>
                                </div>
                                <h4 class="step-title">Serahkan Berkas Fisik</h4>
                                <p class="step-desc">Bawa lembar surat asli bertanda tangan & materai ke loket Bagian Keuangan kampus.</p>
                            </div>

                            <div class="step-card">
                                <div class="step-header">
                                    <span class="step-badge">5</span>
                                    <i class="fas fa-calendar-check step-icon"></i>
                                </div>
                                <h4 class="step-title">Persetujuan &amp; Selesai</h4>
                                <p class="step-desc">Setelah berkas fisik divalidasi, jatuh tempo tagihan di portal otomatis diperbarui.</p>
                            </div>
                        </div>

                        <div class="policy-notice-box">
                            <i class="fas fa-shield-alt notice-icon"></i>
                            <div>
                                <strong>Ketentuan Penting:</strong> Permohonan online berfungsi sebagai pra-pendaftaran sistem. Pengajuan baru berstatus <em>Sah & Diproses</em> setelah surat fisik bermaterai asli diserahkan langsung ke loket Keuangan (BAK). Maksimal perpanjangan adalah <strong>45 hari</strong> dari jatuh tempo awal.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. FORM PENGAJUAN & TEMPLATE ACTION (SPLIT GRID) -->
                <div class="disp-main-grid mb-4">
                    <!-- Left: Download & Checklist Box -->
                    <div class="solid-card form-side-card">
                        <div class="card-header-row">
                            <div style="display:flex;align-items:center;gap:0.6rem;">
                                <div class="header-icon-badge">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div>
                                    <h3 class="card-heading">Blanko Surat Resmi</h3>
                                    <p class="card-subheading">Format fisik baku Universitas</p>
                                </div>
                            </div>
                        </div>

                        <div class="card-body-content">
                            <div v-if="template?.template_path" class="template-hero-box">
                                <div class="template-file-meta">
                                    <div class="file-icon-square">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div style="min-width:0;flex:1;">
                                        <div class="file-name" :title="template.template_filename">
                                            {{ template.template_filename || 'Format_Surat_Dispensasi_UKT.pdf' }}
                                        </div>
                                        <div class="file-sub">Dokumen Resmi Bagian Keuangan</div>
                                    </div>
                                </div>

                                <a
                                    :href="route('mahasiswa.dispensasi.download-template')"
                                    target="_blank"
                                    class="solid-btn btn-indigo-solid btn-block"
                                    style="margin-top:1rem;"
                                >
                                    <i class="fas fa-download"></i> Unduh Format Blanko
                                </a>
                            </div>

                            <div v-else class="alert-box-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div>Template surat dispensasi belum diunggah oleh administrator. Silakan hubungi loket Keuangan.</div>
                            </div>

                            <!-- Checklist Persyaratan -->
                            <div class="checklist-section">
                                <h4 class="checklist-title">Checklist Berkas Fisik:</h4>
                                <ul class="checklist-list">
                                    <li>
                                        <i class="fas fa-check-circle text-emerald"></i>
                                        <span>Surat pernyataan bermaterai Rp 10.000 asli</span>
                                    </li>
                                    <li>
                                        <i class="fas fa-check-circle text-emerald"></i>
                                        <span>Tanda tangan asli mahasiswa & orang tua/wali</span>
                                    </li>
                                    <li>
                                        <i class="fas fa-check-circle text-emerald"></i>
                                        <span>Fotokopi Kartu Tanda Mahasiswa (KTM)</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Form Pengajuan -->
                    <div class="solid-card form-body-card">
                        <div class="card-header-row">
                            <div style="display:flex;align-items:center;gap:0.6rem;">
                                <div class="header-icon-badge">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <div>
                                    <h3 class="card-heading">Formulir Pengajuan Dispensasi</h3>
                                    <p class="card-subheading">Pilih semester tagihan dan tentukan tanggal perpanjangan</p>
                                </div>
                            </div>
                        </div>

                        <div class="card-body-content">
                            <form @submit.prevent="submit" class="styled-form">
                                <!-- Pilih Tagihan -->
                                <div class="form-group">
                                    <label class="form-label">
                                        Pilih Tagihan UKT Semester <span class="text-rose">*</span>
                                    </label>
                                    <div class="select-wrapper">
                                        <select
                                            v-model="form.tagihan_id"
                                            class="form-control form-select"
                                            :disabled="tagihans.length === 0 || form.processing"
                                        >
                                            <option value="">-- Pilih Semester Tagihan yang Ingin Diajukan --</option>
                                            <option
                                                v-for="t in tagihans"
                                                :key="t.id"
                                                :value="t.id"
                                                :disabled="t.hasPending"
                                            >
                                                Semester {{ t.semester }} ({{ t.tahun_akademik }}) — {{ formatRupiah(t.nominal) }}
                                                {{ t.hasPending ? ' [Sedang Menunggu Verifikasi]' : '' }}
                                            </option>
                                        </select>
                                    </div>
                                    <div v-if="tagihans.length === 0" class="field-hint text-amber">
                                        <i class="fas fa-info-circle"></i> Tidak ada tagihan terbuka yang dapat diajukan dispensasi saat ini.
                                    </div>
                                    <div v-if="form.errors.tagihan_id" class="form-error-msg">
                                        {{ form.errors.tagihan_id }}
                                    </div>
                                </div>

                                <!-- Selected Tagihan Preview Badge -->
                                <div v-if="selectedTagihan" class="selected-tagihan-preview">
                                    <div class="preview-item">
                                        <span class="p-label">Tagihan Semester</span>
                                        <span class="p-val font-semibold">Semester {{ selectedTagihan.semester }} ({{ selectedTagihan.tahun_akademik }})</span>
                                    </div>
                                    <div class="preview-item">
                                        <span class="p-label">Nominal Tagihan</span>
                                        <span class="p-val font-mono text-indigo font-bold">{{ formatRupiah(selectedTagihan.nominal) }}</span>
                                    </div>
                                    <div class="preview-item">
                                        <span class="p-label">Jatuh Tempo Asal</span>
                                        <span class="p-val font-semibold text-rose">{{ formatDate(selectedTagihan.jatuh_tempo) }}</span>
                                    </div>
                                </div>

                                <!-- Tanggal Tempo Baru -->
                                <div class="form-group">
                                    <label class="form-label">
                                        Tanggal Jatuh Tempo Baru yang Dimohonkan <span class="text-rose">*</span>
                                    </label>
                                    <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                                        <input
                                            v-model="form.tempo_baru"
                                            type="date"
                                            class="form-control"
                                            style="flex:1;min-width:200px;"
                                            :min="minTempoBaru"
                                            :max="maxTempoBaru"
                                            :disabled="!selectedTagihan || form.processing"
                                        />
                                        <div v-if="diffDays > 0" class="extension-pill">
                                            <i class="fas fa-plus-circle"></i> +{{ diffDays }} Hari Perpanjangan
                                        </div>
                                    </div>
                                    <div v-if="selectedTagihan" class="field-hint">
                                        Batas maksimal permohonan s/d <strong>{{ formatDate(maxTempoBaru) }}</strong> (maksimal 45 hari dari tanggal jatuh tempo saat ini).
                                    </div>
                                    <div v-if="form.errors.tempo_baru" class="form-error-msg">
                                        {{ form.errors.tempo_baru }}
                                    </div>
                                </div>

                                <!-- Alasan Pengajuan -->
                                <div class="form-group">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.35rem;">
                                        <label class="form-label" style="margin-bottom:0;">
                                            Alasan Pengajuan Dispensasi <span class="text-rose">*</span>
                                        </label>
                                        <span style="font-size:0.75rem;color:#94a3b8;">
                                            {{ form.alasan.length }}/1000 karakter
                                        </span>
                                    </div>
                                    <textarea
                                        v-model="form.alasan"
                                        rows="3"
                                        maxlength="1000"
                                        class="form-control textarea-styled"
                                        placeholder="Jelaskan secara jelas dan jujur kendala atau alasan mengajukan perpanjangan tempo pembayaran UKT ini..."
                                        :disabled="form.processing"
                                    ></textarea>
                                    <div v-if="form.errors.alasan" class="form-error-msg">
                                        {{ form.errors.alasan }}
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="form-submit-row">
                                    <button
                                        type="submit"
                                        class="solid-btn btn-indigo-solid"
                                        :disabled="!canSubmit || form.processing"
                                    >
                                        <i :class="form.processing ? 'fas fa-spinner fa-spin' : 'fas fa-paper-plane'"></i>
                                        {{ form.processing ? 'Sedang Mengirim Permohonan...' : 'Kirim Pengajuan Dispensasi' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- 4. RIWAYAT PENGAJUAN DISPENSASI (TABLE & DETAIL MODAL) -->
                <div class="solid-card">
                    <div class="card-header-row" style="flex-wrap:wrap;gap:1rem;">
                        <div style="display:flex;align-items:center;gap:0.6rem;">
                            <div class="header-icon-badge">
                                <i class="fas fa-history"></i>
                            </div>
                            <div>
                                <h3 class="card-heading">Riwayat Pengajuan Dispensasi Anda</h3>
                                <p class="card-subheading">Daftar seluruh riwayat permohonan dispensasi pembayaran UKT beserta status verifikasi</p>
                            </div>
                        </div>

                        <!-- Status Filter Tabs -->
                        <div class="filter-tabs">
                            <button
                                type="button"
                                class="tab-btn"
                                :class="{ active: selectedStatusFilter === 'all' }"
                                @click="selectedStatusFilter = 'all'"
                            >
                                Semua ({{ dispensasis.length }})
                            </button>
                            <button
                                type="button"
                                class="tab-btn"
                                :class="{ active: selectedStatusFilter === 'pending' }"
                                @click="selectedStatusFilter = 'pending'"
                            >
                                Menunggu ({{ totalPending }})
                            </button>
                            <button
                                type="button"
                                class="tab-btn"
                                :class="{ active: selectedStatusFilter === 'disetujui' }"
                                @click="selectedStatusFilter = 'disetujui'"
                            >
                                Disetujui ({{ totalDisetujui }})
                            </button>
                            <button
                                type="button"
                                class="tab-btn"
                                :class="{ active: selectedStatusFilter === 'ditolak' }"
                                @click="selectedStatusFilter = 'ditolak'"
                            >
                                Ditolak
                            </button>
                        </div>
                    </div>

                    <div class="card-body-content" style="padding:0;">
                        <!-- Empty State -->
                        <div v-if="filteredDispensasis.length === 0" class="empty-state-box">
                            <div class="empty-icon-circle">
                                <i class="fas fa-folder-open"></i>
                            </div>
                            <h4 class="empty-title">Belum Ada Riwayat Pengajuan</h4>
                            <p class="empty-desc">
                                {{ selectedStatusFilter === 'all' ? 'Anda belum pernah mengajukan dispensasi perpanjangan tempo pembayaran UKT.' : 'Tidak ada data pengajuan dengan status yang dipilih.' }}
                            </p>
                        </div>

                        <div v-else>
                            <!-- Desktop Table -->
                            <div class="table-responsive-wrapper desktop-table-view">
                                <table class="solid-table">
                                    <thead>
                                        <tr>
                                            <th style="width:140px;">Tgl Pengajuan</th>
                                            <th style="width:130px;">Semester</th>
                                            <th>Perubahan Jatuh Tempo</th>
                                            <th>Alasan Mahasiswa</th>
                                            <th style="width:170px;text-align:center;">Status</th>
                                            <th style="width:100px;text-align:center;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="d in filteredDispensasis" :key="d.id" class="table-row-hover">
                                            <td>
                                                <div style="font-weight:600;color:#0f172a;font-size:0.8125rem;">
                                                    {{ formatDate(d.created_at) }}
                                                </div>
                                                <div style="font-size:0.6875rem;color:#94a3b8;font-family:monospace;">
                                                    Ref #DISP-{{ String(d.id).padStart(4, '0') }}
                                                </div>
                                            </td>

                                            <td>
                                                <span class="semester-badge">
                                                    Smt {{ d.tagihan?.semester }} ({{ d.tagihan?.tahun_akademik }})
                                                </span>
                                            </td>

                                            <td>
                                                <div class="timeline-tempo-box">
                                                    <span class="tempo-old" title="Jatuh Tempo Awal">{{ formatDate(d.tempo_awal) }}</span>
                                                    <i class="fas fa-arrow-right tempo-arrow"></i>
                                                    <span class="tempo-new font-semibold" title="Jatuh Tempo Baru yang Diajukan">{{ formatDate(d.tempo_baru) }}</span>
                                                </div>
                                            </td>

                                            <td>
                                                <div class="reason-truncate" :title="d.alasan">
                                                    "{{ d.alasan }}"
                                                </div>
                                                <div v-if="d.catatan_admin" class="admin-note-pill" :title="d.catatan_admin">
                                                    <i class="fas fa-comment-dots text-amber"></i> Catatan BAK: {{ d.catatan_admin }}
                                                </div>
                                            </td>

                                            <td style="text-align:center;">
                                                <span :class="['solid-badge', statusInfo(d.status).cls]">
                                                    <i :class="statusInfo(d.status).icon"></i>
                                                    <span style="margin-left:0.35rem;">{{ statusInfo(d.status).label }}</span>
                                                </span>
                                            </td>

                                            <td style="text-align:center;">
                                                <button
                                                    type="button"
                                                    class="action-icon-btn"
                                                    @click="openDetailModal(d)"
                                                    title="Lihat Rincian Pengajuan"
                                                >
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Mobile Card List -->
                            <div class="mobile-card-list">
                                <div v-for="d in filteredDispensasis" :key="d.id" class="mobile-disp-card">
                                    <div class="m-card-top">
                                        <span class="semester-badge">
                                            Semester {{ d.tagihan?.semester }} &bull; {{ formatDate(d.created_at) }}
                                        </span>
                                        <span :class="['solid-badge', statusInfo(d.status).cls]">
                                            <i :class="statusInfo(d.status).icon"></i>
                                            <span style="margin-left:0.25rem;">{{ statusInfo(d.status).label }}</span>
                                        </span>
                                    </div>

                                    <div class="m-tempo-timeline">
                                        <div class="m-tempo-col">
                                            <span class="m-t-label">Tempo Awal</span>
                                            <span class="m-t-val text-muted">{{ formatDate(d.tempo_awal) }}</span>
                                        </div>
                                        <i class="fas fa-arrow-right text-indigo"></i>
                                        <div class="m-tempo-col">
                                            <span class="m-t-label">Tempo Baru</span>
                                            <span class="m-t-val font-semibold text-indigo">{{ formatDate(d.tempo_baru) }}</span>
                                        </div>
                                    </div>

                                    <div class="m-reason-box">
                                        <span style="color:#64748b;font-size:0.75rem;font-weight:600;display:block;margin-bottom:0.15rem;">Alasan Pengajuan:</span>
                                        <em>"{{ d.alasan }}"</em>
                                    </div>

                                    <div v-if="d.catatan_admin" class="admin-note-pill mt-2">
                                        <i class="fas fa-comment-dots text-amber"></i> <strong>Catatan BAK:</strong> {{ d.catatan_admin }}
                                    </div>

                                    <div style="margin-top:0.75rem;text-align:right;">
                                        <button type="button" class="solid-btn btn-white-border btn-sm" @click="openDetailModal(d)">
                                            <i class="fas fa-info-circle"></i> Lihat Rincian Lengkap
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. MODAL DETAIL DISPENSASI -->
        <div v-if="showDetailModal && selectedDispensasiDetail" class="modal-overlay" @click.self="closeDetailModal">
            <div class="modal-dialog-box">
                <div class="modal-header-row">
                    <div style="display:flex;align-items:center;gap:0.6rem;">
                        <div class="modal-icon-circle">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <div>
                            <h3 class="modal-title">Rincian Permohonan Dispensasi</h3>
                            <p class="modal-subtitle">Ref #DISP-{{ String(selectedDispensasiDetail.id).padStart(4, '0') }}</p>
                        </div>
                    </div>
                    <button type="button" class="modal-close-btn" @click="closeDetailModal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body-content">
                    <!-- Status Banner -->
                    <div class="modal-status-banner">
                        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;">
                            <div>
                                <span style="font-size:0.75rem;color:#64748b;display:block;">Status Permohonan:</span>
                                <span :class="['solid-badge', statusInfo(selectedDispensasiDetail.status).cls]" style="font-size:0.875rem;padding:0.35rem 0.875rem;margin-top:0.2rem;">
                                    <i :class="statusInfo(selectedDispensasiDetail.status).icon"></i>
                                    <span style="margin-left:0.35rem;">{{ statusInfo(selectedDispensasiDetail.status).label }}</span>
                                </span>
                            </div>
                            <div style="text-align:right;">
                                <span style="font-size:0.75rem;color:#64748b;display:block;">Tanggal Diajukan:</span>
                                <strong style="font-size:0.875rem;color:#0f172a;">{{ formatDate(selectedDispensasiDetail.created_at, true) }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Details 2-Col Grid -->
                    <div class="modal-meta-grid">
                        <div class="m-meta-card">
                            <span class="m-meta-label">Semester & Tagihan</span>
                            <span class="m-meta-value font-semibold">Semester {{ selectedDispensasiDetail.tagihan?.semester }} ({{ selectedDispensasiDetail.tagihan?.tahun_akademik }})</span>
                        </div>
                        <div class="m-meta-card">
                            <span class="m-meta-label">Nominal Tagihan UKT</span>
                            <span class="m-meta-value font-mono font-bold text-indigo">{{ formatRupiah(selectedDispensasiDetail.tagihan?.nominal) }}</span>
                        </div>
                        <div class="m-meta-card">
                            <span class="m-meta-label">Jatuh Tempo Awal</span>
                            <span class="m-meta-value text-muted">{{ formatDate(selectedDispensasiDetail.tempo_awal) }}</span>
                        </div>
                        <div class="m-meta-card">
                            <span class="m-meta-label">Jatuh Tempo Baru Dimohon</span>
                            <span class="m-meta-value font-bold" style="color:#059669;">{{ formatDate(selectedDispensasiDetail.tempo_baru) }}</span>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="modal-reason-box">
                        <h4 class="m-box-title"><i class="fas fa-quote-left text-indigo"></i> Alasan Pengajuan Mahasiswa:</h4>
                        <div class="m-box-content">
                            {{ selectedDispensasiDetail.alasan }}
                        </div>
                    </div>

                    <!-- Admin Review Note -->
                    <div v-if="selectedDispensasiDetail.catatan_admin || selectedDispensasiDetail.diproses_oleh" class="modal-admin-review-box">
                        <h4 class="m-box-title"><i class="fas fa-user-check text-amber"></i> Catatan & Verifikasi Bagian Keuangan (BAK):</h4>
                        <div v-if="selectedDispensasiDetail.catatan_admin" class="m-box-content" style="color:#92400e;font-weight:500;">
                            {{ selectedDispensasiDetail.catatan_admin }}
                        </div>
                        <div v-if="selectedDispensasiDetail.diproses_oleh" style="font-size:0.75rem;color:#78350f;margin-top:0.5rem;">
                            Diverifikasi oleh: <strong>{{ selectedDispensasiDetail.diproses_oleh?.name || 'Administrator BAK' }}</strong>
                            <span v-if="selectedDispensasiDetail.diproses_at"> &bull; {{ formatDate(selectedDispensasiDetail.diproses_at, true) }}</span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer-row">
                    <button type="button" class="solid-btn btn-white-border" @click="closeDetailModal">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Page Header */
.dispensasi-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

/* 1. Top KPI Cards */
.disp-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
    margin-bottom: 1.5rem;
}

.metric-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s, box-shadow 0.2s;
}

.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.metric-icon-box {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.bg-blue-light { background: #eff6ff; }
.text-blue-dark { color: #2563eb; }
.bg-amber-light { background: #fffbeb; }
.text-amber-dark { color: #d97706; }
.bg-emerald-light { background: #ecfdf5; }
.text-emerald-dark { color: #059669; }

.metric-info {
    min-width: 0;
}

.metric-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 0.2rem;
}

.metric-value {
    font-size: 1.25rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
}

.metric-desc {
    font-size: 0.6875rem;
    color: #94a3b8;
    margin-top: 0.2rem;
}

/* 2. Solid Cards */
.solid-card {
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}

.card-header-row {
    background: #f8fafc;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.header-icon-badge {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: #eff6ff;
    color: #4f46e5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.card-heading {
    font-size: 0.9375rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
}

.card-subheading {
    font-size: 0.75rem;
    color: #64748b;
    margin: 0.15rem 0 0;
}

.card-body-content {
    padding: 1.5rem;
}

/* Stepper */
.stepper-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.step-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    transition: transform 0.2s;
}

.step-card:hover {
    transform: translateY(-2px);
    border-color: #cbd5e1;
    background: #ffffff;
}

.step-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.65rem;
}

.step-badge {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #4f46e5;
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
}

.step-icon {
    font-size: 1rem;
    color: #94a3b8;
}

.step-title {
    font-size: 0.8125rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.35rem;
}

.step-desc {
    font-size: 0.6875rem;
    color: #64748b;
    line-height: 1.4;
    margin: 0;
}

.policy-notice-box {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    padding: 0.875rem 1.25rem;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    font-size: 0.8125rem;
    color: #1e40af;
    line-height: 1.45;
}

.notice-icon {
    font-size: 1.15rem;
    color: #3b82f6;
    margin-top: 0.1rem;
    flex-shrink: 0;
}

/* 3. Main Split Grid */
.disp-main-grid {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 1.25rem;
    align-items: start;
}

.template-hero-box {
    background: #f8fafc;
    border: 1.5px dashed #cbd5e1;
    border-radius: 10px;
    padding: 1.25rem;
}

.template-file-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.file-icon-square {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    background: #fee2e2;
    color: #dc2626;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.file-name {
    font-size: 0.8125rem;
    font-weight: 700;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.file-sub {
    font-size: 0.6875rem;
    color: #64748b;
    margin-top: 0.15rem;
}

.btn-block {
    width: 100%;
}

.alert-box-warning {
    background: #fffbeb;
    border: 1px solid #fde68a;
    color: #b45309;
    border-radius: 8px;
    padding: 0.875rem 1rem;
    font-size: 0.8125rem;
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
}

.checklist-section {
    margin-top: 1.25rem;
    padding-top: 1.25rem;
    border-top: 1px solid #f1f5f9;
}

.checklist-title {
    font-size: 0.75rem;
    font-weight: 800;
    color: #334155;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin: 0 0 0.6rem;
}

.checklist-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.checklist-list li {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: #475569;
    line-height: 1.35;
}

/* Styled Form */
.styled-form {
    display: flex;
    flex-direction: column;
    gap: 1.15rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-label {
    font-size: 0.8125rem;
    font-weight: 700;
    color: #334155;
    margin-bottom: 0.4rem;
}

.text-rose { color: #e11d48; }
.text-emerald { color: #059669; }
.text-amber { color: #d97706; }
.text-indigo { color: #4f46e5; }
.font-semibold { font-weight: 600; }
.font-bold { font-weight: 800; }
.font-mono { font-family: monospace; }
.text-muted { color: #64748b; }

.form-control {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.875rem;
    color: #0f172a;
    background: #ffffff;
    transition: all 0.2s;
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
}

.form-control:disabled {
    background: #f1f5f9;
    color: #94a3b8;
    cursor: not-allowed;
}

.form-select {
    cursor: pointer;
}

.textarea-styled {
    resize: vertical;
    min-height: 80px;
}

.field-hint {
    font-size: 0.7188rem;
    color: #64748b;
    margin-top: 0.35rem;
}

.form-error-msg {
    font-size: 0.75rem;
    color: #e11d48;
    font-weight: 600;
    margin-top: 0.35rem;
}

.selected-tagihan-preview {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.preview-item {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.p-label {
    font-size: 0.6875rem;
    color: #64748b;
    font-weight: 500;
}

.p-val {
    font-size: 0.8125rem;
}

.extension-pill {
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
    border-radius: 6px;
    padding: 0.5rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    white-space: nowrap;
}

.form-submit-row {
    display: flex;
    justify-content: flex-end;
    padding-top: 0.75rem;
    border-top: 1px solid #f1f5f9;
}

/* 4. History Tabs & Table */
.filter-tabs {
    display: flex;
    align-items: center;
    background: #f1f5f9;
    padding: 3px;
    border-radius: 8px;
    gap: 2px;
}

.tab-btn {
    background: transparent;
    border: none;
    font-family: inherit;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s;
}

.tab-btn:hover {
    color: #0f172a;
}

.tab-btn.active {
    background: #ffffff;
    color: #4f46e5;
    font-weight: 700;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.table-responsive-wrapper {
    overflow-x: auto;
}

.solid-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
}

.solid-table thead th {
    background: #f8fafc;
    color: #475569;
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
}

.solid-table tbody td {
    padding: 0.875rem 1rem;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
}

.table-row-hover:hover {
    background: #f8fafc;
}

.semester-badge {
    display: inline-block;
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #e2e8f0;
    font-size: 0.7188rem;
    font-weight: 700;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
}

.timeline-tempo-box {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.75rem;
}

.tempo-old {
    color: #94a3b8;
    text-decoration: line-through;
}

.tempo-arrow {
    font-size: 0.65rem;
    color: #4f46e5;
}

.tempo-new {
    color: #059669;
}

.reason-truncate {
    font-size: 0.75rem;
    color: #475569;
    max-width: 280px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.admin-note-pill {
    margin-top: 0.25rem;
    font-size: 0.6875rem;
    color: #b45309;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 4px;
    padding: 0.15rem 0.4rem;
    display: inline-block;
    max-width: 280px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.action-icon-btn {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: #4f46e5;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s;
}

.action-icon-btn:hover {
    background: #4f46e5;
    color: #ffffff;
    border-color: #4f46e5;
}

/* Empty State */
.empty-state-box {
    text-align: center;
    padding: 3rem 1.5rem;
}

.empty-icon-circle {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #f1f5f9;
    color: #94a3b8;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 0.75rem;
}

.empty-title {
    font-size: 0.9375rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.25rem;
}

.empty-desc {
    font-size: 0.8125rem;
    color: #64748b;
    max-width: 360px;
    margin: 0 auto;
}

/* Mobile Cards */
.mobile-card-list {
    display: none;
    padding: 0.75rem;
}

.mobile-disp-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 0.75rem;
}

.m-card-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.6rem;
}

.m-tempo-timeline {
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    border-radius: 6px;
    padding: 0.5rem 0.75rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.m-tempo-col {
    display: flex;
    flex-direction: column;
}

.m-t-label {
    font-size: 0.625rem;
    color: #94a3b8;
}

.m-t-val {
    font-size: 0.75rem;
}

.m-reason-box {
    background: #f8fafc;
    border-radius: 6px;
    padding: 0.5rem 0.65rem;
    font-size: 0.75rem;
    color: #334155;
    line-height: 1.4;
}

/* 5. Modal Dialog */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 1050;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.modal-dialog-box {
    background: #ffffff;
    width: 100%;
    max-width: 560px;
    border-radius: 16px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    animation: modal-pop 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modal-pop {
    0% { transform: scale(0.95); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

.modal-header-row {
    background: #f8fafc;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-icon-circle {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: #eff6ff;
    color: #4f46e5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

.modal-title {
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
}

.modal-subtitle {
    font-size: 0.75rem;
    color: #64748b;
    font-family: monospace;
    margin: 0.1rem 0 0;
}

.modal-close-btn {
    background: transparent;
    border: none;
    font-size: 1.15rem;
    color: #94a3b8;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 6px;
    transition: color 0.15s;
}

.modal-close-btn:hover {
    color: #0f172a;
}

.modal-body-content {
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    max-height: 70vh;
    overflow-y: auto;
}

.modal-status-banner {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.875rem 1rem;
}

.modal-meta-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}

.m-meta-card {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.65rem 0.875rem;
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.m-meta-label {
    font-size: 0.6875rem;
    color: #64748b;
}

.m-meta-value {
    font-size: 0.8125rem;
}

.modal-reason-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.875rem 1rem;
}

.m-box-title {
    font-size: 0.75rem;
    font-weight: 800;
    color: #334155;
    margin: 0 0 0.4rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.m-box-content {
    font-size: 0.8125rem;
    color: #1e293b;
    line-height: 1.45;
}

.modal-admin-review-box {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 10px;
    padding: 0.875rem 1rem;
}

.modal-footer-row {
    background: #f8fafc;
    padding: 0.75rem 1.25rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
}

/* Global Solid Buttons & Badges */
.solid-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-family: inherit;
    font-weight: 600;
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid transparent;
}

.btn-white-border {
    background: #ffffff;
    color: #334155;
    border-color: #cbd5e1;
}

.btn-white-border:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a;
}

.btn-indigo-solid {
    background: #4f46e5;
    color: #ffffff;
    border-color: #4f46e5;
}

.btn-indigo-solid:hover {
    background: #4338ca;
    color: #ffffff;
}

.btn-sm {
    font-size: 0.75rem;
    padding: 0.35rem 0.65rem;
}

.solid-badge {
    display: inline-flex;
    align-items: center;
    font-weight: 700;
    font-size: 0.7188rem;
    padding: 0.25rem 0.6rem;
    border-radius: 9999px;
    letter-spacing: 0.02em;
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

.badge-solid-warning {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
}

.badge-solid-neutral {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
}

/* Responsive Breakpoints */
@media (max-width: 992px) {
    .stepper-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .disp-main-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    /* Header */
    .dispensasi-page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .dispensasi-page-header h2 {
        font-size: 1.05rem !important;
    }

    .dispensasi-page-header p {
        font-size: 0.75rem !important;
    }

    /* Desktop/Mobile view toggle */
    .desktop-table-view {
        display: none;
    }
    .mobile-card-list {
        display: block;
    }

    /* Stepper Grid */
    .stepper-grid {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    .step-card {
        padding: 0.85rem;
    }

    .step-title {
        font-size: 0.82rem;
    }

    .step-desc {
        font-size: 0.72rem;
    }

    /* Policy Notice */
    .policy-notice-box {
        font-size: 0.75rem;
        padding: 0.75rem;
        gap: 0.5rem;
    }

    /* Card Headers */
    .card-header-row {
        padding: 0.85rem;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.65rem;
    }

    .card-heading {
        font-size: 0.92rem;
    }

    .card-subheading {
        font-size: 0.72rem;
    }

    .header-icon-badge {
        width: 2rem;
        height: 2rem;
        font-size: 0.85rem;
    }

    /* Card Body */
    .card-body-content {
        padding: 0.85rem;
    }

    /* Form */
    .styled-form .form-group {
        margin-bottom: 1rem;
    }

    .form-label {
        font-size: 0.78rem;
    }

    .form-control {
        font-size: 0.82rem;
        padding: 0.5rem 0.65rem;
    }

    .textarea-styled {
        min-height: 80px;
    }

    /* Selected Tagihan Preview */
    .selected-tagihan-preview {
        grid-template-columns: 1fr;
        gap: 0.5rem;
        padding: 0.75rem;
    }

    /* Extension Pill */
    .extension-pill {
        font-size: 0.72rem;
    }

    /* Submit Button */
    .form-submit-row {
        flex-direction: column;
    }

    .form-submit-row .solid-btn {
        width: 100%;
        justify-content: center;
        min-height: 2.75rem;
    }

    /* Template Download Box */
    .template-hero-box {
        padding: 0.75rem;
    }

    .file-name {
        font-size: 0.78rem;
    }

    .file-sub {
        font-size: 0.68rem;
    }

    /* Checklist */
    .checklist-list li {
        font-size: 0.78rem;
    }

    /* Filter Tabs */
    .filter-tabs {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        white-space: nowrap;
        width: 100%;
    }

    .filter-tabs::-webkit-scrollbar {
        display: none;
    }

    .tab-btn {
        flex-shrink: 0;
        font-size: 0.72rem;
        padding: 0.35rem 0.65rem;
    }

    /* Mobile Cards */
    .mobile-disp-card {
        padding: 0.85rem;
        margin: 0.5rem 0.65rem;
        border-radius: 0.75rem;
    }

    .m-card-top {
        flex-wrap: wrap;
        gap: 0.45rem;
    }

    .semester-badge {
        font-size: 0.72rem;
    }

    .solid-badge {
        font-size: 0.68rem;
    }

    .m-tempo-timeline {
        padding: 0.55rem;
        gap: 0.35rem;
    }

    .m-t-label {
        font-size: 0.62rem;
    }

    .m-t-val {
        font-size: 0.78rem;
    }

    .m-reason-box {
        font-size: 0.78rem;
        padding: 0.65rem;
    }

    /* Modal */
    .modal-overlay {
        padding: 0.5rem;
        align-items: flex-end;
    }

    .modal-dialog-box {
        max-width: 100%;
        max-height: 85vh;
        border-radius: 1rem 1rem 0 0;
    }

    .modal-header-row {
        padding: 0.85rem;
    }

    .modal-icon-circle {
        width: 2rem;
        height: 2rem;
        font-size: 0.85rem;
    }

    .modal-title {
        font-size: 0.92rem;
    }

    .modal-subtitle {
        font-size: 0.68rem;
    }

    .modal-body-content {
        padding: 0.85rem;
    }

    .modal-meta-grid {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }

    .modal-reason-box,
    .modal-admin-review-box {
        padding: 0.75rem;
    }

    .m-box-title {
        font-size: 0.72rem;
    }

    .m-box-content {
        font-size: 0.78rem;
    }

    .modal-footer-row {
        padding: 0.65rem 0.85rem;
    }

    .modal-footer-row .solid-btn {
        width: 100%;
        justify-content: center;
        min-height: 2.75rem;
    }

    /* Empty State */
    .empty-state-box {
        padding: 2rem 1rem;
    }

    .empty-icon-circle {
        width: 3rem;
        height: 3rem;
        font-size: 1.25rem;
    }

    .empty-title {
        font-size: 0.92rem;
    }

    .empty-desc {
        font-size: 0.78rem;
    }

    /* Bottom nav padding */
    .page-body {
        padding-bottom: calc(76px + env(safe-area-inset-bottom, 0px));
    }
}
</style>
