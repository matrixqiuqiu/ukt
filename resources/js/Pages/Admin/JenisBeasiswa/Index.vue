<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ items: Object, filters: Object });
const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');

const showModal = ref(false);
const editMode = ref(false);
const editId = ref(null);
const form = ref({ kode:'', nama:'', deskripsi:'', status_aktif:true });

const doFilter = () => {
    const p={}; if(search.value) p.search=search.value; if(status.value) p.status=status.value;
    router.get(route('admin.jenis-beasiswa.index'), p, {preserveState:true});
};
const openCreate = ()=>{ editMode.value=false; editId.value=null; form.value={kode:'',nama:'',deskripsi:'',status_aktif:true}; showModal.value=true; };
const openEdit = (row)=>{ editMode.value=true; editId.value=row.id; form.value={kode:row.kode,nama:row.nama,deskripsi:row.deskripsi||'',status_aktif:row.status_aktif}; showModal.value=true; };
const close=()=>showModal.value=false;
const submit=()=>{
    const url=editMode.value?route('admin.jenis-beasiswa.update',editId.value):route('admin.jenis-beasiswa.store');
    const method=editMode.value?'put':'post';
    router[method](url, form.value, {preserveScroll:true,onSuccess:()=>close()});
};
const destroy=(row)=>{ if(confirm(`Hapus ${row.nama}?`)) router.delete(route('admin.jenis-beasiswa.destroy',row.id),{preserveScroll:true}); };
const toggle=(row)=>router.post(route('admin.jenis-beasiswa.toggle',row.id),{},{preserveScroll:true});
</script>

<template>
    <Head title="Jenis Beasiswa" />
    <AuthenticatedLayout>
        <template #header><h2 class="page-heading">Master Data — Jenis Beasiswa</h2></template>
        <div class="page-body"><div class="container-xl">
            <div class="custom-card" style="margin-bottom:1.25rem;">
                <div class="card-body" style="padding:1rem 1.25rem;">
                    <div style="display:grid;grid-template-columns:1fr 160px auto;gap:0.75rem;align-items:end;">
                        <div class="input-group input-group--search">
                            <span class="input-group__text"><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11.5" cy="11.5" r="9.5"/><path stroke-linecap="round" d="M18.5 18.5L22 22"/></g></svg></span>
                            <input type="search" class="input" v-model="search" placeholder="Cari kode/nama..." @keyup.enter="doFilter" />
                        </div>
                        <select v-model="status" @change="doFilter" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                        <div style="display:flex;gap:0.5rem;">
                            <button class="m-btn m-btn-primary" @click="doFilter"><i class="fas fa-filter"></i> Filter</button>
                            <a :href="route('admin.jenis-beasiswa.export')" class="m-btn m-btn-secondary"><i class="fas fa-download"></i> Export</a>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-bottom:1rem;display:flex;justify-content:space-between;align-items:center;">
                <div style="font-size:0.8125rem;color:var(--gray-600);">Total {{ items.total }} jenis</div>
                <button class="m-btn m-btn-primary" @click="openCreate"><i class="fas fa-plus"></i> Tambah Jenis</button>
            </div>

            <div class="custom-card">
                <div class="card-header"><h4><i class="fas fa-tags"></i> Daftar Jenis Beasiswa</h4></div>
                <div class="card-body" style="padding:0;">
                    <div v-if="items.data.length" class="table-responsive">
                    <table class="m-data-table">
                        <thead><tr><th>Kode</th><th>Nama</th><th>Deskripsi</th><th>Status</th><th style="width:160px">Aksi</th></tr></thead>
                        <tbody>
                            <tr v-for="row in items.data" :key="row.id">
                                <td style="font-family:monospace;font-weight:700;">{{ row.kode }}</td>
                                <td style="font-weight:600;">{{ row.nama }}</td>
                                <td style="color:var(--gray-600);max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ row.deskripsi || '-' }}</td>
                                <td><span class="m-badge" :class="row.status_aktif?'m-badge-success':'m-badge-danger'">{{ row.status_aktif?'Aktif':'Nonaktif' }}</span></td>
                                <td>
                                    <div style="display:flex;gap:0.375rem;">
                                        <button class="m-btn m-btn-sm" :class="row.status_aktif?'m-badge-warning':'m-badge-success'" @click="toggle(row)"><i :class="row.status_aktif?'fas fa-toggle-on':'fas fa-toggle-off'"></i></button>
                                        <button class="m-btn m-btn-sm m-btn-secondary" @click="openEdit(row)"><i class="fas fa-pen"></i></button>
                                        <button class="m-btn m-btn-sm m-btn-danger" @click="destroy(row)"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                    <div v-else style="text-align:center;padding:2.5rem;color:var(--gray-500);">Belum ada jenis beasiswa</div>
                    <div v-if="items.links" class="pagination-wrap">
                        <span class="pagination-info">Menampilkan {{ items.from }}-{{ items.to }} dari {{ items.total }}</span>
                        <div class="pagination">
                            <template v-for="link in items.links" :key="link.label">
                                <span v-if="!link.url" class="page-item disabled"><span class="page-link" v-html="link.label"></span></span>
                                <span v-else class="page-item" :class="{active:link.active}"><Link :href="link.url" class="page-link" v-html="link.label" /></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div></div>

        <Teleport to="body">
            <div v-if="showModal" class="modal-overlay" @click.self="close" style="position:fixed;inset:0;background:rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;z-index:100;padding:1rem;">
                <div class="modal-box" style="background:#fff;border-radius:1rem;width:100%;max-width:480px;">
                    <div style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--gray-200);display:flex;justify-content:space-between;align-items:center;">
                        <h3 style="margin:0;font-weight:700;">{{ editMode?'Edit Jenis':'Tambah Jenis' }}</h3>
                        <button @click="close" style="background:none;border:none;font-size:1.25rem;cursor:pointer;">×</button>
                    </div>
                    <form @submit.prevent="submit" style="padding:1.5rem;display:grid;gap:1rem;">
                        <div><label class="form-label">Kode *</label><input v-model="form.kode" class="form-control" required placeholder="JB006" /></div>
                        <div><label class="form-label">Nama *</label><input v-model="form.nama" class="form-control" required placeholder="Prestasi" /></div>
                        <div><label class="form-label">Deskripsi</label><textarea v-model="form.deskripsi" class="form-control" rows="2"></textarea></div>
                        <label style="display:flex;gap:0.5rem;align-items:center;"><input type="checkbox" v-model="form.status_aktif" /> Aktif</label>
                        <div style="display:flex;gap:0.5rem;justify-content:flex-end;">
                            <button type="button" class="m-btn m-btn-secondary" @click="close">Batal</button>
                            <button type="submit" class="m-btn m-btn-primary">{{ editMode?'Simpan':'Tambah' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.form-label{display:block;font-size:0.8125rem;font-weight:600;margin-bottom:0.375rem}
.form-control{width:100%;padding:0.625rem 0.75rem;border:1px solid var(--gray-300);border-radius:0.75rem}
.table-responsive{overflow-x:auto}.pagination-wrap{display:flex;justify-content:space-between;align-items:center;padding:1rem;border-top:1px solid var(--gray-100);flex-wrap:wrap;gap:1rem}
</style>
