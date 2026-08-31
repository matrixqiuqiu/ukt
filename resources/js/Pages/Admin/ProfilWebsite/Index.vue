<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    profile: Object,
});
const { success, error } = useToast();

const form = useForm({
    website_name: props.profile?.website_name || '',
    website_short_name: props.profile?.website_short_name || '',
    website_tagline: props.profile?.website_tagline || '',
    website_footer_text: props.profile?.website_footer_text || '',
    invoice_institution_name: props.profile?.invoice_institution_name || '',
    invoice_institution_address: props.profile?.invoice_institution_address || '',
    invoice_institution_phone: props.profile?.invoice_institution_phone || '',
    invoice_institution_email: props.profile?.invoice_institution_email || '',
    invoice_institution_website: props.profile?.invoice_institution_website || '',
});

const submit = () => {
    form.put(route('admin.profil-website.update'), {
        preserveScroll: true,
        onSuccess: () => success('Profil website berhasil disimpan.'),
        onError: () => error('Gagal menyimpan, periksa isian form.'),
    });
};
</script>

<template>
    <Head title="Profil Website" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="page-heading">Pengaturan Profil Website</h2>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <form @submit.prevent="submit">
                    <!-- Identitas Website -->
                    <div class="custom-card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Identitas Website</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="m-form-group">
                                        <label>Nama Website</label>
                                        <input v-model="form.website_name" type="text" class="m-form-control" placeholder="Contoh: UKT System" />
                                        <div v-if="form.errors.website_name" class="form-error">{{ form.errors.website_name }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="m-form-group">
                                        <label>Nama Singkat</label>
                                        <input v-model="form.website_short_name" type="text" class="m-form-control" placeholder="Contoh: UKT" />
                                        <div v-if="form.errors.website_short_name" class="form-error">{{ form.errors.website_short_name }}</div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="m-form-group">
                                        <label>Tagline</label>
                                        <input v-model="form.website_tagline" type="text" class="m-form-control" placeholder="Contoh: Kelola pembayaran UKT dengan mudah" />
                                        <div v-if="form.errors.website_tagline" class="form-error">{{ form.errors.website_tagline }}</div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="m-form-group">
                                        <label>Footer Text</label>
                                        <input v-model="form.website_footer_text" type="text" class="m-form-control" placeholder="Contoh: &copy; 2026 Universitas Bumigora. All rights reserved." />
                                        <div v-if="form.errors.website_footer_text" class="form-error">{{ form.errors.website_footer_text }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Identitas Institusi (untuk invoice/surat) -->
                    <div class="custom-card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Identitas Institusi (Invoice)</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="m-form-group">
                                        <label>Nama Institusi</label>
                                        <input v-model="form.invoice_institution_name" type="text" class="m-form-control" placeholder="Contoh: UNIVERSITAS BUMIGORA" />
                                        <div v-if="form.errors.invoice_institution_name" class="form-error">{{ form.errors.invoice_institution_name }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="m-form-group">
                                        <label>Telepon</label>
                                        <input v-model="form.invoice_institution_phone" type="text" class="m-form-control" placeholder="Contoh: (0370) 634498" />
                                        <div v-if="form.errors.invoice_institution_phone" class="form-error">{{ form.errors.invoice_institution_phone }}</div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="m-form-group">
                                        <label>Alamat</label>
                                        <input v-model="form.invoice_institution_address" type="text" class="m-form-control" placeholder="Alamat lengkap institusi" />
                                        <div v-if="form.errors.invoice_institution_address" class="form-error">{{ form.errors.invoice_institution_address }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="m-form-group">
                                        <label>Email</label>
                                        <input v-model="form.invoice_institution_email" type="email" class="m-form-control" placeholder="email@institusi.ac.id" />
                                        <div v-if="form.errors.invoice_institution_email" class="form-error">{{ form.errors.invoice_institution_email }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="m-form-group">
                                        <label>Website</label>
                                        <input v-model="form.invoice_institution_website" type="text" class="m-form-control" placeholder="https://ubg.ac.id" />
                                        <small class="text-muted">Boleh tanpa https://, akan otomatis ditambahkan.</small>
                                        <div v-if="form.errors.invoice_institution_website" class="form-error">{{ form.errors.invoice_institution_website }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            <i class="fas fa-save"></i>
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Profil' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.card-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}
.m-form-group { margin-bottom: 1.25rem; }
.m-form-group label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}
.m-form-control {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 1.5px solid #d1d5db;
    border-radius: 0.625rem;
    font-size: 0.875rem;
    font-family: inherit;
    color: #1f2937;
    background: #ffffff;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.m-form-control:focus {
    outline: none;
    border-color: var(--primary, #4f46e5);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
}
.form-error { color: #dc2626; font-size: 0.8125rem; margin-top: 0.375rem; }
.form-actions {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 2rem;
}
</style>
