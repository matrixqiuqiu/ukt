<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { formatRupiah } from '@/utils';

const props = defineProps({
    konfigurasis: Array,
    angkatans: Array,
    jurusans: Array,
    komponens: Array,
    summary: Array,
    filters: Object,
    semesterAktif: Object,
});

const page = usePage();
const showModal = ref(false);
const editMode = ref(false);
const editId = ref(null);

const filterAngkatan = ref(props.filters?.angkatan || '');
const filterJurusan = ref(props.filters?.jurusan || '');

const form = useForm({
    komponen_biaya_id: '',
    angkatan: new Date().getFullYear(),
    jurusan: '',
    nominal: 0,
    status_aktif: true,
});

const applyFilter = () => {
    const params = {};
    if (filterAngkatan.value) params.angkatan = filterAngkatan.value;
    if (filterJurusan.value) params.jurusan = filterJurusan.value;
    router.get(route('admin.biaya.index'), params, { preserveState: true });
};

const openCreate = () => {
    editMode.value = false;
    editId.value = null;
    form.reset();
    form.status_aktif = true;
    form.angkatan = new Date().getFullYear();
    form.nominal = 0;
    showModal.value = true;
};

const openEdit = (item) => {
    editMode.value = true;
    editId.value = item.id;
    form.komponen_biaya_id = item.komponen_biaya_id;
    form.angkatan = item.angkatan;
    form.jurusan = item.jurusan;
    form.nominal = item.nominal;
    form.status_aktif = item.status_aktif;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.clearErrors();
    form.reset();
};

const submit = () => {
    if (editMode.value) {
        form.put(route('admin.biaya.update', editId.value), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.biaya.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const deleteItem = (item) => {
    if (confirm(`Yakin ingin menghapus konfigurasi biaya ini?`)) {
        router.delete(route('admin.biaya.destroy', item.id), {
            preserveScroll: true,
        });
    }
};

const toggleStatus = (item) => {
    router.post(route('admin.biaya.toggle', item.id), {}, { preserveScroll: true });
};

const filteredSummary = computed(() => {
    let data = props.summary;
    if (filterAngkatan.value) {
        data = data.filter(s => s.angkatan == filterAngkatan.value);
    }
    if (filterJurusan.value) {
        data = data.filter(s => s.jurusan === filterJurusan.value);
    }
    return data;
});

const getKomponenName = (id) => {
    const k = props.komponens.find(c => c.id == id);
    return k ? k.nama : '-';
};
</script>

<template>
    <Head title="Pengaturan Biaya" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="page-heading">Pengaturan Biaya UKT</h2>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <!-- Semester Aktif Info -->
                <div v-if="semesterAktif" class="info-banner">
                    <div class="info-banner__main">
                        <span class="info-banner__icon"><i class="fas fa-calendar-alt"></i></span>
                        <div class="info-banner__text">
                            <span class="info-label">Tahun Akademik:</span> <span class="info-value">{{ semesterAktif.tahun_akademik }}</span>
                            <span class="info-sep">•</span>
                            <span class="info-label">Jatuh Tempo:</span> <span class="info-value">{{ new Date(semesterAktif.jatuh_tempo).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</span>
                        </div>
                    </div>
                    <div class="info-banner__hint"><i class="fas fa-info-circle"></i> Toggle biaya → tagihan otomatis dibuat per mahasiswa</div>
                </div>

                <!-- Filter -->
                <div class="custom-card filter-card">
                    <div class="card-body">
                        <div class="filter-grid">
                            <div class="filter-field">
                                <label class="filter-label">Angkatan</label>
                                <select v-model="filterAngkatan" class="form-control">
                                    <option value="">Semua Angkatan</option>
                                    <option v-for="a in angkatans" :key="a" :value="a">{{ a }}</option>
                                </select>
                            </div>
                            <div class="filter-field">
                                <label class="filter-label">Jurusan</label>
                                <select v-model="filterJurusan" class="form-control">
                                    <option value="">Semua Jurusan</option>
                                    <option v-for="j in jurusans" :key="j" :value="j">{{ j }}</option>
                                </select>
                            </div>
                            <div class="filter-actions">
                                <button class="m-btn m-btn-primary" @click="applyFilter"><i class="fas fa-filter"></i> Filter</button>
                                <button class="m-btn m-btn-secondary" @click="filterAngkatan='';filterJurusan='';applyFilter()"><i class="fas fa-times"></i> Reset</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div v-if="filteredSummary.length > 0" class="summary-grid">
                    <div v-for="s in filteredSummary" :key="s.angkatan + '-' + s.jurusan" class="custom-card summary-card">
                        <div class="card-body">
                            <div class="summary-head">
                                <div>
                                    <div class="summary-angkatan">Angkatan {{ s.angkatan }}</div>
                                    <div class="summary-jurusan">{{ s.jurusan }}</div>
                                </div>
                                <span class="m-badge m-badge-success">{{ s.komponen_count }} komponen</span>
                            </div>
                            <div class="summary-total">{{ formatRupiah(s.total) }}</div>
                            <div class="summary-sub">Total biaya per mahasiswa</div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="margin-bottom:1.5rem;">
                    <button class="m-btn m-btn-primary" @click="openCreate">
                        <i class="fas fa-plus"></i> Tambah Biaya
                    </button>
                </div>

                <!-- Table -->
                <div class="custom-card">
                    <div class="card-header">
                        <h4><i class="fas fa-list" style="margin-right:0.5rem;"></i> Daftar Konfigurasi Biaya</h4>
                        <span class="m-badge m-badge-secondary">{{ konfigurasis.length }} data</span>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <div v-if="konfigurasis && konfigurasis.length > 0" class="table-responsive">
                            <table class="m-data-table">
                                <thead>
                                    <tr>
                                        <th style="width:50px">No</th>
                                        <th>Komponen</th>
                                        <th>Angkatan</th>
                                        <th>Jurusan</th>
                                        <th style="text-align:right;">Biaya</th>
                                        <th style="width:100px">Status</th>
                                        <th style="width:120px">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in konfigurasis" :key="item.id">
                                        <td>{{ index + 1 }}</td>
                                        <td style="font-weight:600;">{{ item.komponen_biaya?.nama || '-' }}</td>
                                        <td>{{ item.angkatan }}</td>
                                        <td>{{ item.jurusan }}</td>
                                        <td style="text-align:right;font-weight:700;color:var(--gray-900);">{{ formatRupiah(item.nominal) }}</td>
                                        <td>
                                            <span class="m-badge" :class="item.status_aktif ? 'm-badge-success' : 'm-badge-danger'">
                                                {{ item.status_aktif ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display:flex;gap:0.375rem;">
                                                <button class="m-btn m-btn-sm" :class="item.status_aktif ? 'm-badge-warning' : 'm-badge-success'" @click="toggleStatus(item)">
                                                    <i :class="item.status_aktif ? 'fas fa-toggle-on' : 'fas fa-toggle-off'"></i>
                                                </button>
                                                <button class="m-btn m-btn-sm m-btn-secondary" @click="openEdit(item)" title="Edit">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <button class="m-btn m-btn-sm m-btn-danger" @click="deleteItem(item)" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="empty-state">
                            <i class="fas fa-coins"></i>
                            <p>Belum ada konfigurasi biaya. Klik "Tambah Biaya" untuk menambahkan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tambah/Edit Biaya -->
        <Teleport to="body">
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                <div class="modal-box">
                    <div class="modal-header">
                        <h3>{{ editMode ? 'Edit Konfigurasi Biaya' : 'Tambah Konfigurasi Biaya' }}</h3>
                        <button class="modal-close" @click="closeModal"><i class="fas fa-times"></i></button>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="modal-body">
                            <div class="form-group">
                                <label class="form-label">Komponen Biaya <span style="color:var(--danger);">*</span></label>
                                <select v-model="form.komponen_biaya_id" class="form-control" required>
                                    <option value="">Pilih Komponen</option>
                                    <option v-for="k in komponens" :key="k.id" :value="k.id">{{ k.nama }} ({{ k.kode }})</option>
                                </select>
                                <div v-if="form.errors.komponen_biaya_id" class="form-error">{{ form.errors.komponen_biaya_id }}</div>
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                                <div class="form-group">
                                    <label class="form-label">Angkatan <span style="color:var(--danger);">*</span></label>
                                    <select v-model="form.angkatan" class="form-control" required>
                                        <option v-for="a in angkatans" :key="a" :value="a">{{ a }}</option>
                                    </select>
                                    <div v-if="form.errors.angkatan" class="form-error">{{ form.errors.angkatan }}</div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Jurusan <span style="color:var(--danger);">*</span></label>
                                    <select v-model="form.jurusan" class="form-control" required>
                                        <option value="">Pilih Jurusan</option>
                                        <option v-for="j in jurusans" :key="j" :value="j">{{ j }}</option>
                                    </select>
                                    <div v-if="form.errors.jurusan" class="form-error">{{ form.errors.jurusan }}</div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nominal (Rp) <span style="color:var(--danger);">*</span></label>
                                <input v-model="form.nominal" type="number" class="form-control" min="0" step="1000" required />
                                <div v-if="form.errors.nominal" class="form-error">{{ form.errors.nominal }}</div>
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
                            <button type="button" class="m-btn m-btn-secondary" @click="closeModal">Batal</button>
                            <button type="submit" class="m-btn m-btn-primary" :disabled="form.processing">
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
/* Banner */
.info-banner { background: linear-gradient(135deg,#eff6ff 0%,#f0f9ff 100%); border:1px solid #bfdbfe; border-radius:1rem; padding:1rem 1.25rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:1rem; flex-wrap:wrap; box-shadow:0 1px 3px rgba(59,130,246,0.08); }
.info-banner__main { display:flex; align-items:center; gap:0.75rem; flex:1; min-width:0; }
.info-banner__icon { width:2.25rem; height:2.25rem; border-radius:0.75rem; background:#3b82f6; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.info-banner__text { font-size:0.875rem; color:#1e40af; }
.info-label { font-weight:700; } .info-value { font-weight:500; } .info-sep { margin:0 0.5rem; color:#6b7280; }
.info-banner__hint { margin-left:auto; font-size:0.8125rem; color:#6b7280; background:#fff; padding:0.375rem 0.75rem; border-radius:9999px; border:1px solid #e5e7eb; }
/* Filter */
.filter-card { margin-bottom:1.5rem; overflow:hidden; border:1px solid var(--gray-200); border-radius:1rem; box-shadow:0 1px 3px rgba(0,0,0,0.05); }
.filter-card .card-body { padding:1rem 1.25rem; }
.filter-grid { display:grid; grid-template-columns:1fr 1fr auto; gap:0.75rem; align-items:end; }
.filter-label { display:block; font-size:0.75rem; font-weight:700; color:var(--gray-600); margin-bottom:0.375rem; text-transform:uppercase; letter-spacing:0.03em; }
.filter-actions { display:flex; gap:0.5rem; }
/* Summary */
.summary-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:1rem; margin-bottom:1.5rem; }
.summary-card { margin-bottom:0; border:1px solid var(--gray-200); border-radius:1rem; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.05); transition: transform 0.15s, box-shadow 0.15s; }
.summary-card:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(0,0,0,0.06); }
.summary-card .card-body { padding:1.25rem; }
.summary-head { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.75rem; gap:0.5rem; }
.summary-angkatan { font-size:0.75rem; color:var(--gray-600); font-weight:700; text-transform:uppercase; letter-spacing:0.04em; }
.summary-jurusan { font-size:0.9375rem; font-weight:800; color:var(--gray-900); line-height:1.2; }
.summary-total { font-size:1.6rem; font-weight:800; color:var(--primary); letter-spacing:-0.02em; }
.summary-sub { font-size:0.75rem; color:var(--gray-500); margin-top:0.125rem; }
/* Table */
.custom-card { border:1px solid var(--gray-200); border-radius:1rem; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.05); }
.card-header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem; padding:1rem 1.25rem; border-bottom:1px solid var(--gray-100); background:linear-gradient(to bottom,#fff,#f9fafb); }
.table-responsive { width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch; }
.table-responsive .m-data-table { min-width:720px; }
.empty-state { text-align:center; padding:3rem 1.5rem; color:var(--gray-600); }
.empty-state i { font-size:2.5rem; color:var(--gray-300); margin-bottom:1rem; display:block; }
.empty-state p { margin:0; font-size:0.875rem; }
/* Forms & Modal */
.form-group { margin-bottom: 1rem; }
.form-label { display: block; font-size: 0.875rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.375rem; }
.form-control { width: 100%; padding: 0.625rem 0.875rem; border: 1px solid var(--gray-300); border-radius: 0.75rem; font-size: 0.875rem; transition: border-color 0.2s, box-shadow 0.2s; background:#fff; }
.form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15); }
.form-error { font-size: 0.75rem; color: var(--danger); margin-top: 0.25rem; }
.toggle-label { display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.875rem; }
.toggle-label input[type="checkbox"] { width: 1rem; height: 1rem; accent-color: var(--primary); }
.modal-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(2px); display: flex; align-items: center; justify-content: center; z-index: 100; padding: 1rem; }
.modal-box { background: white; border-radius: 1rem; width: 100%; max-width: 480px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15); }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--gray-200); }
.modal-header h3 { margin: 0; font-size: 1.125rem; font-weight: 700; color: var(--gray-900); }
.modal-close { background: none; border: none; font-size: 1.125rem; color: var(--gray-500); cursor: pointer; padding: 0.25rem; }
.modal-close:hover { color: var(--gray-800); }
.modal-body { padding: 1.5rem; }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.5rem; border-top: 1px solid var(--gray-200); }
.m-btn-danger { background: var(--danger); color: white; }
.m-btn-danger:hover { background: #dc2626; }
.m-btn-warning { background: var(--warning); color: white; }
.m-btn-warning:hover { background: #d97706; }
@media (max-width: 900px) { .filter-grid { grid-template-columns:1fr 1fr; } .filter-actions { grid-column:1/-1; } .summary-grid { grid-template-columns:1fr 1fr; } }
@media (max-width: 640px) {
    .container-xl { padding-left:1rem; padding-right:1rem; }
    .info-banner { flex-direction:column; align-items:stretch; }
    .info-banner__hint { margin-left:0; }
    .filter-grid { grid-template-columns:1fr; }
    .summary-grid { grid-template-columns:1fr; }
    .table-responsive .m-data-table { min-width:600px; font-size:0.8125rem; }
}
</style>
