<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
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
const editMode = ref(false);
const editId = ref(null);
const form = ref({
    kode: '', nama_beasiswa: '', jenis: 'prestasi', jenis_beasiswa_id: '', sumber_dana: '', tahun_akademik_id: '', semester: '', tipe_diskon: 'persen', nilai_diskon: 0, komponen_biaya_id: '', kuota: 0, tanggal_buka: '', tanggal_tutup: '', deskripsi: '', status_aktif: true,
});

const doFilter = () => {
    const p = {};
    if (search.value) p.search = search.value;
    if (jenis.value) p.jenis = jenis.value;
    if (status.value) p.status = status.value;
    router.get(route('admin.beasiswa.index'), p, { preserveState:true });
};

const openCreate = () => {
    editMode.value = false; editId.value = null;
    form.value = { kode:'', nama_beasiswa:'', jenis:'prestasi', jenis_beasiswa_id:'', sumber_dana:'', tahun_akademik_id:'', semester:'', tipe_diskon:'persen', nilai_diskon:0, komponen_biaya_id:'', kuota:0, tanggal_buka:'', tanggal_tutup:'', deskripsi:'', status_aktif:true };
    showModal.value = true;
};
const openEdit = (row) => {
    editMode.value = true; editId.value = row.id;
    form.value = { kode:row.kode, nama_beasiswa:row.nama_beasiswa, jenis:row.jenis, jenis_beasiswa_id:row.jenis_beasiswa_id||'', sumber_dana:row.sumber_dana||'', tahun_akademik_id:row.tahun_akademik_id||'', semester:row.semester||'', tipe_diskon:row.tipe_diskon, nilai_diskon:row.nilai_diskon, komponen_biaya_id:row.komponen_biaya_id||'', kuota:row.kuota, tanggal_buka:row.tanggal_buka||'', tanggal_tutup:row.tanggal_tutup||'', deskripsi:row.deskripsi||'', status_aktif:row.status_aktif };
    showModal.value = true;
};
const closeModal = () => showModal.value = false;
const submit = () => {
    const url = editMode.value ? route('admin.beasiswa.update', editId.value) : route('admin.beasiswa.store');
    const method = editMode.value ? 'put' : 'post';
    router[method](url, form.value, { preserveScroll:true, onSuccess:()=>closeModal() });
};
const destroy = (row) => { if(confirm(`Hapus ${row.nama_beasiswa}?`)) router.delete(route('admin.beasiswa.destroy', row.id), { preserveScroll:true }); };
const toggle = (row) => router.post(route('admin.beasiswa.toggle', row.id), {}, { preserveScroll:true });

const diskonLabel = (row) => {
    if (row.tipe_diskon==='full') return 'Gratis 100%';
    if (row.tipe_diskon==='persen') return `${row.nilai_diskon}%`;
    return formatRupiah(row.nilai_diskon);
};

// Sinkron semester beasiswa dengan Tahun Akademik master (hindari dobel/bentrok: Tahun 2025/2026 Ganjil sudah imply semester 1)
watch(() => form.value.tahun_akademik_id, (newVal) => {
    if (!newVal) return; // Umum — biarkan semester bisa Umum
    const ta = props.tahunAkademiks.find(t => t.id == newVal);
    if (ta) {
        const flag = ta.semester === 'Genap' ? 2 : 1;
        if (!form.value.semester || form.value.semester !== flag) {
            form.value.semester = flag;
        }
    }
});
</script>

<template>
    <Head title="Beasiswa" />
    <AuthenticatedLayout>
        <template #header><h2 class="page-heading">Master Data — Beasiswa</h2></template>
        <div class="page-body"><div class="container-xl">
            <!-- Filter -->
            <div class="custom-card filter-card">
                <div class="card-body">
                    <div class="filter-grid">
                        <div class="input-group input-group--search">
                            <span class="input-group__text"><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11.5" cy="11.5" r="9.5"/><path stroke-linecap="round" d="M18.5 18.5L22 22"/></g></svg></span>
                            <input type="search" class="input" v-model="search" placeholder="Cari kode/nama beasiswa..." @keyup.enter="doFilter" />
                        </div>
                        <select v-model="jenis" @change="doFilter" class="form-control">
                            <option value="">Semua Jenis</option>
                            <option v-for="j in jenisBeasiswas" :key="j.id" :value="j.id">{{ j.kode }} - {{ j.nama }}</option>
                        </select>
                        <select v-model="status" @change="doFilter" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                        <div class="filter-actions">
                            <button class="m-btn m-btn-primary" @click="doFilter"><i class="fas fa-filter"></i> Filter</button>
                            <a :href="route('admin.beasiswa.export')" class="m-btn m-btn-secondary"><i class="fas fa-download"></i> Export</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="beasiswa-toolbar">
                <div class="toolbar-info">Total {{ beasiswas.total }} beasiswa — kuota & terpakai otomatis</div>
                <button class="m-btn m-btn-primary" @click="openCreate"><i class="fas fa-plus"></i> Tambah Beasiswa</button>
            </div>

            <div class="custom-card">
                <div class="card-header"><h4><i class="fas fa-graduation-cap" style="margin-right:0.5rem;"></i> Daftar Beasiswa</h4><span class="toolbar-info" style="font-size:0.75rem;color:var(--gray-500);">{{ beasiswas.total }} data</span></div>
                <div class="card-body" style="padding:0;">
                    <div v-if="beasiswas.data.length>0" class="table-responsive">
                    <table class="m-data-table">
                        <thead><tr>
                            <th>Kode</th><th>Nama Beasiswa</th><th>Jenis</th><th>Periode</th><th>Diskon</th><th>Kuota</th><th>Status</th><th style="width:90px;text-align:center;">Penerima</th><th style="width:100px;text-align:center;">Pencairan</th><th style="width:130px">Aksi</th>
                        </tr></thead>
                        <tbody>
                            <tr v-for="b in beasiswas.data" :key="b.id">
                                <td style="font-weight:700;font-family:monospace;">{{ b.kode }}</td>
                                <td>
                                    <div style="font-weight:600;">{{ b.nama_beasiswa }}</div>
                                    <div style="font-size:0.7rem;color:var(--gray-500);">{{ b.sumber_dana || '-' }}</div>
                                </td>
                                <td>
                                    <span class="m-badge m-badge-info" :title="b.jenis_beasiswa_id ? '' : b.jenis">{{ b.jenisBeasiswa ? b.jenisBeasiswa.nama : b.jenis }}</span>
                                    <div v-if="b.jenisBeasiswa" style="font-size:0.6rem;color:var(--gray-500);">{{ b.jenisBeasiswa.kode }}</div>
                                </td>
                                <td>
                                    <span v-if="b.tahun_akademik">{{ b.tahun_akademik.nama }} · {{ b.semester===1?'Ganjil':'Genap' }}</span>
                                    <span v-else style="color:var(--gray-500);">Umum</span>
                                </td>
                                <td><span class="m-badge" :class="b.tipe_diskon==='full'?'m-badge-success': b.tipe_diskon==='persen'?'m-badge-warning':'m-badge-info'">{{ diskonLabel(b) }}</span></td>
                                <td><span style="font-weight:700;">{{ b.terpakai }}/{{ b.kuota || '∞' }}</span><div style="font-size:0.65rem;color:var(--gray-500);">sisa {{ Math.max(0, (b.kuota||0)-b.terpakai) }}</div></td>
                                <td><span class="m-badge" :class="b.status_aktif?'m-badge-success':'m-badge-danger'">{{ b.status_aktif?'Aktif':'Nonaktif' }}</span></td>
                                <td style="text-align:center;">
                                    <Link :href="route('admin.beasiswa.assignments', b.id)" class="m-btn m-btn-sm m-btn-secondary" title="Kelola Penerima">
                                        <i class="fas fa-users"></i> {{ b.terpakai }}
                                    </Link>
                                </td>
                                <td style="text-align:center;">
                                    <Link v-if="b.sumber_dana==='eksternal'" :href="route('admin.beasiswa.pencairan.index', b.id)" class="m-btn m-btn-sm" style="background:#0ea5e9;color:#fff;" title="Pencairan Eksternal"><i class="fas fa-hand-holding-usd"></i> Kelola</Link>
                                    <span v-else style="color:var(--gray-400);font-size:0.75rem;">—</span>
                                </td>
                                <td>
                                    <div style="display:flex;gap:0.375rem;flex-wrap:wrap;">
                                        <button class="m-btn m-btn-sm" :class="b.status_aktif?'m-badge-warning':'m-badge-success'" @click="toggle(b)"><i :class="b.status_aktif?'fas fa-toggle-on':'fas fa-toggle-off'"></i></button>
                                        <button class="m-btn m-btn-sm m-btn-secondary" @click="openEdit(b)"><i class="fas fa-pen"></i></button>
                                        <button class="m-btn m-btn-sm m-btn-danger" @click="destroy(b)"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                    <div v-else style="text-align:center;padding:2.5rem;color:var(--gray-500);">Belum ada beasiswa</div>
                    <div v-if="beasiswas.links" class="pagination-wrap">
                        <span class="pagination-info">Menampilkan {{ beasiswas.from }}-{{ beasiswas.to }} dari {{ beasiswas.total }}</span>
                        <div class="pagination">
                            <template v-for="link in beasiswas.links" :key="link.label">
                                <span v-if="!link.url" class="page-item disabled"><span class="page-link" v-html="link.label"></span></span>
                                <span v-else class="page-item" :class="{active:link.active}"><Link :href="link.url" class="page-link" v-html="link.label" /></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div></div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;z-index:100;padding:1rem;">
                <div class="modal-box" style="background:#fff;border-radius:1rem;width:100%;max-width:640px;max-height:90vh;overflow:auto;">
                    <div style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--gray-200);display:flex;justify-content:space-between;align-items:center;">
                        <h3 style="margin:0;font-weight:700;">{{ editMode ? 'Edit Beasiswa' : 'Tambah Beasiswa' }}</h3>
                        <button @click="closeModal" style="background:none;border:none;font-size:1.25rem;cursor:pointer;">×</button>
                    </div>
                    <form @submit.prevent="submit" style="padding:1.5rem;display:grid;gap:1rem;">
                        <div class="modal-grid-2">
                            <div><label class="form-label">Kode *</label><input v-model="form.kode" class="form-control" required /></div>
                            <div><label class="form-label">Jenis Beasiswa *</label><select v-model="form.jenis_beasiswa_id" class="form-control" required><option value="">Pilih Jenis</option><option v-for="j in jenisBeasiswas" :key="j.id" :value="j.id">{{ j.kode }} - {{ j.nama }}</option></select><div style="font-size:0.65rem;color:var(--gray-500);margin-top:0.25rem;">Kelola di <a :href="route('admin.jenis-beasiswa.index')" style="color:var(--primary);">Master Jenis Beasiswa</a></div></div>
                        </div>
                        <div><label class="form-label">Nama Beasiswa *</label><input v-model="form.nama_beasiswa" class="form-control" required /></div>
                        <div class="modal-grid-2">
                            <div>
                                <label class="form-label">Sumber Dana *</label>
                                <select v-model="form.sumber_dana" class="form-control" required>
                                    <option value="">Pilih Sumber Dana</option>
                                    <option value="internal">Internal (Kampus)</option>
                                    <option value="eksternal">Eksternal</option>
                                    <option value="pemerintah">Pemerintah</option>
                                    <option value="kerjasama">Kerjasama</option>
                                </select>
                            </div>
                            <div><label class="form-label">Kuota (jumlah_mahasiswa) *</label><input v-model.number="form.kuota" type="number" min="0" class="form-control" required /></div>
                        </div>
                        <div class="modal-grid-3">
                            <div><label class="form-label">Tahun Akademik</label><select v-model="form.tahun_akademik_id" class="form-control"><option value="">Umum (semua periode)</option><option v-for="t in tahunAkademiks" :key="t.id" :value="t.id">{{ t.nama }} {{ t.semester }}</option></select><div style="font-size:0.65rem;color:var(--gray-500);margin-top:0.2rem;">Sinkron ke <a href="/admin/tahun-akademik" style="color:var(--primary);">Pengaturan Tahun Akademik</a></div></div>
                            <div><label class="form-label">Semester (1=Ganjil,2=Genap)</label><select v-model="form.semester" class="form-control" :disabled="!!form.tahun_akademik_id"><option value="">Umum</option><option :value="1">1 - Ganjil</option><option :value="2">2 - Genap</option></select><div v-if="form.tahun_akademik_id" style="font-size:0.65rem;color:var(--primary);margin-top:0.2rem;">Otomatis sinkron dengan Tahun Akademik terpilih</div><div v-else style="font-size:0.65rem;color:var(--gray-500);margin-top:0.2rem;">Kosongkan = berlaku semua semester</div></div>
                            <div><label class="form-label">Komponen</label><select v-model="form.komponen_biaya_id" class="form-control"><option value="">Semua komponen</option><option v-for="k in komponens" :key="k.id" :value="k.id">{{ k.nama }}</option></select></div>
                        </div>
                        <div class="modal-grid-2">
                            <div><label class="form-label">Tipe Diskon *</label><select v-model="form.tipe_diskon" class="form-control"><option value="persen">Persen (%)</option><option value="nominal">Nominal (Rp)</option><option value="full">Full Gratis</option></select></div>
                            <div><label class="form-label">Nilai Diskon *</label><input v-model.number="form.nilai_diskon" type="number" min="0" class="form-control" :disabled="form.tipe_diskon==='full'" /></div>
                        </div>
                        <div class="modal-grid-2">
                            <div><label class="form-label">Buka</label><input v-model="form.tanggal_buka" type="date" class="form-control" /></div>
                            <div><label class="form-label">Tutup</label><input v-model="form.tanggal_tutup" type="date" class="form-control" /></div>
                        </div>
                        <div><label class="form-label">Deskripsi</label><textarea v-model="form.deskripsi" class="form-control" rows="2"></textarea></div>
                        <label style="display:flex;gap:0.5rem;align-items:center;"><input type="checkbox" v-model="form.status_aktif" /> Aktif</label>
                        <div style="display:flex;gap:0.5rem;justify-content:flex-end;">
                            <button type="button" class="m-btn m-btn-secondary" @click="closeModal">Batal</button>
                            <button type="submit" class="m-btn m-btn-primary">{{ editMode ? 'Simpan' : 'Tambah' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.page-heading{font-size:1.5rem;font-weight:700;letter-spacing:-0.02em}
.filter-card{margin-bottom:1.25rem;overflow:hidden;border:1px solid var(--gray-200);border-radius:1rem;box-shadow:0 1px 3px rgba(0,0,0,0.05)}
.filter-card .card-body{padding:1rem 1.25rem}
.filter-grid{display:grid;grid-template-columns:1fr 160px 140px auto;gap:0.75rem;align-items:end}
.filter-actions{display:flex;gap:0.5rem;align-items:center}
.beasiswa-toolbar{display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:1rem;flex-wrap:wrap}
.toolbar-info{font-size:0.8125rem;color:var(--gray-600)}
.custom-card{overflow:hidden;border:1px solid var(--gray-200);border-radius:1rem;box-shadow:0 1px 3px rgba(0,0,0,0.05)}
.card-header{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;padding:1rem 1.25rem;border-bottom:1px solid var(--gray-100);background:linear-gradient(to bottom,#fff,#f9fafb)}
.form-label{display:block;font-size:0.8125rem;font-weight:600;color:var(--gray-700);margin-bottom:0.375rem}
.form-control{width:100%;padding:0.625rem 0.875rem;border:1px solid var(--gray-300);border-radius:0.75rem;font-size:0.875rem;transition:border-color 0.2s,box-shadow 0.2s;background:#fff}
.form-control:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(79,70,229,0.15)}
.table-responsive{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
.table-responsive .m-data-table{min-width:980px}
.pagination-wrap{display:flex;justify-content:space-between;align-items:center;padding:1rem 1.25rem;border-top:1px solid var(--gray-100);flex-wrap:wrap;gap:1rem}
.pagination-info{font-size:0.8125rem;color:var(--gray-600)}
.modal-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.modal-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem}
@media (max-width: 900px){
    .filter-grid{grid-template-columns:1fr 1fr}
    .filter-actions{grid-column:1/-1;justify-content:flex-start}
    .modal-grid-2,.modal-grid-3{grid-template-columns:1fr}
}
@media (max-width: 640px){
    .container-xl{padding-left:1rem;padding-right:1rem}
    .page-heading{font-size:1.25rem}
    .filter-grid{grid-template-columns:1fr}
    .beasiswa-toolbar{flex-direction:column;align-items:stretch}
    .beasiswa-toolbar .m-btn{width:100%;justify-content:center}
    .card-header{padding:0.875rem 1rem}
    .table-responsive .m-data-table{min-width:720px;font-size:0.8125rem}
    .pagination-wrap{flex-direction:column;align-items:stretch;text-align:center}
    .pagination{justify-content:center;flex-wrap:wrap}
    .modal-grid-2,.modal-grid-3{grid-template-columns:1fr}
}
</style>
