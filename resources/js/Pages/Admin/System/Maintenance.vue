<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ isDown: Boolean, downData: Object });

const form = useForm({ action: props.isDown ? 'up' : 'down', secret: '', retry: 60, message: '' });

const toggle = () => {
    form.action = props.isDown ? 'up' : 'down';
    form.post(route('admin.system.maintenance.toggle'), { preserveScroll: true });
};
</script>

<template>
    <Head title="Maintenance" />
    <AuthenticatedLayout>
        <template #header><h2 class="page-heading">System — Maintenance</h2></template>
        <div class="page-body"><div class="container-xl">
            <div class="custom-card" style="max-width:720px;">
                <div class="card-header"><h4><i class="fas fa-tools"></i> Maintenance Mode</h4>
                    <span class="m-badge" :class="isDown ? 'm-badge-danger' : 'm-badge-success'">{{ isDown ? 'Aktif (Down)' : 'Nonaktif (Up)' }}</span>
                </div>
                <div class="card-body">
                    <div v-if="isDown" style="background:#fef2f2;border:1px solid #fecaca;border-radius:0.75rem;padding:1rem;margin-bottom:1rem;font-size:0.875rem;color:#991b1b;">
                        <strong>Situs dalam mode maintenance.</strong> Hanya IP/bypass secret yang bisa akses. Jangan lupa nonaktifkan setelah selesai.
                        <pre v-if="downData" style="margin-top:0.5rem;background:#fff;padding:0.5rem;border-radius:0.5rem;overflow:auto;font-size:0.75rem;">{{ JSON.stringify(downData, null, 2) }}</pre>
                    </div>
                    <div v-else style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:0.75rem;padding:1rem;margin-bottom:1rem;font-size:0.875rem;color:#166534;">
                        Situs berjalan normal.
                    </div>

                    <form @submit.prevent="toggle">
                        <div v-if="!isDown" class="form-group"><label class="form-label">Secret (opsional, bypass URL /secret)</label><input v-model="form.secret" type="text" class="form-control" placeholder="mis: my-secret-123" /></div>
                        <div v-if="!isDown" class="form-group"><label class="form-label">Retry (detik)</label><input v-model.number="form.retry" type="number" class="form-control" min="30" /></div>
                        <div v-if="!isDown" class="form-group"><label class="form-label">Pesan (opsional)</label><input v-model="form.message" type="text" class="form-control" placeholder="Sedang maintenance..." /></div>
                        <button type="submit" class="m-btn" :class="isDown ? 'm-btn-success' : 'm-btn-danger'" :disabled="form.processing">
                            <i :class="isDown ? 'fas fa-power-off' : 'fas fa-tools'"></i> {{ isDown ? 'Nonaktifkan Maintenance' : 'Aktifkan Maintenance' }}
                        </button>
                    </form>
                </div>
            </div>
        </div></div>
    </AuthenticatedLayout>
</template>

<style scoped>
.form-group{margin-bottom:1rem}.form-label{display:block;font-size:0.875rem;font-weight:600;color:var(--gray-700);margin-bottom:0.375rem}.form-control{width:100%;padding:0.625rem 0.875rem;border:1px solid var(--gray-300);border-radius:0.75rem;font-size:0.875rem}
</style>
