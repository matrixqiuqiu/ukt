<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { formatRupiah } from '@/utils';

const props = defineProps({
    beasiswas: Object,
    filters: Object,
    tahunAkademiks: Array,
    komponens: Array,
    jenisBeasiswas: Array,
});

const search = ref(props.filters?.search || '');
const jenis = ref(props.filters?.jenis || '');
const status = ref(props.filters?.status || '');

const showModal = ref(false);
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const editMode = ref(false);
const editId = ref(null);

const form = ref({
    kode: '',
    nama_beasiswa: '',
    jenis: 'prestasi',
    jenis_beasiswa_id: '',
    sumber_dana: 'internal',
    tahun_akademik_id: '',
    semester: '',
    tipe_diskon: 'persen',
    nilai_diskon: 0,
    komponen_biaya_id: '',
    kuota: 0,
    tanggal_buka: '',
    tanggal_tutup: '',
    deskripsi: '',
    status_aktif: true,
});

const doFilter = () => {
    const p = {};
    if (search.value) p.search = search.value;
    if (jenis.value) p.jenis = jenis.value;
    if (status.value) p.status = status.value;
    router.get(route('admin.beasiswa.index'), p, { preserveState: true, replace: true });
};

const clearFilter = () => {
    search.value = '';
    jenis.value = '';
    status.value = '';
    router.get(route('admin.beasiswa.index'), {}, { preserveState: true, replace: true });
};

const hasFilter = () => search.value || jenis.value || status.value;

const openCreate = () => {
    editMode.value = false;
    editId.value = null;
    form.value = {
        kode: '',
        nama_beasiswa: '',
        jenis: 'prestasi',
        jenis_beasiswa_id: props.jenisBeasiswas?.[0]?.id || '',
        sumber_dana: 'internal',
        tahun_akademik_id: '',
        semester: '',
        tipe_diskon: 'persen',
        nilai_diskon: 0,
        komponen_biaya_id: '',
        kuota: 0,
        tanggal_buka: '',
        tanggal_tutup: '',
        deskripsi: '',
        status_aktif: true,
    };
    showModal.value = true;
};

const openEdit = (row) => {
    editMode.value = true;
    editId.value = row.id;
    form.value = {
        kode: row.kode,
        nama_beasiswa: row.nama_beasiswa,
        jenis: row.jenis,
        jenis_beasiswa_id: row.jenis_beasiswa_id || '',
        sumber_dana: row.sumber_dana || 'internal',
        tahun_akademik_id: row.tahun_akademik_id || '',
        semester: row.semester || '',
        tipe_diskon: row.tipe_diskon || 'persen',
        nilai_diskon: row.nilai_diskon || 0,
        komponen_biaya_id: row.komponen_biaya_id || '',
        kuota: row.kuota || 0,
        tanggal_buka: row.tanggal_buka || '',
        tanggal_tutup: row.tanggal_tutup || '',
        deskripsi: row.deskripsi || '',
        status_aktif: !!row.status_aktif,
    };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const submit = () => {
    const url = editMode.value ? route('admin.beasiswa.update', editId.value) : route('admin.beasiswa.store');
    const method = editMode.value ? 'put' : 'post';
    router[method](url, form.value, {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const confirmDestroy = (row) => {
    itemToDelete.value = row;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (!itemToDelete.value) return;
    router.delete(route('admin.beasiswa.destroy', itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            itemToDelete.value = null;
        }
    });
};

const toggle = (row) => {
    router.post(route('admin.beasiswa.toggle', row.id), {}, { preserveScroll: true });
};

const diskonLabel = (row) => {
    if (row.tipe_diskon === 'full') return 'Gratis 100%';
    if (row.tipe_diskon === 'persen') return `${row.nilai_diskon}%`;
    return formatRupiah(row.nilai_diskon);
};

const sumberDanaLabel = (val) => {
    const map = {
        internal: 'Internal Kampus',
        eksternal: 'Eksternal / Mitra',
        pemerintah: 'Pemerintah (KIP/Pemda)',
        kerjasama: 'Kerjasama Lembaga',
    };
    return map[val] || val;
};

// Sinkron semester beasiswa dengan Tahun Akademik master
watch(() => form.value.tahun_akademik_id, (newVal) => {
    if (!newVal) return;
    const ta = props.tahunAkademiks?.find(t => t.id == newVal);
    if (ta) {
        const flag = String(ta.semester).toLowerCase() === 'genap' ? 2 : 1;
        if (!form.value.semester || form.value.semester !== flag) {
            form.value.semester = flag;
        }
    }
});
</script>

<template>
    <Head title="Data Beasiswa & Potongan UKT" />
    <AuthenticatedLayout>
        <template #header>
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div>
                    <h2 class="page-heading" style="font-size:1.375rem;font-weight:700;color:#0f172a;margin-bottom:0.25rem;">Data Beasiswa & Potongan UKT</h2>
                    <p style="font-size:0.875rem;color:#64748b;margin:0;">Kelola program beasiswa, kuota kuota penerima, skema potongan, dan pencairan dana</p>
                </div>
                <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                    <Link :href="route('admin.jenis-beasiswa.index')" class="solid-btn btn-white-border" title="Kelola Master Jenis Beasiswa">
                        <i class="fas fa-tags"></i> Master Jenis
                    </Link>
                    <a :href="route('admin.beasiswa.export')" class="solid-btn btn-white-border" title="Unduh data beasiswa dalam format Excel">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                    <button @click="openCreate" class="solid-btn btn-indigo-solid">
                        <i class="fas fa-plus"></i> Tambah Beasiswa
                    </button>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">
                <!-- Filter Card -->
                <div class="filter-card">
                    <div style="font-size:0.875rem;font-weight:700;color:#1e293b;margin-bottom:0.75rem;display:flex;align-items:center;justify-content:space-between;">
                        <span style="display:flex;align-items:center;gap:0.5rem;">
                            <i class="fas fa-filter" style="color:#4f46e5;"></i> Filter Beasiswa
                        </span>
                        <span v-if="beasiswas?.total !== undefined" style="font-size:0.8125rem;font-weight:500;color:#64748b;">
                            Total: <strong style="color:#0f172a;">{{ beasiswas.total }}</strong> program beasiswa
                        </span>
                    </div>

                    <div class="filter-grid">
                        <!-- Search Box -->
                        <div class="search-box-wrap">
                            <i class="fas fa-search search-icon"></i>
                            <input
                                type="search"
                                class="filter-input search-input"
                                v-model="search"
                                placeholder="Cari kode atau nama beasiswa..."
                                @keyup.enter="doFilter"
                            />
                        </div>

                        <!-- Jenis Beasiswa Filter -->
                        <select v-model="jenis" @change="doFilter" class="filter-input">
                            <option value="">Semua Jenis Beasiswa</option>
                            <option v-for="j in jenisBeasiswas" :key="j.id" :value="j.id">{{ j.kode }} - {{ j.nama }}</option>
                        </select>

                        <!-- Status Filter -->
                        <select v-model="status" @change="doFilter" class="filter-input">
                            <option value="">Semua Status</option>
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>

                        <!-- Action Buttons -->
                        <div class="filter-actions">
                            <button @click="doFilter" class="btn-solid-primary" style="padding:0.5625rem 1rem;">
                                <i class="fas fa-search"></i> Terapkan
                            </button>
                            <button v-if="hasFilter()" @click="clearFilter" class="btn-solid-secondary" style="padding:0.5625rem 0.875rem;" title="Reset filter">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="data-card">
                    <div v-if="beasiswas.data && beasiswas.data.length > 0">
                        <div class="table-responsive">
                            <table class="solid-table">
                                <thead>
                                    <tr>
                                        <th style="width:40px;text-align:center;">No</th>
                                        <th>Kode</th>
                                        <th>Nama Beasiswa & Sumber</th>
                                        <th>Jenis Beasiswa</th>
                                        <th>Periode Akademik</th>
                                        <th>Skema Diskon</th>
                                        <th style="text-align:center;">Kuota</th>
                                        <th style="text-align:center;">Status</th>
                                        <th style="text-align:center;width:90px;">Penerima</th>
                                        <th style="text-align:center;width:90px;">Pencairan</th>
                                        <th style="width:110px;text-align:center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(b, i) in beasiswas.data" :key="b.id">
                                        <td style="text-align:center;color:#64748b;font-size:0.8125rem;">
                                            {{ (beasiswas.from || 1) + i }}
                                        </td>
                                        <td>
                                            <span style="font-family:monospace;font-weight:700;color:#0f172a;background:#f1f5f9;padding:0.2rem 0.45rem;border-radius:0.375rem;font-size:0.8125rem;">
                                                {{ b.kode }}
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-weight:700;color:#0f172a;font-size:0.875rem;">
                                                {{ b.nama_beasiswa }}
                                            </div>
                                            <div style="font-size:0.75rem;color:#64748b;display:flex;align-items:center;gap:0.375rem;margin-top:0.125rem;">
                                                <i class="fas fa-shield-alt" style="font-size:0.6875rem;color:#94a3b8;"></i>
                                                <span>{{ sumberDanaLabel(b.sumber_dana) }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="solid-badge badge-solid-info" :title="b.jenis_beasiswa_id ? '' : b.jenis">
                                                {{ b.jenisBeasiswa ? b.jenisBeasiswa.nama : (b.jenis || '-') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div v-if="b.tahun_akademik" style="font-size:0.8125rem;color:#1e293b;font-weight:600;">
                                                {{ b.tahun_akademik.nama }}
                                                <span style="font-size:0.75rem;color:#64748b;font-weight:normal;">({{ b.semester === 1 ? 'Ganjil' : 'Genap' }})</span>
                                            </div>
                                            <span v-else style="font-size:0.75rem;color:#64748b;background:#f8fafc;border:1px solid #e2e8f0;padding:0.15rem 0.4rem;border-radius:0.25rem;">
                                                Berlaku Umum
                                            </span>
                                        </td>
                                        <td>
                                            <span :class="['solid-badge', b.tipe_diskon === 'full' ? 'badge-solid-success' : b.tipe_diskon === 'persen' ? 'badge-solid-warning' : 'badge-solid-primary']">
                                                {{ diskonLabel(b) }}
                                            </span>
                                        </td>
                                        <td style="text-align:center;">
                                            <div style="font-weight:700;color:#0f172a;font-size:0.8125rem;">
                                                {{ b.terpakai }} / {{ b.kuota > 0 ? b.kuota : '∞' }}
                                            </div>
                                            <div v-if="b.kuota > 0" style="font-size:0.6875rem;color:#64748b;">
                                                sisa {{ Math.max(0, b.kuota - b.terpakai) }}
                                            </div>
                                            <div v-else style="font-size:0.6875rem;color:#16a34a;">
                                                bebas kuota
                                            </div>
                                        </td>
                                        <td style="text-align:center;">
                                            <span :class="['solid-badge', b.status_aktif ? 'badge-solid-success' : 'badge-solid-danger']">
                                                {{ b.status_aktif ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td style="text-align:center;">
                                            <Link :href="route('admin.beasiswa.assignments', b.id)" class="action-btn-custom btn-penerima" title="Kelola Mahasiswa Penerima">
                                                <i class="fas fa-users"></i>
                                                <span style="font-weight:700;font-size:0.75rem;">{{ b.terpakai }}</span>
                                            </Link>
                                        </td>
                                        <td style="text-align:center;">
                                            <Link v-if="b.sumber_dana === 'eksternal'" :href="route('admin.beasiswa.pencairan.index', b.id)" class="action-btn-custom btn-pencairan" title="Kelola Pencairan Dana Eksternal">
                                                <i class="fas fa-hand-holding-usd"></i>
                                            </Link>
                                            <span v-else style="color:#cbd5e1;font-size:0.8125rem;">—</span>
                                        </td>
                                        <td style="text-align:center;">
                                            <div style="display:flex;gap:0.375rem;justify-content:center;">
                                                <button
                                                    @click="toggle(b)"
                                                    class="action-btn-custom"
                                                    :class="b.status_aktif ? 'btn-toggle-on' : 'btn-toggle-off'"
                                                    :title="b.status_aktif ? 'Nonaktifkan Beasiswa' : 'Aktifkan Beasiswa'"
                                                >
                                                    <i :class="b.status_aktif ? 'fas fa-toggle-on' : 'fas fa-toggle-off'"></i>
                                                </button>
                                                <button
                                                    @click="openEdit(b)"
                                                    class="action-btn-custom btn-edit"
                                                    title="Edit Data Beasiswa"
                                                >
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <button
                                                    @click="confirmDestroy(b)"
                                                    class="action-btn-custom btn-delete"
                                                    title="Hapus Beasiswa"
                                                >
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Footer -->
                        <div class="pagination-footer">
                            <span style="font-size:0.8125rem;color:#64748b;">
                                Menampilkan <strong style="color:#0f172a;">{{ beasiswas.from || 1 }}-{{ beasiswas.to || beasiswas.total }}</strong> dari <strong style="color:#0f172a;">{{ beasiswas.total }}</strong> beasiswa
                            </span>
                            <div class="pagination-btns">
                                <template v-for="link in beasiswas.links" :key="link.label">
                                    <span v-if="!link.url" class="p-btn p-disabled" v-html="link.label"></span>
                                    <Link v-else :href="link.url" class="p-btn" :class="{ 'p-active': link.active }" v-html="link.label" preserve-state />
                                </template>
                            </div>
                        </div>
                    </div>

                    <div v-else style="text-align:center;padding:4rem 2rem;color:#64748b;">
                        <i class="fas fa-graduation-cap" style="font-size:2.5rem;color:#cbd5e1;margin-bottom:0.75rem;display:block;"></i>
                        <h4 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0 0 0.25rem;">Tidak Ada Data Beasiswa</h4>
                        <p style="font-size:0.8125rem;color:#64748b;margin:0 0 1rem;">Tidak ditemukan data program beasiswa yang sesuai dengan filter atau kata kunci pencarian.</p>
                        <button v-if="hasFilter()" @click="clearFilter" class="btn-solid-secondary" style="font-size:0.8125rem;">
                            <i class="fas fa-undo"></i> Reset Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teleport Modals -->
        <Teleport to="body">
            <!-- Modal Form Tambah / Edit Beasiswa -->
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                <div class="modal-card modal-card-lg">
                    <div class="modal-header">
                        <div style="display:flex;align-items:center;gap:0.625rem;">
                            <span style="width:36px;height:36px;border-radius:0.5rem;background:#e0e7ff;color:#4338ca;display:flex;align-items:center;justify-content:center;font-size:1.125rem;">
                                <i class="fas" :class="editMode ? 'fa-edit' : 'fa-graduation-cap'"></i>
                            </span>
                            <div>
                                <h4 style="margin:0;font-size:1rem;font-weight:700;color:#0f172a;">{{ editMode ? 'Edit Data Beasiswa' : 'Tambah Beasiswa Baru' }}</h4>
                                <p style="margin:0;font-size:0.75rem;color:#64748b;">Konfigurasi kuota, skema potongan, dan masa aktif beasiswa</p>
                            </div>
                        </div>
                        <button class="modal-close-btn" @click="closeModal">&times;</button>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="modal-body" style="max-height:72vh;overflow-y:auto;display:grid;gap:0.875rem;">
                            <div class="modal-grid-2">
                                <div>
                                    <label class="modal-label">Kode Beasiswa <span style="color:#dc2626;">*</span></label>
                                    <input v-model="form.kode" class="modal-input" placeholder="Contoh: BSW-PRESTASI" required />
                                </div>
                                <div>
                                    <label class="modal-label">Jenis Beasiswa <span style="color:#dc2626;">*</span></label>
                                    <select v-model="form.jenis_beasiswa_id" class="modal-input" required>
                                        <option value="">-- Pilih Kategori Jenis --</option>
                                        <option v-for="j in jenisBeasiswas" :key="j.id" :value="j.id">{{ j.kode }} - {{ j.nama }}</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="modal-label">Nama Beasiswa <span style="color:#dc2626;">*</span></label>
                                <input v-model="form.nama_beasiswa" class="modal-input" placeholder="Contoh: Beasiswa Prestasi Akademik Semester Ganjil" required />
                            </div>

                            <div class="modal-grid-2">
                                <div>
                                    <label class="modal-label">Sumber Dana <span style="color:#dc2626;">*</span></label>
                                    <select v-model="form.sumber_dana" class="modal-input" required>
                                        <option value="internal">Internal Kampus</option>
                                        <option value="eksternal">Eksternal / Mitra</option>
                                        <option value="pemerintah">Pemerintah (KIP/Pemda)</option>
                                        <option value="kerjasama">Kerjasama Lembaga</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="modal-label">Periode Tahun Akademik</label>
                                    <select v-model="form.tahun_akademik_id" class="modal-input">
                                        <option value="">Semua Periode (Berlaku Umum)</option>
                                        <option v-for="ta in tahunAkademiks" :key="ta.id" :value="ta.id">{{ ta.nama }} - {{ ta.semester }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="modal-grid-3">
                                <div>
                                    <label class="modal-label">Tipe Diskon <span style="color:#dc2626;">*</span></label>
                                    <select v-model="form.tipe_diskon" class="modal-input" required>
                                        <option value="persen">Persentase (%)</option>
                                        <option value="nominal">Nominal Tetap (Rp)</option>
                                        <option value="full">Gratis Penuh (100%)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="modal-label">
                                        Nilai Diskon <span v-if="form.tipe_diskon !== 'full'" style="color:#dc2626;">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        v-model="form.nilai_diskon"
                                        class="modal-input"
                                        :disabled="form.tipe_diskon === 'full'"
                                        min="0"
                                        step="any"
                                        :placeholder="form.tipe_diskon === 'persen' ? 'Contoh: 50' : 'Contoh: 1500000'"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="modal-label">Kuota Penerima</label>
                                    <input
                                        type="number"
                                        v-model="form.kuota"
                                        class="modal-input"
                                        min="0"
                                        placeholder="0 = Tanpa Batas"
                                    />
                                </div>
                            </div>

                            <div class="modal-grid-2">
                                <div>
                                    <label class="modal-label">Komponen Biaya Terkait</label>
                                    <select v-model="form.komponen_biaya_id" class="modal-input">
                                        <option value="">Semua Komponen UKT</option>
                                        <option v-for="k in komponens" :key="k.id" :value="k.id">{{ k.kode }} - {{ k.nama }}</option>
                                    </select>
                                </div>
                                <div style="display:flex;align-items:center;gap:0.5rem;padding-top:1.5rem;">
                                    <label class="toggle-checkbox-wrap">
                                        <input type="checkbox" v-model="form.status_aktif" class="checkbox-input" />
                                        <span class="toggle-text">Status Program Aktif</span>
                                    </label>
                                </div>
                            </div>

                            <div class="modal-grid-2">
                                <div>
                                    <label class="modal-label">Tanggal Buka Pendaftaran</label>
                                    <input type="date" v-model="form.tanggal_buka" class="modal-input" />
                                </div>
                                <div>
                                    <label class="modal-label">Tanggal Tutup Pendaftaran</label>
                                    <input type="date" v-model="form.tanggal_tutup" class="modal-input" />
                                </div>
                            </div>

                            <div>
                                <label class="modal-label">Deskripsi & Syarat Beasiswa</label>
                                <textarea v-model="form.deskripsi" class="modal-input" rows="2" placeholder="Catatan atau kriteria persyaratan penerima..."></textarea>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="solid-btn btn-white-border" @click="closeModal">Batal</button>
                            <button type="submit" class="solid-btn btn-indigo-solid">
                                <i class="fas fa-save"></i> {{ editMode ? 'Simpan Perubahan' : 'Tambah Beasiswa' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Hapus Beasiswa -->
            <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
                <div class="modal-card" style="max-width:440px;">
                    <div class="modal-header">
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <span style="width:36px;height:36px;border-radius:0.5rem;background:#fee2e2;color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:1.125rem;">
                                <i class="fas fa-trash-alt"></i>
                            </span>
                            <div>
                                <h4 style="margin:0;font-size:1rem;font-weight:700;color:#991b1b;">Konfirmasi Hapus</h4>
                                <p style="margin:0;font-size:0.75rem;color:#64748b;">Tindakan ini tidak dapat dibatalkan</p>
                            </div>
                        </div>
                        <button class="modal-close-btn" @click="showDeleteModal = false">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p style="margin:0 0 0.75rem;color:#334155;font-size:0.875rem;line-height:1.5;">
                            Apakah Anda yakin ingin menghapus data beasiswa <strong>{{ itemToDelete?.nama_beasiswa }}</strong> ({{ itemToDelete?.kode }})?
                        </p>
                        <div class="solid-notice" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>Pastikan tidak ada mahasiswa penerima yang sedang aktif pada program beasiswa ini.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="solid-btn btn-white-border" @click="showDeleteModal = false">Batal</button>
                        <button type="button" class="solid-btn btn-danger-solid" @click="executeDelete">
                            <i class="fas fa-trash-alt"></i> Ya, Hapus Beasiswa
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
    margin-bottom: 1.25rem;
}
.filter-grid {
    display: grid;
    grid-template-columns: minmax(200px, 1.5fr) minmax(160px, 1.2fr) minmax(130px, 1fr) auto;
    gap: 0.75rem;
    align-items: center;
}
@media (max-width: 900px) {
    .filter-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 640px) {
    .filter-grid {
        grid-template-columns: 1fr;
    }
}
.search-box-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.search-icon {
    position: absolute;
    left: 0.875rem;
    color: #94a3b8;
    font-size: 0.8125rem;
}
.search-input {
    padding-left: 2.25rem !important;
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
}
.filter-input:focus {
    border-color: #4f46e5;
}
.filter-actions {
    display: flex;
    gap: 0.5rem;
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
.badge-solid-info {
    background: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
}
.badge-solid-primary {
    background: #f5f3ff;
    color: #6d28d9;
    border: 1px solid #ddd6fe;
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
.btn-penerima {
    width: auto;
    padding: 0 0.5rem;
    gap: 0.25rem;
    background: #f0fdf4;
    color: #166534;
    border-color: #bbf7d0;
}
.btn-penerima:hover {
    background: #dcfce7;
}
.btn-pencairan {
    background: #f0f9ff;
    color: #0369a1;
    border-color: #bae6fd;
}
.btn-pencairan:hover {
    background: #e0f2fe;
}
.btn-toggle-on {
    background: #ecfdf5;
    color: #059669;
    border-color: #a7f3d0;
}
.btn-toggle-off {
    background: #f1f5f9;
    color: #64748b;
    border-color: #cbd5e1;
}
.btn-edit {
    background: #eff6ff;
    color: #1d4ed8;
    border-color: #bfdbfe;
}
.btn-edit:hover {
    background: #dbeafe;
}
.btn-delete {
    background: #fef2f2;
    color: #b91c1c;
    border-color: #fecaca;
}
.btn-delete:hover {
    background: #fee2e2;
}

/* Pagination */
.pagination-footer {
    padding: 0.875rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #f1f5f9;
    flex-wrap: wrap;
    gap: 0.75rem;
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
.modal-card-lg {
    max-width: 620px;
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
.modal-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}
.modal-grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 0.75rem;
}
@media (max-width: 600px) {
    .modal-grid-2, .modal-grid-3 {
        grid-template-columns: 1fr;
    }
}
.modal-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 0.375rem;
}
.modal-input {
    width: 100%;
    padding: 0.5625rem 0.875rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    outline: none;
    background: #ffffff;
    color: #1e293b;
    box-sizing: border-box;
}
.modal-input:focus {
    border-color: #4f46e5;
}
.toggle-checkbox-wrap {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}
.checkbox-input {
    width: 1rem;
    height: 1rem;
    accent-color: #4f46e5;
    cursor: pointer;
}
.toggle-text {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #1e293b;
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
</style>
