<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    banks: Array,
});

const showModal = ref(false);
const editMode = ref(false);
const editId = ref(null);
const logoInput = ref(null);
const logoPreview = ref('');

const deleteModal = ref(null);
const toggleModal = ref(null);

const form = useForm({
    nama_metode: '',
    kode: '',
    logo: '',
    no_rekening: '',
    instruksi: '',
    kategori: 'rekening_universitas',
    status_aktif: true,
});

const openCreate = () => {
    editMode.value = false;
    editId.value = null;
    form.reset();
    form.status_aktif = true;
    form.kategori = 'rekening_universitas';
    logoPreview.value = '';
    showModal.value = true;
};

const openEdit = (bank) => {
    editMode.value = true;
    editId.value = bank.id;
    form.nama_metode = bank.nama_metode;
    form.kode = bank.kode;
    form.logo = bank.logo || '';
    form.no_rekening = bank.no_rekening || '';
    form.instruksi = bank.instruksi || '';
    form.kategori = bank.kategori || 'rekening_universitas';
    form.status_aktif = Boolean(bank.status_aktif);
    logoPreview.value = bank.logo || '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.clearErrors();
    form.reset();
    logoPreview.value = '';
};

const handleLogoChange = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    form.logo = file;
    logoPreview.value = URL.createObjectURL(file);
};

const handleLogoError = (event) => {
    event.target.style.display = 'none';
    const fallback = event.target.nextElementSibling;
    if (fallback) fallback.style.display = 'block';
};

const submit = () => {
    if (editMode.value) {
        form.put(route('admin.bank.update', editId.value), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                closeModal();
            },
        });
    } else {
        form.post(route('admin.bank.store'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                closeModal();
            },
        });
    }
};

const openDelete = (bank) => {
    deleteModal.value = bank;
};

const executeDelete = () => {
    if (!deleteModal.value) return;
    router.delete(route('admin.bank.destroy', deleteModal.value.id), {
        preserveScroll: true,
        onFinish: () => {
            deleteModal.value = null;
        },
    });
};

const openToggle = (bank) => {
    toggleModal.value = bank;
};

const executeToggle = () => {
    if (!toggleModal.value) return;
    router.post(route('admin.bank.toggle', toggleModal.value.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            toggleModal.value = null;
        },
    });
};

const bankColors = {
    'BNI': '#003399',
    'BTN': '#006633',
    'Mandiri': '#0033A0',
    'BRI': '#008C4A',
    'BCA': '#003399',
};

const getBankColor = (nama) => {
    const key = Object.keys(bankColors).find(k => nama.toLowerCase().includes(k.toLowerCase()));
    return key ? bankColors[key] : '#4f46e5';
};

const hasTrx = (bank) => (bank.pembayarans_count || 0) > 0;
</script>

<template>
    <Head title="Data Bank" />
    <AuthenticatedLayout>
        <template #header>
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div>
                    <h2 class="page-heading" style="font-size:1.375rem;font-weight:700;color:#0f172a;margin-bottom:0.25rem;">Metode & Bank Pembayaran</h2>
                    <p style="font-size:0.875rem;color:#64748b;margin:0;">Kelola kanal pembayaran Virtual Account dan Rekening Universitas</p>
                </div>
                <button class="solid-btn btn-indigo-solid" @click="openCreate">
                    <i class="fas fa-plus"></i> Tambah Bank / Metode
                </button>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">
                <div class="bank-grid">
                    <div
                        v-for="bank in banks"
                        :key="bank.id"
                        class="bank-card"
                        :class="{ 'bank-card-inactive': !bank.status_aktif }"
                    >
                        <!-- Top Bar -->
                        <div class="bank-card-header">
                            <div class="bank-logo-box" :style="{ background: getBankColor(bank.nama_metode) }">
                                <img v-if="bank.logo" :src="bank.logo" :alt="bank.nama_metode" class="bank-logo-img" @error="handleLogoError" />
                                <span v-else class="bank-logo-fallback">{{ bank.kode }}</span>
                            </div>

                            <div style="display:flex;align-items:center;gap:0.375rem;">
                                <span v-if="bank.status_aktif" class="badge-status badge-status-active">
                                    <i class="fas fa-check-circle"></i> Aktif
                                </span>
                                <span v-else class="badge-status badge-status-inactive">
                                    <i class="fas fa-times-circle"></i> Nonaktif
                                </span>
                            </div>
                        </div>

                        <!-- Bank Info -->
                        <div class="bank-card-body">
                            <h4 class="bank-title">{{ bank.nama_metode }}</h4>
                            <div class="bank-tag-wrap">
                                <span :class="['kategori-badge', bank.kategori === 'virtual_account' ? 'badge-va' : 'badge-ru']">
                                    {{ bank.kategori === 'virtual_account' ? 'Virtual Account' : 'Rekening Universitas' }}
                                </span>
                            </div>

                            <div v-if="bank.no_rekening" class="bank-rek-box">
                                <span style="font-size:0.75rem;color:#64748b;">No. Rek:</span>
                                <strong style="font-family:monospace;font-size:0.875rem;color:#0f172a;">{{ bank.no_rekening }}</strong>
                            </div>

                            <div style="font-size:0.75rem;color:#64748b;margin-top:0.5rem;">
                                <i class="fas fa-receipt" style="color:#94a3b8;margin-right:0.25rem;"></i>
                                Digunakan pada <strong style="color:#0f172a;">{{ bank.pembayarans_count || 0 }}</strong> transaksi
                            </div>
                        </div>

                        <!-- Action Footer -->
                        <div class="bank-card-footer">
                            <button
                                class="btn-toggle"
                                :class="bank.status_aktif ? 'btn-toggle-active' : 'btn-toggle-inactive'"
                                @click="openToggle(bank)"
                                :title="bank.status_aktif ? 'Nonaktifkan Bank' : 'Aktifkan Bank'"
                            >
                                <i :class="bank.status_aktif ? 'fas fa-toggle-on' : 'fas fa-toggle-off'"></i>
                                <span>{{ bank.status_aktif ? 'Aktif' : 'Off' }}</span>
                            </button>

                            <div style="display:flex;gap:0.375rem;">
                                <button class="action-btn action-btn-edit" @click="openEdit(bank)" title="Edit Konfigurasi">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button
                                    class="action-btn action-btn-delete"
                                    @click="openDelete(bank)"
                                    :disabled="hasTrx(bank)"
                                    :title="hasTrx(bank) ? 'Tidak dapat dihapus karena telah memiliki riwayat transaksi' : 'Hapus Bank'"
                                    :style="hasTrx(bank) ? 'opacity:0.3;cursor:not-allowed;' : ''"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="!banks || banks.length === 0" style="text-align:center;padding:4rem 2rem;background:#fff;border-radius:0.75rem;border:1px solid #e2e8f0;color:#64748b;">
                    <i class="fas fa-university" style="font-size:2.5rem;color:#cbd5e1;margin-bottom:0.75rem;display:block;"></i>
                    <h4 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 0.375rem;">Belum Ada Data Bank</h4>
                    <p style="font-size:0.8125rem;color:#64748b;margin:0 0 1rem;">Tambahkan rekening bank atau metode pembayaran untuk mahasiswa.</p>
                    <button class="solid-btn btn-indigo-solid" @click="openCreate">
                        <i class="fas fa-plus"></i> Tambah Bank Pertama
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Form Tambah/Edit Bank -->
        <Teleport to="body">
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                <div class="modal-box">
                    <div class="modal-header">
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <span style="width:36px;height:36px;border-radius:0.5rem;background:#e0e7ff;color:#4338ca;display:flex;align-items:center;justify-content:center;font-size:1.125rem;">
                                <i class="fas fa-university"></i>
                            </span>
                            <div>
                                <h4 style="margin:0;font-size:1rem;font-weight:700;color:#0f172a;">{{ editMode ? 'Edit Bank / Metode' : 'Tambah Bank / Metode' }}</h4>
                                <p style="margin:0;font-size:0.75rem;color:#64748b;">Konfigurasi rekening dan instruksi bayar</p>
                            </div>
                        </div>
                        <button class="modal-close" @click="closeModal">&times;</button>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="modal-body">
                            <div class="m-form-group">
                                <label class="m-form-label">Nama Bank / Metode <span style="color:#dc2626;">*</span></label>
                                <input v-model="form.nama_metode" type="text" class="m-form-input" placeholder="Contoh: Bank BRI" required />
                                <div v-if="form.errors.nama_metode" class="m-form-error">{{ form.errors.nama_metode }}</div>
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                                <div class="m-form-group">
                                    <label class="m-form-label">Kode Singkat <span style="color:#dc2626;">*</span></label>
                                    <input v-model="form.kode" type="text" class="m-form-input" placeholder="Contoh: BRI" maxlength="10" required />
                                    <div v-if="form.errors.kode" class="m-form-error">{{ form.errors.kode }}</div>
                                </div>
                                <div class="m-form-group">
                                    <label class="m-form-label">Kategori <span style="color:#dc2626;">*</span></label>
                                    <select v-model="form.kategori" class="m-form-input" required>
                                        <option value="rekening_universitas">Rekening Universitas</option>
                                        <option value="virtual_account">Virtual Account</option>
                                    </select>
                                </div>
                            </div>

                            <div class="m-form-group">
                                <label class="m-form-label">Nomor Rekening (jika transfer manual)</label>
                                <input v-model="form.no_rekening" type="text" class="m-form-input" placeholder="Contoh: 1234-5678-9012" maxlength="50" />
                            </div>

                            <div class="m-form-group">
                                <label class="m-form-label">Instruksi Pembayaran</label>
                                <textarea
                                    v-model="form.instruksi"
                                    class="m-form-input"
                                    rows="3"
                                    placeholder="Tuliskan petunjuk transfer bagi mahasiswa..."
                                ></textarea>
                                <div v-if="form.errors.instruksi" class="m-form-error">{{ form.errors.instruksi }}</div>
                            </div>

                            <div class="m-form-group">
                                <label class="m-form-label">Logo Bank (Opsional)</label>
                                <div class="logo-box-picker" @click="logoInput?.click()">
                                    <input
                                        ref="logoInput"
                                        type="file"
                                        accept="image/*"
                                        style="display:none;"
                                        @change="handleLogoChange"
                                    />
                                    <img v-if="logoPreview" :src="logoPreview" alt="Preview Logo" class="logo-preview-img" />
                                    <div v-else style="text-align:center;color:#64748b;">
                                        <i class="fas fa-image" style="font-size:1.5rem;color:#94a3b8;margin-bottom:0.25rem;display:block;"></i>
                                        <span style="font-size:0.75rem;">Klik untuk memilih file logo</span>
                                    </div>
                                </div>
                                <div v-if="form.errors.logo" class="m-form-error">{{ form.errors.logo }}</div>
                            </div>

                            <div style="display:flex;align-items:center;gap:0.5rem;margin-top:0.75rem;">
                                <input type="checkbox" id="statusAktifCheck" v-model="form.status_aktif" style="width:16px;height:16px;accent-color:#4f46e5;" />
                                <label for="statusAktifCheck" style="font-size:0.8125rem;font-weight:600;color:#1e293b;cursor:pointer;">
                                    Aktifkan metode pembayaran ini untuk mahasiswa
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="solid-btn btn-white-border" @click="closeModal">Batal</button>
                            <button type="submit" class="solid-btn btn-indigo-solid" :disabled="form.processing">
                                {{ form.processing ? 'Menyimpan...' : (editMode ? 'Simpan Perubahan' : 'Tambah Bank') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Modal Toggle Status -->
        <div v-if="toggleModal" class="modal-overlay" @click.self="toggleModal = null">
            <div class="modal-box">
                <div class="modal-header">
                    <h4 style="margin:0;font-size:1rem;font-weight:700;color:#0f172a;">
                        {{ toggleModal.status_aktif ? 'Nonaktifkan Metode' : 'Aktifkan Metode' }}
                    </h4>
                    <button class="modal-close" @click="toggleModal = null">&times;</button>
                </div>
                <div class="modal-body">
                    <p style="font-size:0.875rem;color:#334155;margin:0 0 0.75rem;">
                        Apakah Anda yakin ingin <strong>{{ toggleModal.status_aktif ? 'menonaktifkan' : 'mengaktifkan' }}</strong> kanal pembayaran <strong>{{ toggleModal.nama_metode }}</strong>?
                    </p>
                    <p v-if="toggleModal.status_aktif" style="font-size:0.75rem;color:#dc2626;margin:0;">
                        Mahasiswa tidak akan dapat memilih metode ini di portal pembayaran selama statusnya nonaktif.
                    </p>
                </div>
                <div class="modal-footer">
                    <button class="solid-btn btn-white-border" @click="toggleModal = null">Batal</button>
                    <button
                        class="solid-btn"
                        :class="toggleModal.status_aktif ? 'btn-red-solid' : 'btn-green-solid'"
                        @click="executeToggle"
                    >
                        {{ toggleModal.status_aktif ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Delete -->
        <div v-if="deleteModal" class="modal-overlay" @click.self="deleteModal = null">
            <div class="modal-box">
                <div class="modal-header">
                    <h4 style="margin:0;font-size:1rem;font-weight:700;color:#0f172a;">Hapus Metode Pembayaran</h4>
                    <button class="modal-close" @click="deleteModal = null">&times;</button>
                </div>
                <div class="modal-body">
                    <p style="font-size:0.875rem;color:#334155;margin:0 0 0.75rem;">
                        Apakah Anda yakin ingin menghapus data bank <strong>{{ deleteModal.nama_metode }}</strong>?
                    </p>
                    <p style="font-size:0.75rem;color:#dc2626;margin:0;">
                        Tindakan ini permanen.
                    </p>
                </div>
                <div class="modal-footer">
                    <button class="solid-btn btn-white-border" @click="deleteModal = null">Batal</button>
                    <button class="solid-btn btn-red-solid" @click="executeDelete">
                        <i class="fas fa-trash"></i> Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.bank-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.25rem;
}
.bank-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: box-shadow 0.15s, border-color 0.15s;
}
.bank-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.bank-card-inactive {
    background: #f8fafc;
    border-color: #e2e8f0;
}

.bank-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.875rem;
}
.bank-logo-box {
    width: 44px;
    height: 44px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-weight: 700;
    font-size: 0.875rem;
    overflow: hidden;
}
.bank-logo-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: #ffffff;
}

.badge-status {
    font-size: 0.6875rem;
    font-weight: 600;
    padding: 0.2rem 0.5rem;
    border-radius: 0.375rem;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}
.badge-status-active {
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}
.badge-status-inactive {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.bank-card-body {
    flex: 1;
    margin-bottom: 1rem;
}
.bank-title {
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.375rem;
}
.bank-tag-wrap {
    margin-bottom: 0.5rem;
}
.kategori-badge {
    font-size: 0.6875rem;
    font-weight: 600;
    padding: 0.15rem 0.45rem;
    border-radius: 0.375rem;
    display: inline-block;
}
.badge-va {
    background: #f3e8ff;
    color: #6b21a8;
    border: 1px solid #e9d5ff;
}
.badge-ru {
    background: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
}

.bank-rek-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    padding: 0.375rem 0.625rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.5rem;
}

.bank-card-footer {
    padding-top: 0.875rem;
    border-top: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.btn-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.3rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid transparent;
}
.btn-toggle-active {
    background: #ecfdf5;
    color: #065f46;
    border-color: #a7f3d0;
}
.btn-toggle-inactive {
    background: #f1f5f9;
    color: #64748b;
    border-color: #e2e8f0;
}

.action-btn {
    width: 28px;
    height: 28px;
    border-radius: 0.375rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    cursor: pointer;
}
.action-btn-edit {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #cbd5e1;
}
.action-btn-edit:hover {
    background: #e2e8f0;
}
.action-btn-delete {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}
.action-btn-delete:hover {
    background: #fee2e2;
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
    transition: opacity 0.15s;
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
.m-form-group {
    margin-bottom: 0.875rem;
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
    background: #ffffff;
}
.m-form-input:focus {
    border-color: #4f46e5;
}
.m-form-error {
    font-size: 0.75rem;
    color: #dc2626;
    margin-top: 0.25rem;
}
.logo-box-picker {
    border: 1.5px dashed #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    background: #f8fafc;
}
.logo-box-picker:hover {
    border-color: #4f46e5;
}
.logo-preview-img {
    max-height: 60px;
    object-fit: contain;
}
</style>

