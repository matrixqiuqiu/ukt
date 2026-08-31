<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ backups: Array, tables: Array, dbName: String });

const runBackup = () => { if(confirm('Buat backup database sekarang?')) router.post(route('admin.system.backup.run'), {}, { preserveScroll:false }); };
const download = (name) => { window.location.href = route('admin.system.backup.download', name); };
const del = (name) => { if(confirm(`Hapus ${name}?`)) router.delete(route('admin.system.backup.delete', name), { preserveScroll:true }); };

const importInput = ref(null);
const importForm = useForm({ file: null });
const importing = ref(false);
const triggerImport = () => importInput.value?.click();
const handleImport = (e) => {
    const file = e.target.files[0];
    if(!file) return;
    if(!confirm(`Restore database dari ${file.name}? Data sekarang akan tertimpa!`)) { e.target.value=''; return; }
    importing.value = true;
    importForm.file = file;
    importForm.post(route('admin.system.backup.import'), { forceFormData:true, onFinish:()=>{ importing.value=false; e.target.value=''; } });
};
</script>

<template>
    <Head title="Backup Data" />
    <AuthenticatedLayout>
        <template #header><h2 class="page-heading">System — Backup Data</h2></template>
        <div class="page-body"><div class="container-xl">
            <div class="custom-card" style="margin-bottom:1.5rem;">
                <div class="card-header"><h4><i class="fas fa-database"></i> Database: {{ dbName }}</h4>
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                        <button @click="runBackup" class="m-btn m-btn-primary"><i class="fas fa-download"></i> Export</button>
                        <button @click="triggerImport" class="m-btn m-btn-secondary" :disabled="importing"><i :class="importing ? 'fas fa-spinner fa-spin' : 'fas fa-upload'"></i> {{ importing ? 'Importing...' : 'Import' }}</button>
                        <input ref="importInput" type="file" accept=".sql,.txt" style="display:none" @change="handleImport" />
                    </div>
                </div>
                <div class="card-body">
                    <div style="font-size:0.8125rem;color:var(--gray-600);margin-bottom:0.75rem;">{{ tables.length }} tabel: {{ tables.slice(0,10).join(', ') }}{{ tables.length>10 ? '...' : '' }}</div>
                    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:0.5rem;padding:0.75rem;font-size:0.8125rem;color:#1e40af;"><i class="fas fa-info-circle"></i> Backup disimpan di <code>storage/app/private/backups</code>. Gunakan mysqldump jika tersedia, fallback ke dump PHP.</div>
                </div>
            </div>

            <div class="custom-card"><div class="card-header"><h4>Daftar Backup ({{ backups.length }})</h4></div>
                <div class="card-body" style="padding:0;">
                    <div v-if="backups.length===0" style="text-align:center;padding:2rem;color:var(--gray-500);">Belum ada backup.</div>
                    <div v-else class="table-responsive"><table class="m-data-table"><thead><tr><th>Nama File</th><th>Ukuran</th><th>Tanggal</th><th style="width:180px;">Aksi</th></tr></thead>
                    <tbody><tr v-for="b in backups" :key="b.name"><td style="font-weight:600;font-family:monospace;font-size:0.8125rem;">{{ b.name }}</td><td>{{ b.size_label }}</td><td>{{ b.modified }}</td>
                    <td><div style="display:flex;gap:0.375rem;"><button class="m-btn m-btn-sm m-btn-secondary" @click="download(b.name)"><i class="fas fa-download"></i></button><button class="m-btn m-btn-sm m-btn-danger" @click="del(b.name)"><i class="fas fa-trash"></i></button></div></td></tr></tbody></table></div>
                </div>
            </div>
        </div></div>
    </AuthenticatedLayout>
</template>

<style scoped>
.table-responsive{overflow-x:auto}
.m-data-table{min-width:520px}
</style>
