<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    items: Array,
    activeTa: Object,
    semesterAktif: Object,
    metrics: Object,
    countdown: Object,
});

const { success, error: toastError } = useToast();

const activeTab = ref('kontrol'); // 'kontrol' | 'master'

// Form Master CRUD
const editMode = ref(false);
const editId = ref(null);
const masterForm = useForm({
    nama: '',
    semester: 'Ganjil',
});

function openEdit(item) {
    activeTab.value = 'master';
    editMode.value = true;
    editId.value = item.id;
    masterForm.nama = item.nama;
    masterForm.semester = item.semester;
    masterForm.clearErrors();
}

function resetMasterForm() {
    editMode.value = false;
    editId.value = null;
    masterForm.reset();
    masterForm.clearErrors();
}

function submitMaster() {
    if (editMode.value) {
        masterForm.put(route('admin.tahun-akademik.update', editId.value), {
            preserveScroll: true,
            onSuccess: () => {
                resetMasterForm();
                success('Tahun akademik berhasil diperbarui.');
            },
        });
    } else {
        masterForm.post(route('admin.tahun-akademik.store'), {
            preserveScroll: true,
            onSuccess: () => {
                resetMasterForm();
                success('Tahun akademik baru berhasil ditambahkan.');
            },
        });
    }
}

// Modal Aktivasi Tahun Akademik
const showActivateModal = ref(false);
const itemToActivate = ref(null);
const activateForm = useForm({
    jatuh_tempo: props.semesterAktif?.jatuh_tempo || '',
    generate_tagihan: true,
});

function openActivateModal(item) {
    itemToActivate.value = item;
    activateForm.jatuh_tempo = props.semesterAktif?.jatuh_tempo || new Date(Date.now() + 30 * 86400000).toISOString().substring(0, 10);
    activateForm.generate_tagihan = true;
    showActivateModal.value = true;
}

function executeActivate() {
    if (!itemToActivate.value) return;
    activateForm.post(route('admin.tahun-akademik.activate', itemToActivate.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showActivateModal.value = false;
            itemToActivate.value = null;
        },
    });
}

// Modal Update Jatuh Tempo
const showJatuhTempoModal = ref(false);
const jatuhTempoForm = useForm({
    jatuh_tempo: props.semesterAktif?.jatuh_tempo || '',
    update_tagihan_unpaid: true,
});

function openJatuhTempoModal() {
    jatuhTempoForm.jatuh_tempo = props.semesterAktif?.jatuh_tempo || '';
    jatuhTempoForm.update_tagihan_unpaid = true;
    showJatuhTempoModal.value = true;
}

function executeUpdateJatuhTempo() {
    jatuhTempoForm.put(route('admin.tahun-akademik.jatuh-tempo'), {
        preserveScroll: true,
        onSuccess: () => {
            showJatuhTempoModal.value = false;
        },
    });
}

// Modal Generate Tagihan Massal
const showGenerateModal = ref(false);
const isGenerating = ref(false);

function openGenerateModal() {
    showGenerateModal.value = true;
}

function executeGenerateTagihan() {
    isGenerating.value = true;
    router.post(route('admin.tahun-akademik.generate-tagihan'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isGenerating.value = false;
            showGenerateModal.value = false;
        },
    });
}

// Modal Delete & Blocked Delete
const showDeleteModal = ref(false);
const showBlockedDeleteModal = ref(false);
const itemToDelete = ref(null);

function handleDeleteClick(item) {
    itemToDelete.value = item;
    if (item.tagihan_count > 0 || item.is_aktif) {
        showBlockedDeleteModal.value = true;
    } else {
        showDeleteModal.value = true;
    }
}

function executeDelete() {
    if (!itemToDelete.value) return;
    router.delete(route('admin.tahun-akademik.destroy', itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            itemToDelete.value = null;
        },
    });
}

// Format Rupiah
const formatRupiah = (val) => {
    return Number(val || 0).toLocaleString('id-ID');
};

// Persentase Lunas
const persentaseLunas = computed(() => {
    if (!props.metrics?.total_tagihan || props.metrics.total_tagihan === 0) return 0;
    return Math.round((props.metrics.lunas_count / props.metrics.total_tagihan) * 100);
});
</script>

<template>
    <Head title="Periode & Tahun Akademik" />
    <AuthenticatedLayout>
        <div class="page-header">
            <div>
                <h1 class="page-title">Periode & Tahun Akademik</h1>
                <p class="page-subtitle">Pusat kontrol semester aktif, batas jatuh tempo, dan master data tahun akademik</p>
            </div>
            <!-- Tab Switcher -->
            <div class="tab-switcher">
                <button
                    type="button"
                    class="tab-switch-btn"
                    :class="{ active: activeTab === 'kontrol' }"
                    @click="activeTab = 'kontrol'"
                >
                    <i class="fas fa-bolt"></i> Kontrol Semester Aktif
                </button>
                <button
                    type="button"
                    class="tab-switch-btn"
                    :class="{ active: activeTab === 'master' }"
                    @click="activeTab = 'master'"
                >
                    <i class="fas fa-calendar-alt"></i> Master Data Tahun Akademik
                    <span class="tab-counter">{{ items.length }}</span>
                </button>
            </div>
        </div>

        <div class="page-body">
            <!-- ================= TAB 1: KONTROL SEMESTER AKTIF ================= -->
            <div v-show="activeTab === 'kontrol'" class="fade-in-section">
                <!-- Hero Active Period Banner -->
                <div class="solid-hero-card">
                    <div class="hero-left">
                        <div class="hero-badge-tag">
                            <span class="live-dot"></span> PERIODE BERJALAN SAAT INI
                        </div>
                        <h2 class="hero-period-title">
                            Tahun Akademik {{ activeTa?.nama || '-' }} — Semester {{ activeTa?.semester || '-' }}
                        </h2>
                        <div class="hero-meta-row">
                            <div class="hero-meta-item">
                                <i class="fas fa-calendar-day"></i>
                                <span>Jatuh Tempo: <strong>{{ countdown?.formatted_date || '-' }}</strong></span>
                            </div>
                            <div class="hero-meta-item">
                                <span v-if="countdown?.diff_days !== null" class="solid-pill" :class="{
                                    'pill-danger': countdown?.is_expired,
                                    'pill-warning': countdown?.diff_days === 0,
                                    'pill-success': !countdown?.is_expired && countdown?.diff_days > 0
                                }">
                                    <i :class="countdown?.is_expired ? 'fas fa-exclamation-triangle' : 'fas fa-clock'"></i>
                                    {{ countdown?.is_expired
                                        ? `Telah Lewat Jatuh Tempo (${Math.abs(countdown?.diff_days)} hari lalu)`
                                        : (countdown?.diff_days === 0 ? 'Jatuh Tempo Hari Ini' : `Tersisa ${countdown?.diff_days} Hari Lagi`)
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="hero-right">
                        <button type="button" class="m-btn m-btn-primary" @click="openJatuhTempoModal">
                            <i class="fas fa-calendar-day"></i> Ubah Jatuh Tempo
                        </button>
                        <button type="button" class="m-btn m-btn-success" @click="openGenerateModal">
                            <i class="fas fa-sync-alt"></i> Generate Tagihan
                        </button>
                        <Link :href="route('admin.tagihan.index')" class="m-btn m-btn-secondary">
                            <i class="fas fa-receipt"></i> Data Tagihan
                        </Link>
                    </div>
                </div>

                <!-- Metric Cards Grid -->
                <div class="metrics-grid">
                    <!-- Mahasiswa Aktif -->
                    <div class="metric-card">
                        <div class="metric-card-body">
                            <div class="metric-icon-wrap" style="background:#eff6ff;color:#2563eb;">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div>
                                <div class="metric-label">Mahasiswa Aktif</div>
                                <div class="metric-value">{{ formatRupiah(metrics?.total_mahasiswa_aktif) }}</div>
                                <div class="metric-sub">Terdaftar di sistem UKT</div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Tagihan -->
                    <div class="metric-card">
                        <div class="metric-card-body">
                            <div class="metric-icon-wrap" style="background:#f5f3ff;color:#4f46e5;">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <div>
                                <div class="metric-label">Tagihan Diterbitkan</div>
                                <div class="metric-value">{{ formatRupiah(metrics?.total_tagihan) }} <span style="font-size:0.875rem;font-weight:500;color:#64748b;">Tagihan</span></div>
                                <div class="metric-sub">Total Rp {{ formatRupiah(metrics?.total_nominal) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Lunas -->
                    <div class="metric-card">
                        <div class="metric-card-body">
                            <div class="metric-icon-wrap" style="background:#f0fdf4;color:#16a34a;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <div class="metric-label">Pembayaran Lunas</div>
                                <div class="metric-value" style="color:#15803d;">{{ formatRupiah(metrics?.lunas_count) }} <span style="font-size:0.875rem;font-weight:600;color:#16a34a;">({{ persentaseLunas }}%)</span></div>
                                <div class="metric-sub">Terkumpul Rp {{ formatRupiah(metrics?.lunas_nominal) }}</div>
                            </div>
                        </div>
                        <div class="metric-progress-bar">
                            <div class="metric-progress-fill" :style="{ width: persentaseLunas + '%' }"></div>
                        </div>
                    </div>

                    <!-- Belum Lunas -->
                    <div class="metric-card">
                        <div class="metric-card-body">
                            <div class="metric-icon-wrap" style="background:#fefce8;color:#d97706;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <div class="metric-label">Belum Lunas / Tunggakan</div>
                                <div class="metric-value" style="color:#b45309;">{{ formatRupiah(metrics?.belum_lunas_count) }} <span style="font-size:0.875rem;font-weight:500;color:#64748b;">Tagihan</span></div>
                                <div class="metric-sub">Sisa Rp {{ formatRupiah(metrics?.belum_lunas_nominal) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Guide Box -->
                <div class="guide-box">
                    <div class="guide-icon"><i class="fas fa-info-circle"></i></div>
                    <div class="guide-content">
                        <h4 class="guide-title">Cara Kerja Penagihan Semester Aktif</h4>
                        <p class="guide-desc">
                            Periode yang sedang aktif (<strong>{{ activeTa?.nama }} {{ activeTa?.semester }}</strong>) menentukan perhitungan tagihan UKT mahasiswa secara otomatis. Saat Anda menekan <em>"Generate Tagihan"</em>, sistem akan mengalkulasi nominal tagihan tiap mahasiswa berdasarkan <strong>Angkatan</strong>, <strong>Program Studi</strong>, <strong>Tarif Biaya</strong>, serta diskon/talangan <strong>Beasiswa</strong> yang aktif. Tagihan yang sudah pernah dibuat tidak akan digenerate ulang.
                        </p>
                    </div>
                </div>
            </div>

            <!-- ================= TAB 2: MASTER DATA TAHUN AKADEMIK ================= -->
            <div v-show="activeTab === 'master'" class="layout-split fade-in-section">
                <!-- Kiri: Tabel Master -->
                <div class="panel-table">
                    <div class="custom-card">
                        <div class="card-header">
                            <div>
                                <h4 style="font-size:1rem;font-weight:700;margin:0;color:#0f172a;">
                                    <i class="fas fa-list" style="margin-right:0.5rem;color:#4f46e5;"></i>
                                    Daftar Master Tahun Akademik
                                </h4>
                                <span style="font-size:0.75rem;color:#64748b;">Total {{ items.length }} periode tersimpan</span>
                            </div>
                        </div>
                        <div class="card-body" style="padding:0;">
                            <div class="table-responsive">
                                <table class="m-data-table">
                                    <thead>
                                        <tr>
                                            <th style="width:50px;">No</th>
                                            <th>Tahun Akademik</th>
                                            <th>Semester</th>
                                            <th>Tagihan Terkait</th>
                                            <th>Status</th>
                                            <th style="width:160px;text-align:right;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="items.length === 0">
                                            <td colspan="6" style="text-align:center;color:#94a3b8;padding:3rem 1rem;">
                                                <i class="fas fa-calendar-times" style="font-size:2rem;margin-bottom:0.5rem;display:block;"></i>
                                                Belum ada data master tahun akademik
                                            </td>
                                        </tr>
                                        <tr v-for="(item, idx) in items" :key="item.id" :class="{ 'row-current-active': item.is_aktif, 'row-editing': editMode && editId === item.id }">
                                            <td style="font-weight:500;color:#64748b;">{{ idx + 1 }}</td>
                                            <td>
                                                <div style="font-weight:700;color:#0f172a;font-size:0.9375rem;">{{ item.nama }}</div>
                                                <div style="font-size:0.75rem;color:#64748b;">Dibuat: {{ item.created_at }}</div>
                                            </td>
                                            <td>
                                                <span class="m-badge" :class="item.semester === 'Ganjil' ? 'm-badge-info' : 'm-badge-purple'">
                                                    {{ item.semester }}
                                                </span>
                                            </td>
                                            <td>
                                                <span v-if="item.tagihan_count > 0" class="tagihan-badge">
                                                    <i class="fas fa-receipt"></i> {{ formatRupiah(item.tagihan_count) }} Tagihan
                                                </span>
                                                <span v-else class="tagihan-empty">
                                                    0 Tagihan
                                                </span>
                                            </td>
                                            <td>
                                                <span v-if="item.is_aktif" class="status-pill status-active">
                                                    <span class="status-dot"></span> Aktif Berjalan
                                                </span>
                                                <span v-else class="status-pill status-inactive">
                                                    Non-aktif
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-btns" style="justify-content:flex-end;">
                                                    <button
                                                        v-if="!item.is_aktif"
                                                        type="button"
                                                        class="m-btn m-btn-sm m-btn-success"
                                                        @click="openActivateModal(item)"
                                                        title="Aktifkan Periode Ini"
                                                    >
                                                        <i class="fas fa-toggle-off"></i> Aktifkan
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="m-btn m-btn-sm m-btn-secondary"
                                                        @click="openEdit(item)"
                                                        title="Edit Data"
                                                    >
                                                        <i class="fas fa-pen"></i>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="m-btn m-btn-sm m-btn-danger"
                                                        @click="handleDeleteClick(item)"
                                                        title="Hapus"
                                                        :disabled="item.is_aktif"
                                                    >
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

                <!-- Kanan: Form Tambah/Edit Master -->
                <div class="panel-form">
                    <div class="custom-card">
                        <div class="card-header">
                            <h4 style="font-size:1rem;font-weight:700;margin:0;color:#0f172a;">
                                <i :class="editMode ? 'fas fa-pen' : 'fas fa-plus'" style="margin-right:0.5rem;color:#2563eb;"></i>
                                {{ editMode ? 'Edit Tahun Akademik' : 'Tambah Tahun Akademik' }}
                            </h4>
                        </div>
                        <div class="card-body">
                            <form @submit.prevent="submitMaster">
                                <div class="form-group">
                                    <label class="form-label">Tahun Akademik <span style="color:#dc2626;">*</span></label>
                                    <input
                                        v-model="masterForm.nama"
                                        type="text"
                                        class="form-control"
                                        placeholder="Contoh: 2026/2027"
                                        required
                                    />
                                    <div v-if="masterForm.errors.nama" class="form-error">{{ masterForm.errors.nama }}</div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Semester <span style="color:#dc2626;">*</span></label>
                                    <select v-model="masterForm.semester" class="form-control" required>
                                        <option value="Ganjil">Ganjil</option>
                                        <option value="Genap">Genap</option>
                                    </select>
                                    <div v-if="masterForm.errors.semester" class="form-error">{{ masterForm.errors.semester }}</div>
                                </div>

                                <div class="form-actions" style="margin-top:1.5rem;">
                                    <button
                                        v-if="editMode"
                                        type="button"
                                        class="m-btn m-btn-secondary"
                                        @click="resetMasterForm"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="submit"
                                        class="m-btn m-btn-primary"
                                        style="flex:1;"
                                        :disabled="masterForm.processing"
                                    >
                                        <i class="fas" :class="masterForm.processing ? 'fa-spinner fa-pulse' : (editMode ? 'fa-save' : 'fa-plus')"></i>
                                        {{ masterForm.processing ? 'Menyimpan...' : (editMode ? 'Simpan Perubahan' : 'Tambah Tahun Akademik') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= MODALS ================= -->
        <Teleport to="body">
            <!-- Modal 1: Aktivasi Tahun Akademik (Wajib Set Jatuh Tempo) -->
            <div v-if="showActivateModal" class="modal-overlay" @click.self="showActivateModal = false">
                <div class="modal-box" style="max-width: 500px;">
                    <div class="modal-header">
                        <h3 style="color: #15803d; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-check-circle"></i> Aktifkan Tahun Akademik
                        </h3>
                        <button class="modal-close" @click="showActivateModal = false"><i class="fas fa-times"></i></button>
                    </div>
                    <form @submit.prevent="executeActivate">
                        <div class="modal-body" style="padding: 1.25rem 1.5rem;">
                            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.75rem;padding:1rem;margin-bottom:1.25rem;">
                                <div style="font-size:0.75rem;color:#64748b;text-transform:uppercase;font-weight:700;">Periode Yang Akan Diaktifkan</div>
                                <div style="font-size:1.125rem;font-weight:800;color:#0f172a;margin-top:0.25rem;">
                                    {{ itemToActivate?.nama }} (Semester {{ itemToActivate?.semester }})
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Tanggal Jatuh Tempo UKT <span style="color:#dc2626;">*</span></label>
                                <input
                                    v-model="activateForm.jatuh_tempo"
                                    type="date"
                                    class="form-control"
                                    required
                                />
                                <div style="font-size:0.75rem;color:#64748b;margin-top:0.25rem;">
                                    Batas akhir pembayaran mahasiswa untuk periode ini.
                                </div>
                            </div>

                            <div class="form-group" style="margin-top:1rem;">
                                <label class="toggle-label" style="display:flex;align-items:flex-start;gap:0.75rem;cursor:pointer;">
                                    <input
                                        type="checkbox"
                                        v-model="activateForm.generate_tagihan"
                                        style="margin-top:0.25rem;width:1.125rem;height:1.125rem;accent-color:#16a34a;"
                                    />
                                    <div style="font-size:0.8125rem;color:#334155;line-height:1.4;">
                                        <strong>Generate Tagihan Massal Sekarang</strong>
                                        <div style="font-size:0.75rem;color:#64748b;">
                                            Otomatis buat tagihan UKT untuk seluruh mahasiswa aktif berdasarkan tarif biaya saat ini.
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="m-btn m-btn-secondary" @click="showActivateModal = false">Batal</button>
                            <button type="submit" class="m-btn m-btn-primary" style="background:#16a34a;" :disabled="activateForm.processing">
                                <i class="fas" :class="activateForm.processing ? 'fa-spinner fa-pulse' : 'fa-check'"></i>
                                {{ activateForm.processing ? 'Mengaktifkan...' : 'Ya, Aktifkan Periode' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal 2: Update Jatuh Tempo -->
            <div v-if="showJatuhTempoModal" class="modal-overlay" @click.self="showJatuhTempoModal = false">
                <div class="modal-box" style="max-width: 460px;">
                    <div class="modal-header">
                        <h3 style="color: #0f172a; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-calendar-day" style="color:#2563eb;"></i> Ubah Tanggal Jatuh Tempo
                        </h3>
                        <button class="modal-close" @click="showJatuhTempoModal = false"><i class="fas fa-times"></i></button>
                    </div>
                    <form @submit.prevent="executeUpdateJatuhTempo">
                        <div class="modal-body" style="padding: 1.25rem 1.5rem;">
                            <div class="form-group">
                                <label class="form-label">Tanggal Jatuh Tempo Baru <span style="color:#dc2626;">*</span></label>
                                <input
                                    v-model="jatuhTempoForm.jatuh_tempo"
                                    type="date"
                                    class="form-control"
                                    required
                                />
                            </div>

                            <div class="form-group" style="margin-top:1rem;">
                                <label class="toggle-label" style="display:flex;align-items:flex-start;gap:0.75rem;cursor:pointer;">
                                    <input
                                        type="checkbox"
                                        v-model="jatuhTempoForm.update_tagihan_unpaid"
                                        style="margin-top:0.25rem;width:1.125rem;height:1.125rem;accent-color:#2563eb;"
                                    />
                                    <div style="font-size:0.8125rem;color:#334155;line-height:1.4;">
                                        <strong>Perbarui tagihan mahasiswa yang belum lunas</strong>
                                        <div style="font-size:0.75rem;color:#64748b;">
                                            Tanggal jatuh tempo pada tagihan yang berstatus belum bayar di semester ini akan ikut diupdate.
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="m-btn m-btn-secondary" @click="showJatuhTempoModal = false">Batal</button>
                            <button type="submit" class="m-btn m-btn-primary" :disabled="jatuhTempoForm.processing">
                                <i class="fas" :class="jatuhTempoForm.processing ? 'fa-spinner fa-pulse' : 'fa-save'"></i>
                                {{ jatuhTempoForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal 3: Konfirmasi Generate Tagihan Massal -->
            <div v-if="showGenerateModal" class="modal-overlay" @click.self="showGenerateModal = false">
                <div class="modal-box" style="max-width: 480px;">
                    <div class="modal-header">
                        <h3 style="color: #2563eb; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-sync-alt"></i> Generate Tagihan UKT Massal
                        </h3>
                        <button class="modal-close" @click="showGenerateModal = false"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="modal-body" style="padding: 1.25rem 1.5rem;">
                        <p style="margin: 0 0 1rem; color: #334155; font-size: 0.875rem; line-height: 1.5;">
                            Sistem akan mengecek seluruh <strong>Mahasiswa Aktif</strong> untuk periode <strong>{{ activeTa?.nama }} ({{ activeTa?.semester }})</strong> dan membuatkan tagihan UKT berdasarkan konfigurasi tarif biaya & beasiswa yang berlaku.
                        </p>
                        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:0.5rem;padding:0.75rem 1rem;font-size:0.8125rem;color:#1e40af;">
                            <i class="fas fa-shield-alt"></i> <strong>Aman:</strong> Mahasiswa yang sudah memiliki tagihan di semester ini tidak akan diduplikasi.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="m-btn m-btn-secondary" @click="showGenerateModal = false" :disabled="isGenerating">Batal</button>
                        <button type="button" class="m-btn m-btn-primary" @click="executeGenerateTagihan" :disabled="isGenerating">
                            <i class="fas" :class="isGenerating ? 'fa-spinner fa-pulse' : 'fa-play'"></i>
                            {{ isGenerating ? 'Sedang Memproses...' : 'Mulai Generate' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal 4: Konfirmasi Hapus Master -->
            <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
                <div class="modal-box" style="max-width: 440px;">
                    <div class="modal-header">
                        <h3 style="color: #991b1b; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-trash-alt"></i> Konfirmasi Hapus
                        </h3>
                        <button class="modal-close" @click="showDeleteModal = false"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="modal-body" style="padding: 1.25rem 1.5rem;">
                        <p style="margin: 0; color: #475569; font-size: 0.875rem; line-height: 1.5;">
                            Apakah Anda yakin ingin menghapus tahun akademik <strong>{{ itemToDelete?.nama }} ({{ itemToDelete?.semester }})</strong> dari master data?
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="m-btn m-btn-secondary" @click="showDeleteModal = false">Batal</button>
                        <button type="button" class="m-btn m-btn-danger" @click="executeDelete">
                            <i class="fas fa-trash"></i> Ya, Hapus
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal 5: Blokir Hapus Master (Proteksi Data Tagihan / Status Aktif) -->
            <div v-if="showBlockedDeleteModal" class="modal-overlay" @click.self="showBlockedDeleteModal = false">
                <div class="modal-box" style="max-width: 460px;">
                    <div class="modal-header">
                        <h3 style="color: #dc2626; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-ban"></i> Tidak Dapat Dihapus
                        </h3>
                        <button class="modal-close" @click="showBlockedDeleteModal = false"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="modal-body" style="padding: 1.25rem 1.5rem;">
                        <div v-if="itemToDelete?.is_aktif" style="margin-bottom:0.75rem;color:#334155;font-size:0.875rem;">
                            Tahun akademik <strong>{{ itemToDelete?.nama }}</strong> saat ini berstatus <strong>AKTIF</strong>. Anda harus mengaktifkan tahun akademik lain terlebih dahulu sebelum dapat menghapusnya.
                        </div>
                        <div v-else style="margin-bottom:0.75rem;color:#334155;font-size:0.875rem;">
                            Tahun akademik <strong>{{ itemToDelete?.nama }}</strong> memiliki <strong>{{ formatRupiah(itemToDelete?.tagihan_count) }} data tagihan mahasiswa</strong> yang terhubung. Menghapus periode ini akan merusak riwayat transaksi dan audit pembayaran mahasiswa.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="m-btn m-btn-primary" @click="showBlockedDeleteModal = false">Mengerti</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.page-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    letter-spacing: -0.02em;
}
.page-subtitle {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0.25rem 0 0;
}

/* Tab Switcher */
.tab-switcher {
    display: flex;
    background: #e2e8f0;
    padding: 3px;
    border-radius: 0.75rem;
    gap: 2px;
}
.tab-switch-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
    background: transparent;
    border: none;
    border-radius: 0.625rem;
    cursor: pointer;
    transition: all 0.15s ease;
}
.tab-switch-btn:hover {
    color: #0f172a;
}
.tab-switch-btn.active {
    background: #ffffff;
    color: #0f172a;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.tab-counter {
    background: #cbd5e1;
    color: #334155;
    font-size: 0.6875rem;
    padding: 0.125rem 0.375rem;
    border-radius: 9999px;
}
.tab-switch-btn.active .tab-counter {
    background: #eff6ff;
    color: #2563eb;
}

/* Hero Card */
.solid-hero-card {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.25rem;
}
.hero-badge-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.6875rem;
    font-weight: 700;
    color: #15803d;
    background: #dcfce7;
    padding: 0.25rem 0.625rem;
    border-radius: 9999px;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}
.live-dot {
    width: 6px;
    height: 6px;
    background: #16a34a;
    border-radius: 50%;
}
.hero-period-title {
    font-size: 1.375rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 0.5rem;
    letter-spacing: -0.01em;
}
.hero-meta-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    font-size: 0.875rem;
    color: #475569;
}
.hero-meta-item {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.hero-right {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    flex-wrap: wrap;
}

/* Solid Pills */
.solid-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.625rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}
.pill-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.pill-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.pill-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

/* Metrics Grid */
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.metric-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.metric-card-body {
    padding: 1.25rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}
.metric-icon-wrap {
    width: 44px;
    height: 44px;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}
.metric-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin-bottom: 0.25rem;
}
.metric-value {
    font-size: 1.375rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
}
.metric-sub {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 0.25rem;
}
.metric-progress-bar {
    height: 4px;
    background: #e2e8f0;
    width: 100%;
}
.metric-progress-fill {
    height: 100%;
    background: #16a34a;
    transition: width 0.3s ease;
}

/* Guide Box */
.guide-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1.25rem 1.5rem;
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}
.guide-icon {
    font-size: 1.25rem;
    color: #2563eb;
    margin-top: 0.125rem;
}
.guide-title {
    font-size: 0.9375rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.25rem;
}
.guide-desc {
    font-size: 0.8125rem;
    color: #475569;
    line-height: 1.5;
    margin: 0;
}

/* Split Layout */
.layout-split {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 340px;
    gap: 1.5rem;
    align-items: start;
}
.panel-table { min-width: 0; }
.custom-card {
    overflow: hidden;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    background: #ffffff;
}
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
    background: #f8fafc;
}
.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.table-responsive .m-data-table { min-width: 600px; }
.action-btns { display: flex; gap: 0.375rem; }

/* Badges */
.m-badge-info { background: #dbeafe; color: #1e40af; }
.m-badge-purple { background: #ede9fe; color: #7c3aed; }
.tagihan-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    background: #eff6ff;
    color: #1d4ed8;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}
.tagihan-empty {
    font-size: 0.75rem;
    color: #94a3b8;
}

/* Status Pills */
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.625rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 700;
}
.status-active { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.status-inactive { background: #f1f5f9; color: #64748b; }
.status-dot { width: 6px; height: 6px; border-radius: 50%; background: #16a34a; }

.row-current-active { background: #f0fdf4 !important; }
.row-editing { background: #eff6ff !important; }

/* Forms */
.form-group { margin-bottom: 1rem; }
.form-label { display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.375rem; }
.form-control {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    transition: border-color 0.2s, box-shadow 0.2s;
    background: #ffffff;
}
.form-control:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }
.form-error { font-size: 0.75rem; color: #dc2626; margin-top: 0.25rem; }
.form-actions { display: flex; gap: 0.5rem; }

/* Modals */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
    padding: 1rem;
}
.modal-box {
    background: #ffffff;
    border-radius: 1rem;
    width: 100%;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}
.modal-header h3 { margin: 0; font-size: 1.125rem; font-weight: 700; }
.modal-close { background: none; border: none; font-size: 1.125rem; color: #64748b; cursor: pointer; padding: 0.25rem; }
.modal-close:hover { color: #0f172a; }
.modal-body { padding: 1.5rem; }
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
    border-bottom-left-radius: 1rem;
    border-bottom-right-radius: 1rem;
}

.fade-in-section {
    animation: fadeIn 0.15s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(3px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 900px) {
    .layout-split { grid-template-columns: 1fr; }
    .hero-right { width: 100%; justify-content: flex-start; }
}
@media (max-width: 640px) {
    .page-header { flex-direction: column; align-items: stretch; }
    .tab-switcher { width: 100%; justify-content: stretch; }
    .tab-switch-btn { flex: 1; justify-content: center; }
}
</style>
