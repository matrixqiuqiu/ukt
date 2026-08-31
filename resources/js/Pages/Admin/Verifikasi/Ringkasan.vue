<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { formatRupiah } from '@/utils';

const props = defineProps({
    summary: Object,
});
</script>
<template>
    <Head title="Ringkasan Verifikasi" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="page-heading">Ringkasan Verifikasi</h2>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <div class="quick-stats mb-4">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="custom-card">
                                <div class="card-body text-center">
                                    <div class="stat-card">
                                        <div class="stat-card__label">Menunggu</div>
                                        <div class="stat-card__value" style="color:var(--warning);">{{ summary.pending }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="custom-card">
                                <div class="card-body text-center">
                                    <div class="stat-card">
                                        <div class="stat-card__label">Dikonfirmasi</div>
                                        <div class="stat-card__value" style="color:var(--success);">{{ summary.confirmed }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="custom-card">
                                <div class="card-body text-center">
                                    <div class="stat-card">
                                        <div class="stat-card__label">Ditolak</div>
                                        <div class="stat-card__value" style="color:var(--danger);">{{ summary.rejected }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="custom-card">
                                <div class="card-body text-center">
                                    <div class="stat-card">
                                        <div class="stat-card__label">Total Pendapatan</div>
                                        <div class="stat-card__value" style="color:var(--primary-color);font-size:1.25rem;">{{ formatRupiah(summary.totalPendapatan) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
