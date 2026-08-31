<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    banks: Array,
});

const showModal = ref(false);
const editMode = ref(false);
const editId = ref(null);
const logoInput = ref(null);
const logoPreview = ref('');

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
    form.logo = bank.logo || '';
    form.kategori = bank.kategori || 'rekening_universitas';
    form.status_aktif = bank.status_aktif;
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

// Fallback to the bank code initial when the logo URL fails to load.
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

const deleteBank = (bank) => {
    if (confirm(`Yakin ingin menghapus "${bank.nama_metode}"?`)) {
        router.delete(route('admin.bank.destroy', bank.id), {
            preserveScroll: true,
        });
    }
};

const toggleStatus = (bank) => {
    if (confirm(`Yakin ingin ${bank.status_aktif ? 'menonaktifkan' : 'mengaktifkan'} "${bank.nama_metode}"?`)) {
        router.post(route('admin.bank.toggle', bank.id), {}, { preserveScroll: true });
    }
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
    return key ? bankColors[key] : '#6b7280';
};

const getBankCode = (nama) => {
    if (nama.toLowerCase().includes('bri')) return 'BRI';
    if (nama.toLowerCase().includes('bni')) return 'BNI';
    if (nama.toLowerCase().includes('mandiri')) return 'MDR';
    if (nama.toLowerCase().includes('bca')) return 'BCA';
    if (nama.toLowerCase().includes('btn')) return 'BTN';
    if (nama.toLowerCase().includes('virtual') || nama.toLowerCase().includes('va')) return 'VIR';
    return nama.substring(0, 3).toUpperCase();
};

const hasTrx = (bank) => bank.pembayarans_count > 0;
</script>

<template>
    <Head title="Data Bank" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="page-heading">Data Bank Pembayaran</h2>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <div class="custom-card">
                    <div class="card-header">
                        <h3 class="card-title">Daftar Bank</h3>
                        <button class="btn btn-primary" @click="openCreate">
                            <i class="fas fa-plus"></i> Tambah Bank
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="bank-admin-grid">
                            <div v-for="bank in banks" :key="bank.id" class="bank-admin-card" :class="{ inactive: !bank.status_aktif }">
                                <div class="bank-admin-top">
                                    <div class="bank-admin-logo" :style="{ background: getBankColor(bank.nama_metode) }">
                                        <img v-if="bank.logo" :src="bank.logo" alt="Logo {{ bank.nama_metode }}" class="bank-admin-logo-img" @error="handleLogoError" />
                                        <span v-else class="bank-admin-logo-text">{{ bank.kode }}</span>
                                    </div>
                                    <div class="bank-admin-check" v-if="bank.status_aktif">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="bank-admin-cross" v-else>
                                        <i class="fas fa-times-circle"></i>
                                    </div>
                                </div>
                                <div class="bank-admin-name">{{ bank.nama_metode }}</div>
                                <div class="bank-admin-kategori">
                                    <span class="kategori-tag" :class="bank.kategori === 'virtual_account' ? 'va' : 'ru'">
                                        {{ bank.kategori === 'virtual_account' ? 'Virtual Account' : 'Rekening Universitas' }}
                                    </span>
                                </div>
                                <div class="bank-admin-rek" v-if="bank.no_rekening">
                                    <i class="fas fa-hashtag" style="font-size: 0.625rem;"></i> {{ bank.no_rekening }}
                                </div>
                                <div class="bank-admin-count">{{ bank.pembayarans_count }} transaksi</div>

                                <div class="bank-admin-actions">
                                    <button
                                        class="bank-admin-toggle"
                                        :class="bank.status_aktif ? 'active' : 'inactive'"
                                        @click="toggleStatus(bank)"
                                        :title="bank.status_aktif ? 'Nonaktifkan' : 'Aktifkan'"
                                    >
                                        <i :class="bank.status_aktif ? 'fas fa-toggle-on' : 'fas fa-toggle-off'"></i>
                                    </button>
                                    <button class="bank-admin-btn edit" @click="openEdit(bank)" title="Edit">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button
                                        class="bank-admin-btn delete"
                                        @click="deleteBank(bank)"
                                        title="Hapus"
                                        :disabled="hasTrx(bank)"
                                        :style="hasTrx(bank) ? 'opacity:0.3;cursor:not-allowed;' : ''"
                                    >
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="!banks || banks.length === 0" class="text-center py-4" style="color: var(--gray-600);">
                            Belum ada data bank
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Form -->
        <Teleport to="body">
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                <div class="modal-box">
                    <div class="modal-header">
                        <h3>{{ editMode ? 'Edit Bank' : 'Tambah Bank' }}</h3>
                        <button class="modal-close" @click="closeModal"><i class="fas fa-times"></i></button>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="modal-body">
                            <div class="form-group">
                                <label class="form-label">Nama Bank <span style="color:var(--danger);">*</span></label>
                                <input v-model="form.nama_metode" type="text" class="form-control" placeholder="Contoh: Bank BRI" required />
                                <div v-if="form.errors.nama_metode" class="form-error">{{ form.errors.nama_metode }}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Kode <span style="color:var(--danger);">*</span></label>
                                <input v-model="form.kode" type="text" class="form-control" placeholder="Contoh: BRI" maxlength="10" required />
                                <div v-if="form.errors.kode" class="form-error">{{ form.errors.kode }}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Kategori <span style="color:var(--danger);">*</span></label>
                                <select v-model="form.kategori" class="form-control" required>
                                    <option value="rekening_universitas">Rekening Universitas</option>
                                    <option value="virtual_account">Virtual Account</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">No. Rekening</label>
                                <input v-model="form.no_rekening" type="text" class="form-control" placeholder="Contoh: 1234567890" maxlength="50" />
                            </div>

                            <div class="form-group">
                                <label class="form-label">Instruksi Pembayaran</label>
                                <textarea
                                    v-model="form.instruksi"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Contoh: Pembayaran dapat dilakukan melalui ATM, Mobile Banking, atau Internet Banking menggunakan nomor Virtual Account."
                                ></textarea>
                                <div v-if="form.errors.instruksi" class="form-error">{{ form.errors.instruksi }}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Logo (opsional)</label>
                                <div class="logo-upload-area" @click="logoInput?.click()">
                                    <input
                                        ref="logoInput"
                                        type="file"
                                        accept="image/*"
                                        class="logo-file-input"
                                        @change="handleLogoChange"
                                    />
                                    <img v-if="logoPreview" :src="logoPreview" alt="Preview Logo" class="logo-preview" />
                                    <div v-else class="logo-upload-placeholder">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <span>Klik untuk pilih foto logo</span>
                                    </div>
                                </div>
                                <div v-if="form.errors.logo" class="form-error">{{ form.errors.logo }}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <label class="toggle-label">
                                    <input type="checkbox" v-model="form.status_aktif" />
                                    <span>Aktif</span>
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="closeModal">Batal</button>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                {{ form.processing ? 'Menyimpan...' : (editMode ? 'Simpan Perubahan' : 'Tambah') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.bank-admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1rem;
}

.bank-admin-card {
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: 0.75rem;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.2s;
}

.bank-admin-card:hover {
    border-color: var(--gray-300);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.bank-admin-card.inactive {
    opacity: 0.55;
    background: var(--gray-50);
}

.bank-admin-top {
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    margin-bottom: 0.75rem;
}

.bank-admin-logo {
    width: 56px;
    height: 56px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.125rem;
    font-weight: 700;
    color: white;
    overflow: hidden;
}

.bank-admin-logo-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: white;
}

.bank-admin-logo-text {
    display: block;
}

.bank-admin-check {
    position: absolute;
    top: -4px;
    right: calc(50% - 36px);
    color: var(--success);
    font-size: 1.125rem;
    background: white;
    border-radius: 50%;
    width: 1.25rem;
    height: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bank-admin-cross {
    position: absolute;
    top: -4px;
    right: calc(50% - 36px);
    color: var(--danger);
    font-size: 1.125rem;
    background: white;
    border-radius: 50%;
    width: 1.25rem;
    height: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bank-admin-name {
    font-weight: 600;
    font-size: 0.9375rem;
    color: var(--gray-800);
    margin-bottom: 0.25rem;
}

.bank-admin-kategori {
    margin-bottom: 0.25rem;
}

.kategori-tag {
    display: inline-block;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.6875rem;
    font-weight: 600;
}

.kategori-tag.ru {
    background: #dbeafe;
    color: #1e40af;
}

.kategori-tag.va {
    background: #f3e8ff;
    color: #7c3aed;
}

.bank-admin-rek {
    font-size: 0.75rem;
    color: var(--gray-600);
    margin-bottom: 0.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    font-family: monospace;
    letter-spacing: 0.5px;
}

.bank-admin-count {
    font-size: 0.75rem;
    color: var(--gray-600);
    margin-bottom: 0.75rem;
}

.bank-admin-actions {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
}

.bank-admin-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 0.5rem;
    font-size: 1.125rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.bank-admin-toggle.active {
    background: #d1fae5;
    color: #065f46;
}

.bank-admin-toggle.inactive {
    background: #fee2e2;
    color: #991b1b;
}

.bank-admin-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.bank-admin-btn.edit {
    background: var(--gray-100);
    color: var(--gray-700);
}

.bank-admin-btn.edit:hover {
    background: var(--gray-200);
}

.bank-admin-btn.delete {
    background: var(--gray-100);
    color: var(--danger);
}

.bank-admin-btn.delete:hover {
    background: #fee2e2;
}

/* Modal */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
    padding: 1rem;
}

.modal-box {
    background: white;
    border-radius: 1rem;
    width: 100%;
    max-width: 480px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
}

.modal-header h3 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--gray-900);
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.125rem;
    color: var(--gray-500);
    cursor: pointer;
    padding: 0.25rem;
}

.modal-close:hover {
    color: var(--gray-800);
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--gray-200);
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 0.375rem;
}

.form-control {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.75rem;
    font-size: 0.875rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
}

.logo-upload-area {
    border: 2px dashed var(--gray-300);
    border-radius: 0.75rem;
    padding: 1rem;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
}

.logo-upload-area:hover {
    border-color: var(--primary);
    background: var(--gray-50);
}

.logo-file-input {
    display: none;
}

.logo-preview {
    max-width: 100%;
    max-height: 96px;
    object-fit: contain;
    border-radius: 0.5rem;
    border: 1px solid var(--gray-200);
    background: white;
}

.logo-upload-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.375rem;
    color: var(--gray-500);
    font-size: 0.8125rem;
    padding: 0.5rem;
}

.logo-upload-placeholder i {
    font-size: 1.5rem;
}

.form-error {
    font-size: 0.75rem;
    color: var(--danger);
    margin-top: 0.25rem;
}

.toggle-label {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.875rem;
}

.toggle-label input[type="checkbox"] {
    width: 1rem;
    height: 1rem;
    accent-color: #4f46e5;
}

.alert {
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border-left: 3px solid #10b981;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border-left: 3px solid #ef4444;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-weight: 600;
    font-size: 0.875rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: #4f46e5;
    color: white;
}

.btn-primary:hover {
    background: #4338ca;
}

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-secondary {
    background: var(--gray-100);
    color: var(--gray-700);
}

.btn-secondary:hover {
    background: var(--gray-200);
}

@media (max-width: 768px) {
    .bank-admin-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
