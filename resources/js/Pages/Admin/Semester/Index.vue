<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    semester: Object,
    tahunAkademiks: Array,
});

const activeTa = computed(() => props.tahunAkademiks.find(t => t.is_aktif));

const form = useForm({
    tahun_akademik_id: activeTa.value?.id || '',
    jatuh_tempo: props.semester?.jatuh_tempo ? props.semester.jatuh_tempo.substring(0, 10) : '',
});

const submit = () => {
    form.put(route('admin.semester-aktif.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Pengaturan Semester" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="page-heading">Pengaturan Semester Aktif</h2>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <!-- Status Card -->
                <div class="custom-card" style="margin-bottom:1.5rem;">
                    <div class="card-header">
                        <h4><i class="fas fa-calendar-alt" style="margin-right:0.5rem;"></i> Semester Aktif Saat Ini</h4>
                        <Link :href="route('admin.tahun-akademik.index')" class="m-btn m-btn-sm m-btn-secondary">
                            <i class="fas fa-cog"></i> Kelola Tahun Akademik
                        </Link>
                    </div>
                    <div class="card-body">
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;">
                            <div style="text-align:center;padding:1.5rem;background:var(--gray-50);border-radius:0.75rem;">
                                <div style="font-size:1.75rem;font-weight:800;color:var(--gray-900);">{{ activeTa?.nama || '-' }}</div>
                                <div style="font-size:0.875rem;font-weight:600;color:var(--gray-700);">Tahun Akademik</div>
                                <div style="font-size:0.75rem;color:var(--gray-500);margin-top:0.25rem;">{{ activeTa?.semester || '-' }}</div>
                            </div>
                            <div style="text-align:center;padding:1.5rem;background:var(--gray-50);border-radius:0.75rem;">
                                <div style="font-size:1.75rem;font-weight:800;color:var(--gray-900);">
                                    {{ semester?.jatuh_tempo ? new Date(semester.jatuh_tempo).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-' }}
                                </div>
                                <div style="font-size:0.875rem;font-weight:600;color:var(--gray-700);">Jatuh Tempo</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info -->
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:0.75rem;padding:1rem 1.5rem;margin-bottom:1.5rem;font-size:0.8125rem;color:#1e40af;">
                    <i class="fas fa-info-circle"></i> <strong>Catatan:</strong> Pilih tahun akademik yang sudah dibuat di halaman <Link :href="route('admin.tahun-akademik.index')" style="text-decoration:underline;">Tahun Akademik</Link>. Tahun akademik aktif akan digunakan untuk penentuan tagihan mahasiswa.
                </div>

                <!-- Edit Form -->
                <div class="custom-card">
                    <div class="card-header">
                        <h4><i class="fas fa-cog" style="margin-right:0.5rem;"></i> Ubah Pengaturan</h4>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <div class="form-group">
                                <label class="form-label">Tahun Akademik <span style="color:var(--danger);">*</span></label>
                                <select v-model="form.tahun_akademik_id" class="form-control" required>
                                    <option value="" disabled>Pilih Tahun Akademik</option>
                                    <option v-for="ta in tahunAkademiks" :key="ta.id" :value="ta.id">
                                        {{ ta.nama }} — {{ ta.semester }} {{ ta.is_aktif ? '(Aktif)' : '' }}
                                    </option>
                                </select>
                                <div v-if="form.errors.tahun_akademik_id" class="form-error">{{ form.errors.tahun_akademik_id }}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Jatuh Tempo <span style="color:var(--danger);">*</span></label>
                                <input v-model="form.jatuh_tempo" type="date" class="form-control" required />
                                <div v-if="form.errors.jatuh_tempo" class="form-error">{{ form.errors.jatuh_tempo }}</div>
                            </div>
                            <div style="display:flex;justify-content:flex-end;margin-top:1.5rem;">
                                <button type="submit" class="m-btn m-btn-primary" :disabled="form.processing">
                                    <i class="fas fa-save"></i>
                                    {{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.form-group { margin-bottom: 1.25rem; }
.form-label { display: block; font-size: 0.875rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.375rem; }
.form-control { width: 100%; padding: 0.625rem 0.875rem; border: 1px solid var(--gray-300); border-radius: 0.75rem; font-size: 0.875rem; transition: border-color 0.2s, box-shadow 0.2s; }
.form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15); }
.form-error { font-size: 0.75rem; color: var(--danger); margin-top: 0.25rem; }
</style>
