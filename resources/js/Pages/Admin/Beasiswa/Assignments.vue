<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({ beasiswa: Object, assignments: Object });

const revoke = (id) => { if(confirm('Cabut penerima?')) router.delete(route('admin.beasiswa.revoke', [props.beasiswa.id, id]), { preserveScroll:true }); };

const syncing = ref(false);
const syncTagihan = () => {
    if (!confirm(`Sinkronkan tagihan untuk semua penerima beasiswa ${props.beasiswa.kode}? Tagihan yang sesuai periode akan dipotong diskon.`)) return;
    syncing.value = true;
    router.post(route('admin.beasiswa.sync-tagihan', props.beasiswa.id), {}, {
        preserveScroll: true,
        onFinish: () => syncing.value = false,
    });
};

// Modal + centang
const showModal = ref(false);
const search = ref('');
const jurusan = ref('');
const angkatan = ref('');
const loading = ref(false);
const result = ref({ data:[], links:[], total:0, from:0, to:0 });
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
        const res = await fetch(route('admin.beasiswa.search-mahasiswa', props.beasiswa.id) + '?' + params.toString(), { headers: { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' } });
        const json = await res.json();
        // Laravel paginate JSON has data/links etc
        result.value = json;
    } catch(e) {
        console.error(e);
    } finally { loading.value = false; }
};

const openModal = () => { showModal.value = true; selected.value = new Set(); search.value=''; jurusan.value=''; angkatan.value=''; fetchMahasiswa(1); };
const closeModal = () => showModal.value = false;

let debounce = null;
watch(search, () => { clearTimeout(debounce); debounce = setTimeout(()=>fetchMahasiswa(1), 400); });

const toggleOne = (nim) => {
    const s = new Set(selected.value);
    if (s.has(nim)) s.delete(nim); else s.add(nim);
    selected.value = s;
};
const toggleAll = (e) => {
    const s = new Set(selected.value);
    const nims = result.value.data.map(m=>m.nim);
    if (e.target.checked) nims.forEach(n=>s.add(n)); else nims.forEach(n=>s.delete(n));
    selected.value = s;
};
const isAllChecked = () => result.value.data.length>0 && result.value.data.every(m=>selected.value.has(m.nim));

const submitBulk = () => {
    if (selected.value.size===0) return alert('Pilih minimal satu mahasiswa');
    if (!confirm(`Tambahkan ${selected.value.size} mahasiswa terpilih ke beasiswa ${props.beasiswa.kode}?`)) return;
    router.post(route('admin.beasiswa.assign-bulk', props.beasiswa.id), { nims: Array.from(selected.value) }, {
        preserveScroll:true,
        onSuccess:()=>{ closeModal(); },
    });
};

const goPage = (url) => {
    if(!url) return;
    const u = new URL(url);
    const p = u.searchParams.get('page') || 1;
    fetchMahasiswa(p);
};
</script>

<template>
    <Head :title="`Penerima ${beasiswa.nama_beasiswa}`" />
    <AuthenticatedLayout>
        <template #header><h2 class="page-heading">Beasiswa — Penerima: {{ beasiswa.nama_beasiswa }} ({{ beasiswa.kode }})</h2></template>
        <div class="page-body"><div class="container-xl">
            <div class="custom-card" style="margin-bottom:1.25rem;">
                <div class="card-body" style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;justify-content:space-between;">
                    <div>
                        <div style="font-weight:700;">{{ beasiswa.nama_beasiswa }} <span class="m-badge m-badge-info">{{ beasiswa.jenisBeasiswa?.nama || beasiswa.jenis }}</span>
                            <span v-if="beasiswa.tahunAkademik" class="m-badge m-badge-secondary" style="margin-left:0.5rem;">{{ beasiswa.tahunAkademik.nama }} {{ beasiswa.semester===1?'Ganjil':'Genap' }}</span>
                            <span v-else class="m-badge m-badge-secondary" style="margin-left:0.5rem;">Umum</span>
                        </div>
                        <div style="font-size:0.8125rem;color:var(--gray-600);">Kuota {{ beasiswa.terpakai }}/{{ beasiswa.kuota || '∞' }} · Sisa {{ Math.max(0, (beasiswa.kuota||0)-beasiswa.terpakai) }} · {{ beasiswa.tipe_diskon }} {{ beasiswa.nilai_diskon }}</div>
                    </div>
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                        <a :href="route('admin.beasiswa.penerima.export', beasiswa.id)" class="m-btn m-btn-sm" style="background:#10b981;color:#fff;"><i class="fas fa-file-excel"></i> Excel</a>
                        <a :href="route('admin.beasiswa.penerima.export-pdf', beasiswa.id)" class="m-btn m-btn-sm" style="background:#ef4444;color:#fff;"><i class="fas fa-file-pdf"></i> PDF</a>
                        <button class="m-btn m-btn-sm" style="background:#0ea5e9;color:#fff;" @click="syncTagihan" :disabled="syncing"><i :class="syncing ? 'fas fa-spinner fa-spin' : 'fas fa-sync'"></i> {{ syncing ? 'Sinkron...' : 'Sinkron Tagihan' }}</button>
                        <button class="m-btn m-btn-primary" @click="openModal"><i class="fas fa-user-plus"></i> Tambah Penerima</button>
                        <Link :href="route('admin.beasiswa.index')" class="m-btn m-btn-secondary">Kembali</Link>
                    </div>
                </div>
            </div>
            <div v-if="beasiswa.tahunAkademik" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:0.75rem;padding:0.75rem 1rem;margin-bottom:1rem;font-size:0.8125rem;color:#1e40af;">
                <i class="fas fa-info-circle"></i> Beasiswa ini terikat periode <b>{{ beasiswa.tahunAkademik.nama }} {{ beasiswa.semester===1?'Ganjil':'Genap' }}</b> — sinkron akan cari tagihan mahasiswa untuk periode tersebut dan potong diskon otomatis.
            </div>
            <div v-else style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:0.75rem;padding:0.75rem 1rem;margin-bottom:1rem;font-size:0.8125rem;color:#166534;">
                <i class="fas fa-info-circle"></i> Beasiswa <b>Umum</b> — sinkron akan pakai tagihan terbaru (belum lunas) tiap mahasiswa.
            </div>

            <div class="custom-card">
                <div class="card-header"><h4>Daftar Penerima ({{ assignments.total }})</h4><span style="font-size:0.75rem;color:var(--gray-500);">Sinkron cek apakah tagihan sudah terpotong</span></div>
                <div class="card-body" style="padding:0;">
                    <div v-if="assignments.data.length" class="table-responsive">
                    <table class="m-data-table">
                        <thead><tr><th>NIM</th><th>Nama</th><th>Tagihan</th><th>Diskon</th><th>Sinkron</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <tr v-for="a in assignments.data" :key="a.id">
                                <td style="font-family:monospace;font-weight:600;">{{ a.mahasiswa?.nim }}<div style="font-size:0.65rem;color:var(--gray-500);">{{ a.mahasiswa?.jurusan }}</div></td>
                                <td>{{ a.mahasiswa?.nama_lengkap }}<div style="font-size:0.65rem;color:var(--gray-500);">Angk. {{ a.mahasiswa?.angkatan }}</div></td>
                                <td>
                                    <div v-if="a.tagihan">
                                        <div style="font-weight:600;font-size:0.8125rem;">{{ a.tagihan.tahun_akademik }} S{{ a.tagihan.semester }}</div>
                                        <div style="font-size:0.7rem;color:var(--gray-600);">Rp {{ Number(a.tagihan.nominal).toLocaleString('id-ID') }} · <span :class="a.tagihan.status==='sudah_dibayar'?'m-badge-success':'m-badge-warning'" class="m-badge" style="font-size:0.6rem;">{{ a.tagihan.status }}</span></div>
                                    </div>
                                    <div v-else style="color:#dc2626;font-size:0.75rem;"><i class="fas fa-exclamation-circle"></i> Belum ada tagihan</div>
                                </td>
                                <td>Rp {{ Number(a.diskon_diterapkan).toLocaleString('id-ID') }}</td>
                                <td>
                                    <span v-if="a.tagihan && Number(a.diskon_diterapkan)>0" class="m-badge m-badge-success"><i class="fas fa-check"></i> Sinkron</span>
                                    <span v-else-if="a.tagihan" class="m-badge m-badge-warning">Belum sinkron</span>
                                    <span v-else class="m-badge m-badge-danger">Menunggu tagihan</span>
                                </td>
                                <td><span class="m-badge" :class="a.status==='disetujui'?'m-badge-success':'m-badge-info'">{{ a.status }}</span></td>
                                <td><button class="m-btn m-btn-sm m-btn-danger" @click="revoke(a.id)"><i class="fas fa-times"></i> Cabut</button></td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                    <div v-else style="padding:2rem;text-align:center;color:var(--gray-500);">Belum ada penerima</div>
                    <div v-if="assignments.links" class="pagination-wrap">
                        <span class="pagination-info">Menampilkan {{ assignments.from }}-{{ assignments.to }} dari {{ assignments.total }}</span>
                        <div class="pagination">
                            <template v-for="link in assignments.links" :key="link.label">
                                <span v-if="!link.url" class="page-item disabled"><span class="page-link" v-html="link.label"></span></span>
                                <span v-else class="page-item" :class="{active:link.active}"><Link :href="link.url" class="page-link" v-html="link.label" /></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div></div>

        <!-- Modal Centang -->
        <Teleport to="body">
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.45);display:flex;align-items:center;justify-content:center;z-index:100;padding:1rem;">
                <div class="modal-box" style="background:#fff;border-radius:1rem;width:100%;max-width:880px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;">
                    <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--gray-200);display:flex;justify-content:space-between;align-items:center;gap:1rem;">
                        <div>
                            <h3 style="margin:0;font-weight:800;">Pilih Mahasiswa</h3>
                            <div style="font-size:0.75rem;color:var(--gray-600);">Centang beberapa mahasiswa lalu klik Tambahkan</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <span class="m-badge m-badge-info">{{ selected.size }} terpilih</span>
                            <button @click="closeModal" style="background:none;border:none;font-size:1.5rem;cursor:pointer;">×</button>
                        </div>
                    </div>

                    <!-- Search bar -->
                    <div style="padding:0.75rem 1.25rem;border-bottom:1px solid var(--gray-100);display:grid;grid-template-columns:1fr 160px 130px;gap:0.5rem;align-items:center;">
                        <div class="input-group input-group--search">
                            <span class="input-group__text"><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11.5" cy="11.5" r="9.5"/><path stroke-linecap="round" d="M18.5 18.5L22 22"/></g></svg></span>
                            <input type="search" class="input" v-model="search" placeholder="Cari NIM / Nama / Jurusan..." />
                        </div>
                        <input v-model="jurusan" class="form-control" placeholder="Filter Jurusan" @keyup.enter="fetchMahasiswa(1)" />
                        <input v-model="angkatan" class="form-control" placeholder="Angkatan" @keyup.enter="fetchMahasiswa(1)" />
                    </div>

                    <!-- List -->
                    <div style="flex:1;overflow:auto;">
                        <div v-if="loading" style="padding:2rem;text-align:center;color:var(--gray-500);"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>
                        <div v-else-if="result.data.length===0" style="padding:2rem;text-align:center;color:var(--gray-500);">Tidak ada mahasiswa ditemukan</div>
                        <div v-else class="table-responsive" style="border:0;">
                        <table class="m-data-table">
                            <thead><tr>
                                <th style="width:40px;"><input type="checkbox" :checked="isAllChecked()" @change="toggleAll" /></th>
                                <th>NIM</th><th>Nama</th><th>Jurusan</th><th>Angkatan</th><th>Sem.</th>
                            </tr></thead>
                            <tbody>
                                <tr v-for="m in result.data" :key="m.id" :class="{'row-active': selected.has(m.nim)}" @click="toggleOne(m.nim)" style="cursor:pointer;">
                                    <td @click.stop><input type="checkbox" :checked="selected.has(m.nim)" @change="toggleOne(m.nim)" /></td>
                                    <td style="font-family:monospace;font-weight:600;">{{ m.nim }}</td>
                                    <td>{{ m.nama_lengkap }}</td>
                                    <td>{{ m.jurusan }}</td>
                                    <td style="text-align:center;">{{ m.angkatan }}</td>
                                    <td style="text-align:center;"><span class="m-badge m-badge-secondary">{{ m.semester_hitung ?? m.semester }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div style="padding:0.75rem 1.25rem;border-top:1px solid var(--gray-200);display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
                        <div style="font-size:0.8125rem;color:var(--gray-600);">
                            <span v-if="result.total">Menampilkan {{ result.from }}-{{ result.to }} dari {{ result.total }}</span>
                            <span class="m-badge m-badge-success" style="margin-left:0.5rem;">{{ selected.size }} terpilih</span>
                        </div>
                        <div style="display:flex;gap:0.5rem;align-items:center;">
                            <div v-if="result.links" class="pagination" style="margin:0;">
                                <template v-for="link in result.links" :key="link.label">
                                    <span v-if="!link.url" class="page-item disabled"><span class="page-link" v-html="link.label"></span></span>
                                    <span v-else class="page-item" :class="{active:link.active}"><a href="#" class="page-link" v-html="link.label" @click.prevent="goPage(link.url)"></a></span>
                                </template>
                            </div>
                            <button class="m-btn m-btn-secondary" @click="closeModal">Batal</button>
                            <button class="m-btn m-btn-primary" :disabled="selected.size===0" @click="submitBulk"><i class="fas fa-plus"></i> Tambahkan ({{ selected.size }})</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.form-label{display:block;font-size:0.8125rem;font-weight:600;margin-bottom:0.375rem}
.form-control{width:100%;padding:0.625rem 0.75rem;border:1px solid var(--gray-300);border-radius:0.75rem;font-size:0.875rem}
.input-group{position:relative;display:flex;align-items:center;width:100%}
.input-group--search .input{width:100%;padding-left:2.25rem}
.input-group__text{position:absolute;left:0.875rem;top:50%;transform:translateY(-50%);color:var(--gray-400);pointer-events:none}
.table-responsive{overflow-x:auto}
.pagination-wrap{display:flex;justify-content:space-between;align-items:center;padding:1rem;border-top:1px solid var(--gray-100);flex-wrap:wrap;gap:1rem}
.row-active{background:#eef2ff !important}
</style>
