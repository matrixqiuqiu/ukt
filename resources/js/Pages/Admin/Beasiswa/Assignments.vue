<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { formatRupiah } from '@/utils';

const props = defineProps({
    beasiswa: Object,
    assignments: Object,
});

const showRevokeModal = ref(false);
const revokeId = ref(null);
const showSyncModal = ref(false);
const showConfirmAddModal = ref(false);

const confirmRevoke = (id) => {
    revokeId.value = id;
    showRevokeModal.value = true;
};

const executeRevoke = () => {
    if (!revokeId.value) return;
    router.delete(route('admin.beasiswa.revoke', [props.beasiswa.id, revokeId.value]), {
        preserveScroll: true,
        onSuccess: () => {
            showRevokeModal.value = false;
            revokeId.value = null;
        }
    });
};

const syncing = ref(false);
const promptSyncTagihan = () => {
    showSyncModal.value = true;
};

const executeSyncTagihan = () => {
    showSyncModal.value = false;
    syncing.value = true;
    router.post(route('admin.beasiswa.sync-tagihan', props.beasiswa.id), {}, {
        preserveScroll: true,
        onFinish: () => syncing.value = false,
    });
};

// Modal + Centang Bulk Assign
const showModal = ref(false);
const search = ref('');
const jurusan = ref('');
const angkatan = ref('');
const loading = ref(false);
const result = ref({ data: [], links: [], total: 0, from: 0, to: 0 });
const selected = ref(new Set());
const page = ref(1);

const fetchMahasiswa = async (p = 1) => {
    loading.value = true;
    page.value = p;
    try {
        const params = new URLSearchParams({ page: p });
        if (search.value) params.append('search', search.value);
        if (jurusan.value) params.append('jurusan', jurusan.value);
        if (angkatan.value) params.append('angkatan', angkatan.value);
        const res = await fetch(route('admin.beasiswa.search-mahasiswa', props.beasiswa.id) + '?' + params.toString(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();
        result.value = json;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const openModal = () => {
    showModal.value = true;
    selected.value = new Set();
    search.value = '';
    jurusan.value = '';
    angkatan.value = '';
    fetchMahasiswa(1);
};
const closeModal = () => showModal.value = false;

let debounce = null;
watch(search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => fetchMahasiswa(1), 400);
});

const toggleOne = (nim) => {
    const s = new Set(selected.value);
    if (s.has(nim)) s.delete(nim);
    else s.add(nim);
    selected.value = s;
};

const toggleAll = (e) => {
    const s = new Set(selected.value);
    const nims = result.value.data.map(m => m.nim);
    if (e.target.checked) nims.forEach(n => s.add(n));
    else nims.forEach(n => s.delete(n));
    selected.value = s;
};

const isAllChecked = () => result.value.data.length > 0 && result.value.data.every(m => selected.value.has(m.nim));

const promptBulkAdd = () => {
    if (selected.value.size === 0) return;
    showConfirmAddModal.value = true;
};

const executeBulkAdd = () => {
    showConfirmAddModal.value = false;
    router.post(route('admin.beasiswa.assign-bulk', props.beasiswa.id), { nims: Array.from(selected.value) }, {
        preserveScroll: true,
        onSuccess: () => { closeModal(); },
    });
};

const goPage = (url) => {
    if (!url) return;
    const u = new URL(url);
    const p = u.searchParams.get('page') || 1;
    fetchMahasiswa(p);
};
</script>

<template>
    <Head :title="`Penerima ${beasiswa.nama_beasiswa}`" />
    <AuthenticatedLayout>
        <template #header>
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div>
                    <h2 class="page-heading" style="font-size:1.375rem;font-weight:700;color:#0f172a;margin-bottom:0.25rem;">
                        Penerima Beasiswa: {{ beasiswa.nama_beasiswa }}
                    </h2>
                    <p style="font-size:0.875rem;color:#64748b;margin:0;">
                        Kode: <strong style="font-family:monospace;color:#0f172a;">{{ beasiswa.kode }}</strong> &bull; Kuota: {{ beasiswa.terpakai }}/{{ beasiswa.kuota || '∞' }} Mahasiswa
                    </p>
                </div>
                <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                    <a :href="route('admin.beasiswa.penerima.export', beasiswa.id)" class="solid-btn btn-white-border" title="Export Excel">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a :href="route('admin.beasiswa.penerima.export-pdf', beasiswa.id)" class="solid-btn btn-white-border" title="Export PDF">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <button class="solid-btn btn-white-border" @click="promptSyncTagihan" :disabled="syncing" title="Sinkronkan Diskon ke Tagihan UKT">
                        <i :class="syncing ? 'fas fa-spinner fa-spin' : 'fas fa-sync-alt'"></i> {{ syncing ? 'Menyinkronkan...' : 'Sinkron Tagihan' }}
                    </button>
                    <button class="solid-btn btn-indigo-solid" @click="openModal">
                        <i class="fas fa-user-plus"></i> Tambah Penerima
                    </button>
                    <Link :href="route('admin.beasiswa.index')" class="solid-btn btn-white-border">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </Link>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">
                <!-- Info Status Card -->
                <div v-if="beasiswa.tahunAkademik" class="solid-notice solid-notice-info" style="margin-bottom:1.25rem;">
                    <i class="fas fa-info-circle" style="font-size:1.125rem;"></i>
                    <div>
                        Program ini terikat pada periode <strong>{{ beasiswa.tahunAkademik.nama }} {{ beasiswa.semester === 1 ? 'Ganjil' : 'Genap' }}</strong>.
                        Fitur <strong>Sinkron Tagihan</strong> akan otomatis memotong tagihan mahasiswa yang aktif pada periode semester tersebut.
                    </div>
                </div>
                <div v-else class="solid-notice solid-notice-success" style="margin-bottom:1.25rem;">
                    <i class="fas fa-info-circle" style="font-size:1.125rem;"></i>
                    <div>
                        Program beasiswa ini bersifat <strong>Berlaku Umum</strong> (Lintas Periode).
                        Fitur <strong>Sinkron Tagihan</strong> akan memotong tagihan UKT aktif terbaru tiap mahasiswa.
                    </div>
                </div>

                <!-- Table Card -->
                <div class="data-card">
                    <div style="padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;">
                        <div style="font-weight:700;color:#0f172a;font-size:0.9375rem;display:flex;align-items:center;gap:0.5rem;">
                            <i class="fas fa-users" style="color:#4f46e5;"></i> Daftar Mahasiswa Penerima
                        </div>
                        <span style="font-size:0.8125rem;font-weight:600;color:#64748b;">
                            Total: <strong style="color:#0f172a;">{{ assignments.total }}</strong> mahasiswa
                        </span>
                    </div>

                    <div v-if="assignments.data && assignments.data.length > 0">
                        <div class="table-responsive">
                            <table class="solid-table">
                                <thead>
                                    <tr>
                                        <th style="width:40px;text-align:center;">No</th>
                                        <th>NIM</th>
                                        <th>Nama Mahasiswa</th>
                                        <th>Tagihan Terkait</th>
                                        <th>Diskon Diterapkan</th>
                                        <th style="text-align:center;">Status Sinkron</th>
                                        <th style="text-align:center;">Status Approval</th>
                                        <th style="width:90px;text-align:center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(a, i) in assignments.data" :key="a.id">
                                        <td style="text-align:center;color:#64748b;font-size:0.8125rem;">
                                            {{ (assignments.from || 1) + i }}
                                        </td>
                                        <td>
                                            <span style="font-family:monospace;font-weight:700;color:#0f172a;background:#f1f5f9;padding:0.2rem 0.45rem;border-radius:0.375rem;font-size:0.8125rem;">
                                                {{ a.mahasiswa?.nim }}
                                            </span>
                                            <div style="font-size:0.75rem;color:#64748b;margin-top:0.125rem;">
                                                {{ a.mahasiswa?.jurusan }}
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight:700;color:#0f172a;font-size:0.875rem;">
                                                {{ a.mahasiswa?.nama_lengkap }}
                                            </div>
                                            <div style="font-size:0.75rem;color:#64748b;">
                                                Angkatan {{ a.mahasiswa?.angkatan || '-' }}
                                            </div>
                                        </td>
                                        <td>
                                            <div v-if="a.tagihan">
                                                <div style="font-weight:600;font-size:0.8125rem;color:#1e293b;">
                                                    {{ a.tagihan.tahun_akademik }} ({{ a.tagihan.semester }})
                                                </div>
                                                <div style="font-size:0.75rem;color:#64748b;">
                                                    {{ formatRupiah(a.tagihan.nominal) }} &bull;
                                                    <span :class="a.tagihan.status === 'sudah_dibayar' ? 'badge-solid-success' : 'badge-solid-warning'" class="solid-badge" style="font-size:0.6875rem;padding:0.1rem 0.35rem;">
                                                        {{ a.tagihan.status === 'sudah_dibayar' ? 'Lunas' : 'Belum Lunas' }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div v-else style="color:#dc2626;font-size:0.75rem;display:flex;align-items:center;gap:0.25rem;">
                                                <i class="fas fa-exclamation-circle"></i> Belum ada tagihan aktif
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-weight:700;color:#16a34a;font-size:0.875rem;">
                                                {{ formatRupiah(a.diskon_diterapkan || 0) }}
                                            </span>
                                        </td>
                                        <td style="text-align:center;">
                                            <span v-if="a.tagihan && Number(a.diskon_diterapkan) > 0" class="solid-badge badge-solid-success">
                                                <i class="fas fa-check-circle"></i> Terpotong
                                            </span>
                                            <span v-else-if="a.tagihan" class="solid-badge badge-solid-warning">
                                                <i class="fas fa-clock"></i> Perlu Sinkron
                                            </span>
                                            <span v-else class="solid-badge badge-solid-danger">
                                                <i class="fas fa-times-circle"></i> Tagihan Nihil
                                            </span>
                                        </td>
                                        <td style="text-align:center;">
                                            <span class="solid-badge" :class="a.status === 'disetujui' ? 'badge-solid-success' : 'badge-solid-info'">
                                                {{ a.status || 'Aktif' }}
                                            </span>
                                        </td>
                                        <td style="text-align:center;">
                                            <button
                                                class="action-btn-custom btn-delete"
                                                @click="confirmRevoke(a.id)"
                                                title="Cabut Beasiswa Mahasiswa"
                                            >
                                                <i class="fas fa-user-minus"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Footer -->
                        <div class="pagination-footer">
                            <span style="font-size:0.8125rem;color:#64748b;">
                                Menampilkan <strong style="color:#0f172a;">{{ assignments.from || 1 }}-{{ assignments.to || assignments.total }}</strong> dari <strong style="color:#0f172a;">{{ assignments.total }}</strong> mahasiswa
                            </span>
                            <div class="pagination-btns">
                                <template v-for="link in assignments.links" :key="link.label">
                                    <span v-if="!link.url" class="p-btn p-disabled" v-html="link.label"></span>
                                    <Link v-else :href="link.url" class="p-btn" :class="{ 'p-active': link.active }" v-html="link.label" preserve-state />
                                </template>
                            </div>
                        </div>
                    </div>

                    <div v-else style="text-align:center;padding:4rem 2rem;color:#64748b;">
                        <i class="fas fa-users-slash" style="font-size:2.5rem;color:#cbd5e1;margin-bottom:0.75rem;display:block;"></i>
                        <h4 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0 0 0.25rem;">Belum Ada Mahasiswa Penerima</h4>
                        <p style="font-size:0.8125rem;color:#64748b;margin:0 0 1rem;">Gunakan tombol "Tambah Penerima" di atas untuk mendaftarkan mahasiswa ke program ini.</p>
                        <button @click="openModal" class="solid-btn btn-indigo-solid" style="font-size:0.8125rem;">
                            <i class="fas fa-user-plus"></i> Tambah Penerima Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teleport Modals -->
        <Teleport to="body">
            <!-- Modal Pilih Mahasiswa (Bulk) -->
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                <div class="modal-card modal-card-lg" style="max-width:880px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;">
                    <div class="modal-header">
                        <div style="display:flex;align-items:center;gap:0.625rem;">
                            <span style="width:36px;height:36px;border-radius:0.5rem;background:#e0e7ff;color:#4338ca;display:flex;align-items:center;justify-content:center;font-size:1.125rem;">
                                <i class="fas fa-user-plus"></i>
                            </span>
                            <div>
                                <h4 style="margin:0;font-size:1rem;font-weight:700;color:#0f172a;">Pilih Mahasiswa Penerima</h4>
                                <p style="margin:0;font-size:0.75rem;color:#64748b;">Cari dan centang mahasiswa yang berhak menerima beasiswa ini</p>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <span class="solid-badge badge-solid-primary">{{ selected.size }} Terpilih</span>
                            <button class="modal-close-btn" @click="closeModal">&times;</button>
                        </div>
                    </div>

                    <!-- Search & Filter Bar -->
                    <div style="padding:0.75rem 1.25rem;border-bottom:1px solid #e2e8f0;display:grid;grid-template-columns:1fr 180px 140px;gap:0.5rem;align-items:center;background:#f8fafc;">
                        <div class="search-box-wrap">
                            <i class="fas fa-search search-icon"></i>
                            <input
                                type="search"
                                class="filter-input search-input"
                                v-model="search"
                                placeholder="Cari NIM atau Nama..."
                            />
                        </div>
                        <input v-model="jurusan" class="filter-input" placeholder="Filter Program Studi" @keyup.enter="fetchMahasiswa(1)" />
                        <input v-model="angkatan" class="filter-input" placeholder="Tahun Angkatan" @keyup.enter="fetchMahasiswa(1)" />
                    </div>

                    <!-- List Mahasiswa -->
                    <div style="flex:1;overflow-y:auto;min-height:260px;">
                        <div v-if="loading" style="padding:3rem;text-align:center;color:#64748b;">
                            <i class="fas fa-spinner fa-spin" style="font-size:1.75rem;color:#4f46e5;margin-bottom:0.5rem;display:block;"></i>
                            <span style="font-weight:600;font-size:0.875rem;">Memuat daftar mahasiswa...</span>
                        </div>
                        <div v-else-if="result.data.length === 0" style="padding:3rem;text-align:center;color:#64748b;">
                            <i class="fas fa-user-slash" style="font-size:2rem;color:#cbd5e1;margin-bottom:0.5rem;display:block;"></i>
                            <div style="font-weight:600;font-size:0.875rem;color:#1e293b;">Tidak ada data mahasiswa ditemukan</div>
                            <div style="font-size:0.75rem;color:#64748b;">Coba ubah kata kunci pencarian atau filter angkatan.</div>
                        </div>
                        <div v-else class="table-responsive">
                            <table class="solid-table">
                                <thead>
                                    <tr>
                                        <th style="width:40px;text-align:center;">
                                            <input type="checkbox" :checked="isAllChecked()" @change="toggleAll" class="checkbox-input" />
                                        </th>
                                        <th>NIM</th>
                                        <th>Nama Lengkap</th>
                                        <th>Program Studi</th>
                                        <th style="text-align:center;">Angkatan</th>
                                        <th style="text-align:center;">Sem.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="m in result.data"
                                        :key="m.id"
                                        :style="selected.has(m.nim) ? 'background:#eff6ff;' : ''"
                                        @click="toggleOne(m.nim)"
                                        style="cursor:pointer;"
                                    >
                                        <td style="text-align:center;" @click.stop>
                                            <input type="checkbox" :checked="selected.has(m.nim)" @change="toggleOne(m.nim)" class="checkbox-input" />
                                        </td>
                                        <td>
                                            <span style="font-family:monospace;font-weight:700;color:#0f172a;background:#f1f5f9;padding:0.2rem 0.4rem;border-radius:0.375rem;font-size:0.8125rem;">
                                                {{ m.nim }}
                                            </span>
                                        </td>
                                        <td style="font-weight:600;color:#0f172a;">{{ m.nama_lengkap }}</td>
                                        <td style="color:#334155;font-size:0.8125rem;">{{ m.jurusan }}</td>
                                        <td style="text-align:center;color:#334155;font-size:0.8125rem;">{{ m.angkatan }}</td>
                                        <td style="text-align:center;">
                                            <span style="display:inline-block;padding:0.15rem 0.45rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.375rem;font-size:0.75rem;font-weight:600;">
                                                {{ m.semester_hitung ?? m.semester }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer" style="justify-content:space-between;flex-wrap:wrap;background:#f8fafc;">
                        <div style="font-size:0.8125rem;color:#64748b;">
                            <span v-if="result.total">Menampilkan {{ result.from }}-{{ result.to }} dari {{ result.total }} mahasiswa</span>
                            <span class="solid-badge badge-solid-success" style="margin-left:0.5rem;">{{ selected.size }} terpilih</span>
                        </div>
                        <div style="display:flex;gap:0.5rem;align-items:center;">
                            <div v-if="result.links" class="pagination-btns" style="margin:0;">
                                <template v-for="link in result.links" :key="link.label">
                                    <span v-if="!link.url" class="p-btn p-disabled" v-html="link.label"></span>
                                    <a v-else href="#" class="p-btn" :class="{ 'p-active': link.active }" v-html="link.label" @click.prevent="goPage(link.url)"></a>
                                </template>
                            </div>
                            <button class="solid-btn btn-white-border" @click="closeModal">Batal</button>
                            <button class="solid-btn btn-indigo-solid" :disabled="selected.size === 0" @click="promptBulkAdd">
                                <i class="fas fa-plus"></i> Tambahkan ({{ selected.size }})
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Konfirmasi Cabut -->
            <div v-if="showRevokeModal" class="modal-overlay" @click.self="showRevokeModal = false">
                <div class="modal-card" style="max-width:440px;">
                    <div class="modal-header">
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <span style="width:36px;height:36px;border-radius:0.5rem;background:#fee2e2;color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:1.125rem;">
                                <i class="fas fa-exclamation-triangle"></i>
                            </span>
                            <div>
                                <h4 style="margin:0;font-size:1rem;font-weight:700;color:#991b1b;">Konfirmasi Cabut</h4>
                                <p style="margin:0;font-size:0.75rem;color:#64748b;">Hapus hak penerima beasiswa</p>
                            </div>
                        </div>
                        <button class="modal-close-btn" @click="showRevokeModal = false">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p style="margin:0 0 0.75rem;color:#334155;font-size:0.875rem;line-height:1.5;">
                            Apakah Anda yakin ingin mencabut beasiswa untuk mahasiswa ini? Tagihan terkait tidak akan lagi mendapatkan pemotongan beasiswa.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="solid-btn btn-white-border" @click="showRevokeModal = false">Batal</button>
                        <button type="button" class="solid-btn btn-danger-solid" @click="executeRevoke">
                            <i class="fas fa-trash-alt"></i> Ya, Cabut Beasiswa
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Konfirmasi Sinkron Tagihan -->
            <div v-if="showSyncModal" class="modal-overlay" @click.self="showSyncModal = false">
                <div class="modal-card" style="max-width:460px;">
                    <div class="modal-header">
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <span style="width:36px;height:36px;border-radius:0.5rem;background:#e0e7ff;color:#4338ca;display:flex;align-items:center;justify-content:center;font-size:1.125rem;">
                                <i class="fas fa-sync-alt"></i>
                            </span>
                            <div>
                                <h4 style="margin:0;font-size:1rem;font-weight:700;color:#0f172a;">Sinkronkan Tagihan</h4>
                                <p style="margin:0;font-size:0.75rem;color:#64748b;">Terapkan diskon beasiswa ke tagihan UKT aktif</p>
                            </div>
                        </div>
                        <button class="modal-close-btn" @click="showSyncModal = false">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p style="margin:0 0 0.75rem;color:#334155;font-size:0.875rem;line-height:1.5;">
                            Sinkronkan tagihan untuk semua penerima beasiswa <strong>{{ beasiswa.nama_beasiswa }}</strong> ({{ beasiswa.kode }})?
                        </p>
                        <div class="solid-notice solid-notice-info">
                            <i class="fas fa-info-circle"></i>
                            <div>Tagihan mahasiswa yang sesuai periode akan otomatis dipotong sesuai skema beasiswa.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="solid-btn btn-white-border" @click="showSyncModal = false">Batal</button>
                        <button type="button" class="solid-btn btn-indigo-solid" @click="executeSyncTagihan">
                            <i class="fas fa-play"></i> Jalankan Sinkronisasi
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Konfirmasi Bulk Add -->
            <div v-if="showConfirmAddModal" class="modal-overlay" @click.self="showConfirmAddModal = false">
                <div class="modal-card" style="max-width:440px;">
                    <div class="modal-header">
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <span style="width:36px;height:36px;border-radius:0.5rem;background:#e0e7ff;color:#4338ca;display:flex;align-items:center;justify-content:center;font-size:1.125rem;">
                                <i class="fas fa-user-plus"></i>
                            </span>
                            <div>
                                <h4 style="margin:0;font-size:1rem;font-weight:700;color:#0f172a;">Konfirmasi Tambah</h4>
                                <p style="margin:0;font-size:0.75rem;color:#64748b;">Daftarkan mahasiswa terpilih</p>
                            </div>
                        </div>
                        <button class="modal-close-btn" @click="showConfirmAddModal = false">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p style="margin:0;color:#334155;font-size:0.875rem;">
                            Tambahkan <strong>{{ selected.size }} mahasiswa</strong> terpilih ke dalam program beasiswa <strong>{{ beasiswa.nama_beasiswa }}</strong>?
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="solid-btn btn-white-border" @click="showConfirmAddModal = false">Batal</button>
                        <button type="button" class="solid-btn btn-indigo-solid" @click="executeBulkAdd">
                            <i class="fas fa-check"></i> Ya, Tambahkan
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
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
    box-sizing: border-box;
}
.filter-input:focus {
    border-color: #4f46e5;
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
    max-width: 720px;
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
.solid-notice-success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
}
.checkbox-input {
    width: 1rem;
    height: 1rem;
    accent-color: #4f46e5;
    cursor: pointer;
}
</style>
