<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { formatRupiah, formatDate } from '@/utils';

const props = defineProps({ beasiswa: Object, pencairans: Array });

const form = ref({ termin_ke: (props.pencairans.length + 1), nominal_dijanjikan: 0, tanggal_janji_cair: '', jatuh_tempo_external: '', keterangan: '' });
const editMode = ref(false);
const editId = ref(null);

const parseCurrency = (val) => Number(String(val).replace(/[^0-9]/g, '')) || 0;
const onNominalInput = (e) => { form.value.nominal_dijanjikan = parseCurrency(e.target.value); };
const onKonfirmasiNominalInput = (e) => { konfirmasiForm.value.nominal_cair = parseCurrency(e.target.value); };

const submit = () => {
    if (editMode.value) {
        router.put(route('admin.beasiswa.pencairan.update', editId.value), form.value, { preserveScroll:true, onSuccess:()=>{ editMode.value=false; editId.value=null; form.value={ termin_ke: props.pencairans.length + 1, nominal_dijanjikan:0, tanggal_janji_cair:'', jatuh_tempo_external:'', keterangan:'' }; } });
    } else {
        router.post(route('admin.beasiswa.pencairan.store', props.beasiswa.id), form.value, { preserveScroll:true, onSuccess:()=>{ form.value={ termin_ke: props.pencairans.length + 2, nominal_dijanjikan:0, tanggal_janji_cair:'', jatuh_tempo_external:'', keterangan:'' }; } });
    }
};
const openEdit = (row) => { editMode.value=true; editId.value=row.id; form.value={ termin_ke:row.termin_ke, nominal_dijanjikan:row.nominal_dijanjikan, tanggal_janji_cair:row.tanggal_janji_cair||'', jatuh_tempo_external:row.jatuh_tempo_external||'', keterangan:row.keterangan||'' }; };
const cancelEdit = () => { editMode.value=false; editId.value=null; form.value={ termin_ke: props.pencairans.length + 1, nominal_dijanjikan:0, tanggal_janji_cair:'', jatuh_tempo_external:'', keterangan:'' }; };
const destroy = (row) => { if(confirm('Hapus termin '+row.termin_ke+'?')) router.delete(route('admin.beasiswa.pencairan.destroy', row.id), {preserveScroll:true}); };

const konfirmasiForm = ref({ nominal_cair:0, tanggal_cair:'', bukti_cair:null });
const showKonfirmasi = ref(null);
const openKonfirmasi = (row) => { showKonfirmasi.value=row.id; konfirmasiForm.value={ nominal_cair: row.nominal_dijanjikan - row.nominal_cair, tanggal_cair:new Date().toISOString().slice(0,10), bukti_cair:null }; };
const handleFile = (e)=>{ konfirmasiForm.value.bukti_cair=e.target.files[0]; };
const submitKonfirmasi = (row) => {
    router.post(route('admin.beasiswa.pencairan.konfirmasi', row.id), { ...konfirmasiForm.value, _method:'post' }, { forceFormData:true, preserveScroll:true, onSuccess:()=>{ showKonfirmasi.value=null; } });
};
</script>

<template>
    <Head :title="`Pencairan ${beasiswa.kode}`" />
    <AuthenticatedLayout>
        <template #header><h2 class="page-heading">Beasiswa — Pencairan Eksternal: {{ beasiswa.nama_beasiswa }} ({{ beasiswa.kode }})</h2></template>
        <div class="page-body"><div class="container-xl">
            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:0.75rem;padding:1rem;margin-bottom:1rem;font-size:0.8125rem;color:#1e40af;">
                <i class="fas fa-info-circle"></i> H2 Talangan: Mahasiswa sudah dianggap lunas (tagihan dipotong), termin di sini untuk <b>menagih eksternal</b> (jatu tempo terpisah C1). Cair → update status, tidak ubah tagihan mahasiswa lagi.
            </div>

            <div class="custom-card" style="margin-bottom:1.5rem;">
                <div class="card-header"><h4><i class="fas fa-hand-holding-usd"></i> Tambah Termin</h4></div>
                <div class="card-body">
                    <form @submit.prevent="submit" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.75rem;align-items:end;">
                        <div><label class="form-label">Termin Ke</label><input v-model.number="form.termin_ke" type="number" min="1" class="form-control" required /></div>
                        <div><label class="form-label">Nominal Dijanjikan</label>
                            <div style="position:relative;">
                                <span style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);color:var(--gray-500);font-weight:600;font-size:0.8125rem;">Rp</span>
                                <input :value="Number(form.nominal_dijanjikan||0).toLocaleString('id-ID')" @input="onNominalInput" type="text" inputmode="numeric" class="form-control" style="padding-left:2.25rem;" required />
                            </div>
                        </div>
                        <div><label class="form-label">Jatuh Tempo External (C1)</label><input v-model="form.jatuh_tempo_external" type="date" class="form-control" /></div>
                        <div><label class="form-label">Tanggal Janji Cair</label><input v-model="form.tanggal_janji_cair" type="date" class="form-control" /></div>
                        <div style="grid-column:span 2;"><label class="form-label">Keterangan</label><input v-model="form.keterangan" class="form-control" placeholder="Termin 1 semester ganjil" /></div>
                        <div style="display:flex;gap:0.5rem;align-items:end;">
                            <button type="submit" class="m-btn m-btn-primary">{{ editMode ? 'Simpan' : 'Tambah' }}</button>
                            <button v-if="editMode" type="button" class="m-btn m-btn-secondary" @click="cancelEdit">Batal</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="custom-card">
                <div class="card-header"><h4>Daftar Termin ({{ pencairans.length }})</h4><Link :href="route('admin.beasiswa.assignments', beasiswa.id)" class="m-btn m-btn-secondary m-btn-sm">← Penerima</Link></div>
                <div class="card-body" style="padding:0;">
                    <div v-if="pencairans.length" class="table-responsive">
                    <table class="m-data-table">
                        <thead><tr><th>Termin</th><th>Dijanjikan</th><th>Cair</th><th>Sisa</th><th>Jatuh Tempo Ext</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <tr v-for="p in pencairans" :key="p.id">
                                <td style="font-weight:700;">#{{ p.termin_ke }}</td>
                                <td>{{ formatRupiah(p.nominal_dijanjikan) }}</td>
                                <td style="color:#059669;font-weight:700;">{{ formatRupiah(p.nominal_cair) }}</td>
                                <td style="color:#dc2626;">{{ formatRupiah(p.nominal_dijanjikan - p.nominal_cair) }}</td>
                                <td>{{ p.jatuh_tempo_external ? formatDate(p.jatuh_tempo_external) : '-' }}</td>
                                <td><span class="m-badge" :class="p.status==='cair_penuh'?'m-badge-success':p.status==='cair_sebagian'?'m-badge-warning':'m-badge-info'">{{ p.status }}</span></td>
                                <td>
                                    <div style="display:flex;gap:0.375rem;flex-wrap:wrap;">
                                        <button class="m-btn m-btn-sm m-btn-secondary" @click="openEdit(p)"><i class="fas fa-pen"></i></button>
                                        <button class="m-btn m-btn-sm" style="background:#0ea5e9;color:#fff;" @click="openKonfirmasi(p)"><i class="fas fa-check"></i> Cair</button>
                                        <button class="m-btn m-btn-sm m-btn-danger" @click="destroy(p)"><i class="fas fa-trash"></i></button>
                                    </div>
                                    <div v-if="showKonfirmasi===p.id" style="margin-top:0.5rem;padding:0.75rem;border:1px solid var(--gray-200);border-radius:0.5rem;background:#f9fafb;">
                                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;">
                                            <div><label class="form-label">Nominal Cair</label>
                                                <div style="position:relative;">
                                                    <span style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);color:var(--gray-500);font-weight:600;font-size:0.8125rem;">Rp</span>
                                                    <input :value="Number(konfirmasiForm.nominal_cair||0).toLocaleString('id-ID')" @input="onKonfirmasiNominalInput" type="text" inputmode="numeric" class="form-control" style="padding-left:2.25rem;" />
                                                </div>
                                            </div>
                                            <div><label class="form-label">Tanggal Cair</label><input v-model="konfirmasiForm.tanggal_cair" type="date" class="form-control" /></div>
                                        </div>
                                        <div style="margin-top:0.5rem;"><label class="form-label">Bukti</label><input type="file" @change="handleFile" class="form-control" /></div>
                                        <div style="margin-top:0.5rem;display:flex;gap:0.5rem;">
                                            <button class="m-btn m-btn-primary m-btn-sm" @click="submitKonfirmasi(p)">Simpan Cair</button>
                                            <button class="m-btn m-btn-secondary m-btn-sm" @click="showKonfirmasi=null">Batal</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                    <div v-else style="padding:2rem;text-align:center;color:var(--gray-500);">Belum ada termin</div>
                </div>
            </div>
        </div></div>
    </AuthenticatedLayout>
</template>

<style scoped>
.form-label{display:block;font-size:0.8125rem;font-weight:600;margin-bottom:0.375rem}
.form-control{width:100%;padding:0.625rem 0.75rem;border:1px solid var(--gray-300);border-radius:0.75rem}
.table-responsive{overflow-x:auto}
</style>
