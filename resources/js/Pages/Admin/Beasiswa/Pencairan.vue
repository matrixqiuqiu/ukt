<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { formatRupiah, formatDate } from '@/utils';

const props = defineProps({
    beasiswa: Object,
    pencairans: Array,
});

const form = ref({
    termin_ke: (props.pencairans.length + 1),
    nominal_dijanjikan: 0,
    tanggal_janji_cair: '',
    jatuh_tempo_external: '',
    keterangan: '',
});

const editMode = ref(false);
const editId = ref(null);
const showDeleteModal = ref(false);
const itemToDelete = ref(null);

const parseCurrency = (val) => Number(String(val).replace(/[^0-9]/g, '')) || 0;
const onNominalInput = (e) => {
    form.value.nominal_dijanjikan = parseCurrency(e.target.value);
};
const onKonfirmasiNominalInput = (e) => {
    konfirmasiForm.value.nominal_cair = parseCurrency(e.target.value);
};

const submit = () => {
    if (editMode.value) {
        router.put(route('admin.beasiswa.pencairan.update', editId.value), form.value, {
            preserveScroll: true,
            onSuccess: () => {
                editMode.value = false;
                editId.value = null;
                form.value = {
                    termin_ke: props.pencairans.length + 1,
                    nominal_dijanjikan: 0,
                    tanggal_janji_cair: '',
                    jatuh_tempo_external: '',
                    keterangan: '',
                };
            }
        });
    } else {
        router.post(route('admin.beasiswa.pencairan.store', props.beasiswa.id), form.value, {
            preserveScroll: true,
            onSuccess: () => {
                form.value = {
                    termin_ke: props.pencairans.length + 2,
                    nominal_dijanjikan: 0,
                    tanggal_janji_cair: '',
                    jatuh_tempo_external: '',
                    keterangan: '',
                };
            }
        });
    }
};

const openEdit = (row) => {
    editMode.value = true;
    editId.value = row.id;
    form.value = {
        termin_ke: row.termin_ke,
        nominal_dijanjikan: row.nominal_dijanjikan,
        tanggal_janji_cair: row.tanggal_janji_cair || '',
        jatuh_tempo_external: row.jatuh_tempo_external || '',
        keterangan: row.keterangan || '',
    };
};

const cancelEdit = () => {
    editMode.value = false;
    editId.value = null;
    form.value = {
        termin_ke: props.pencairans.length + 1,
        nominal_dijanjikan: 0,
        tanggal_janji_cair: '',
        jatuh_tempo_external: '',
        keterangan: '',
    };
};

const confirmDestroy = (row) => {
    itemToDelete.value = row;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (!itemToDelete.value) return;
    router.delete(route('admin.beasiswa.pencairan.destroy', itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            itemToDelete.value = null;
        }
    });
};

const konfirmasiForm = ref({
    nominal_cair: 0,
    tanggal_cair: '',
    bukti_cair: null,
});

const showKonfirmasi = ref(null);
const openKonfirmasi = (row) => {
    showKonfirmasi.value = row.id;
    konfirmasiForm.value = {
        nominal_cair: Math.max(0, row.nominal_dijanjikan - row.nominal_cair),
        tanggal_cair: new Date().toISOString().slice(0, 10),
        bukti_cair: null,
    };
};

const handleFile = (e) => {
    konfirmasiForm.value.bukti_cair = e.target.files[0];
};

const submitKonfirmasi = (row) => {
    router.post(route('admin.beasiswa.pencairan.konfirmasi', row.id), {
        ...konfirmasiForm.value,
        _method: 'post'
    }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showKonfirmasi.value = null;
        }
    });
};
</script>

<template>
    <Head :title="`Pencairan Dana Eksternal ${beasiswa.kode}`" />
    <AuthenticatedLayout>
        <template #header>
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div>
                    <h2 class="page-heading" style="font-size:1.375rem;font-weight:700;color:#0f172a;margin-bottom:0.25rem;">
                        Pencairan Dana Eksternal: {{ beasiswa.nama_beasiswa }}
                    </h2>
                    <p style="font-size:0.875rem;color:#64748b;margin:0;">
                        Kode: <strong style="font-family:monospace;color:#0f172a;">{{ beasiswa.kode }}</strong> &bull; Kelola termin penagihan dana beasiswa dari mitra eksternal
                    </p>
                </div>
                <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                    <Link :href="route('admin.beasiswa.assignments', beasiswa.id)" class="solid-btn btn-white-border">
                        <i class="fas fa-users"></i> Kelola Penerima
                    </Link>
                    <Link :href="route('admin.beasiswa.index')" class="solid-btn btn-white-border">
                        <i class="fas fa-arrow-left"></i> Kembali ke Beasiswa
                    </Link>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">
                <!-- Info Notice -->
                <div class="solid-notice solid-notice-info" style="margin-bottom:1.25rem;">
                    <i class="fas fa-info-circle" style="font-size:1.125rem;"></i>
                    <div>
                        Mahasiswa penerima beasiswa ini telah otomatis mendapatkan potongan tagihan UKT di portal mahasiswa.
                        Formulir termin di bawah ini digunakan untuk <strong>mencatat dan menagih komitmen dana dari pihak penyandang dana eksternal / mitra</strong>.
                    </div>
                </div>

                <!-- Form Card Tambah / Edit Termin -->
                <div class="filter-card" style="margin-bottom:1.25rem;">
                    <div style="font-size:0.875rem;font-weight:700;color:#1e293b;margin-bottom:0.875rem;display:flex;align-items:center;gap:0.5rem;">
                        <i class="fas fa-hand-holding-usd" style="color:#4f46e5;"></i>
                        <span>{{ editMode ? 'Edit Termin Penagihan' : 'Tambah Termin Penagihan Eksternal' }}</span>
                    </div>

                    <form @submit.prevent="submit" class="termin-form-grid">
                        <div>
                            <label class="form-label-solid">Termin Ke <span style="color:#dc2626;">*</span></label>
                            <input v-model.number="form.termin_ke" type="number" min="1" class="filter-input" required />
                        </div>

                        <div>
                            <label class="form-label-solid">Nominal Komitmen Dijanjikan <span style="color:#dc2626;">*</span></label>
                            <div class="currency-input-wrap">
                                <span class="curr-prefix">Rp</span>
                                <input
                                    :value="Number(form.nominal_dijanjikan || 0).toLocaleString('id-ID')"
                                    @input="onNominalInput"
                                    type="text"
                                    inputmode="numeric"
                                    class="filter-input curr-input"
                                    required
                                />
                            </div>
                        </div>

                        <div>
                            <label class="form-label-solid">Batas Jatuh Tempo Eksternal</label>
                            <input v-model="form.jatuh_tempo_external" type="date" class="filter-input" />
                        </div>

                        <div>
                            <label class="form-label-solid">Target Tanggal Janji Cair</label>
                            <input v-model="form.tanggal_janji_cair" type="date" class="filter-input" />
                        </div>

                        <div class="span-2-col">
                            <label class="form-label-solid">Keterangan / Berita Termin</label>
                            <input v-model="form.keterangan" class="filter-input" placeholder="Contoh: Termin 1 pencairan semester ganjil dari Pemprov" />
                        </div>

                        <div style="display:flex;gap:0.5rem;align-items:flex-end;">
                            <button type="submit" class="btn-solid-primary" style="padding:0.5625rem 1.125rem;">
                                <i class="fas" :class="editMode ? 'fa-save' : 'fa-plus'"></i> {{ editMode ? 'Simpan Perubahan' : 'Tambah Termin' }}
                            </button>
                            <button v-if="editMode" type="button" class="btn-solid-secondary" style="padding:0.5625rem 0.875rem;" @click="cancelEdit">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Table Card Daftar Termin -->
                <div class="data-card">
                    <div style="padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;">
                        <div style="font-weight:700;color:#0f172a;font-size:0.9375rem;display:flex;align-items:center;gap:0.5rem;">
                            <i class="fas fa-list-ol" style="color:#4f46e5;"></i> Daftar Jadwal Termin Pencairan
                        </div>
                        <span style="font-size:0.8125rem;font-weight:600;color:#64748b;">
                            Total: <strong style="color:#0f172a;">{{ pencairans.length }}</strong> termin
                        </span>
                    </div>

                    <div v-if="pencairans.length > 0">
                        <div class="table-responsive">
                            <table class="solid-table">
                                <thead>
                                    <tr>
                                        <th style="width:70px;text-align:center;">Termin</th>
                                        <th>Nominal Dijanjikan</th>
                                        <th>Realisasi Cair</th>
                                        <th>Sisa Belum Cair</th>
                                        <th>Jatuh Tempo</th>
                                        <th style="text-align:center;">Status</th>
                                        <th style="width:130px;text-align:center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in pencairans" :key="p.id">
                                        <td style="text-align:center;">
                                            <span style="font-family:monospace;font-weight:700;color:#0f172a;background:#f1f5f9;padding:0.2rem 0.5rem;border-radius:0.375rem;">
                                                #{{ p.termin_ke }}
                                            </span>
                                        </td>
                                        <td style="font-weight:700;color:#0f172a;">
                                            {{ formatRupiah(p.nominal_dijanjikan) }}
                                        </td>
                                        <td>
                                            <span style="font-weight:700;color:#16a34a;">
                                                {{ formatRupiah(p.nominal_cair) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span style="font-weight:700;color:#dc2626;">
                                                {{ formatRupiah(Math.max(0, p.nominal_dijanjikan - p.nominal_cair)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span style="color:#334155;">
                                                {{ p.jatuh_tempo_external ? formatDate(p.jatuh_tempo_external) : '-' }}
                                            </span>
                                        </td>
                                        <td style="text-align:center;">
                                            <span :class="['solid-badge', p.status === 'cair_penuh' ? 'badge-solid-success' : p.status === 'cair_sebagian' ? 'badge-solid-warning' : 'badge-solid-info']">
                                                {{ p.status === 'cair_penuh' ? 'Cair Lunas' : p.status === 'cair_sebagian' ? 'Cair Sebagian' : 'Belum Cair' }}
                                            </span>
                                        </td>
                                        <td style="text-align:center;">
                                            <div style="display:flex;gap:0.375rem;justify-content:center;flex-wrap:wrap;">
                                                <button
                                                    class="action-btn-custom btn-edit"
                                                    @click="openEdit(p)"
                                                    title="Edit Data Termin"
                                                >
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <button
                                                    class="action-btn-custom btn-cair"
                                                    @click="openKonfirmasi(p)"
                                                    title="Konfirmasi Dana Cair Masuk"
                                                >
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                                <button
                                                    class="action-btn-custom btn-delete"
                                                    @click="confirmDestroy(p)"
                                                    title="Hapus Termin"
                                                >
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>

                                            <!-- Inline Konfirmasi Popup -->
                                            <div v-if="showKonfirmasi === p.id" class="konfirmasi-box">
                                                <div style="font-weight:700;color:#0f172a;font-size:0.8125rem;margin-bottom:0.5rem;">
                                                    Konfirmasi Pencairan Dana:
                                                </div>
                                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;text-align:left;">
                                                    <div>
                                                        <label class="form-label-solid">Nominal Cair (Rp)</label>
                                                        <input
                                                            :value="Number(konfirmasiForm.nominal_cair || 0).toLocaleString('id-ID')"
                                                            @input="onKonfirmasiNominalInput"
                                                            type="text"
                                                            inputmode="numeric"
                                                            class="filter-input"
                                                        />
                                                    </div>
                                                    <div>
                                                        <label class="form-label-solid">Tanggal Masuk</label>
                                                        <input v-model="konfirmasiForm.tanggal_cair" type="date" class="filter-input" />
                                                    </div>
                                                </div>
                                                <div style="margin-top:0.5rem;text-align:left;">
                                                    <label class="form-label-solid">File Bukti Transfer (Opsional)</label>
                                                    <input type="file" @change="handleFile" class="filter-input" />
                                                </div>
                                                <div style="margin-top:0.75rem;display:flex;gap:0.5rem;justify-content:flex-end;">
                                                    <button class="solid-btn btn-white-border" style="font-size:0.75rem;padding:0.35rem 0.6rem;" @click="showKonfirmasi = null">Batal</button>
                                                    <button class="solid-btn btn-indigo-solid" style="font-size:0.75rem;padding:0.35rem 0.75rem;" @click="submitKonfirmasi(p)">Simpan Pencairan</button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-else style="text-align:center;padding:4rem 2rem;color:#64748b;">
                        <i class="fas fa-hand-holding-usd" style="font-size:2.5rem;color:#cbd5e1;margin-bottom:0.75rem;display:block;"></i>
                        <h4 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0 0 0.25rem;">Belum Ada Jadwal Termin</h4>
                        <p style="font-size:0.8125rem;color:#64748b;margin:0;">Tambahkan termin pencairan dana di atas untuk memantau realisasi penagihan eksternal.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teleport Modals -->
        <Teleport to="body">
            <!-- Modal Delete Confirmation -->
            <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
                <div class="modal-card" style="max-width:440px;">
                    <div class="modal-header">
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <span style="width:36px;height:36px;border-radius:0.5rem;background:#fee2e2;color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:1.125rem;">
                                <i class="fas fa-trash-alt"></i>
                            </span>
                            <div>
                                <h4 style="margin:0;font-size:1rem;font-weight:700;color:#991b1b;">Konfirmasi Hapus</h4>
                                <p style="margin:0;font-size:0.75rem;color:#64748b;">Hapus data jadwal termin</p>
                            </div>
                        </div>
                        <button class="modal-close-btn" @click="showDeleteModal = false">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p style="margin:0;color:#334155;font-size:0.875rem;line-height:1.5;">
                            Apakah Anda yakin ingin menghapus data <strong>Termin #{{ itemToDelete?.termin_ke }}</strong>?
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="solid-btn btn-white-border" @click="showDeleteModal = false">Batal</button>
                        <button type="button" class="solid-btn btn-danger-solid" @click="executeDelete">
                            <i class="fas fa-trash-alt"></i> Ya, Hapus
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
}
.termin-form-grid {
    display: grid;
    grid-template-columns: 100px minmax(180px, 1.3fr) 1fr 1fr;
    gap: 0.75rem;
}
.span-2-col {
    grid-column: span 3;
}
@media (max-width: 900px) {
    .termin-form-grid {
        grid-template-columns: 1fr 1fr;
    }
    .span-2-col {
        grid-column: span 2;
    }
}
@media (max-width: 640px) {
    .termin-form-grid {
        grid-template-columns: 1fr;
    }
    .span-2-col {
        grid-column: span 1;
    }
}

.form-label-solid {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 0.375rem;
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
    box-sizing: border-box;
}
.filter-input:focus {
    border-color: #4f46e5;
}

.currency-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.curr-prefix {
    position: absolute;
    left: 0.75rem;
    color: #94a3b8;
    font-weight: 700;
    font-size: 0.8125rem;
    pointer-events: none;
}
.curr-input {
    padding-left: 2.25rem !important;
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
.badge-solid-warning {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
}
.badge-solid-info {
    background: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
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
.btn-edit {
    background: #eff6ff;
    color: #1d4ed8;
    border-color: #bfdbfe;
}
.btn-edit:hover {
    background: #dbeafe;
}
.btn-cair {
    background: #ecfdf5;
    color: #059669;
    border-color: #a7f3d0;
}
.btn-cair:hover {
    background: #d1fae5;
}
.btn-delete {
    background: #fef2f2;
    color: #b91c1c;
    border-color: #fecaca;
}
.btn-delete:hover {
    background: #fee2e2;
}

.konfirmasi-box {
    margin-top: 0.75rem;
    padding: 0.875rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    background: #f8fafc;
    text-align: left;
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
.solid-notice {
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    line-height: 1.5;
    display: flex;
    align-items: flex-start;
    gap: 0.625rem;
}
.solid-notice-info {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1e40af;
}
</style>
