<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { formatRupiah, formatDate } from '@/utils';

const props = defineProps({
    pembayaran: Object,
});
</script>
<template>
    <Head title="Detail Riwayat" />
    <AuthenticatedLayout>
        <template #header>
            <div class="page-heading">
                <Link :href="route('mahasiswa.riwayat.index')" class="m-btn m-btn--secondary m-btn--sm">&larr; Kembali</Link>
                <span>Detail Riwayat Pembayaran</span>
            </div>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="m-card">
                            <div class="m-card-header">
                                <h3 class="m-card-title">Informasi Pembayaran</h3>
                            </div>
                            <div class="m-card-body">
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <div class="stat-card">
                                            <div class="stat-card__label">Status</div>
                                            <div class="stat-card__value">
                                                <span :class="{
                                                    'm-badge m-badge--lg': true,
                                                    'm-badge--warning': pembayaran.status === 'pending',
                                                    'm-badge--success': pembayaran.status === 'dikonfirmasi',
                                                    'm-badge--danger': pembayaran.status === 'ditolak'
                                                }">{{ pembayaran.status }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="stat-card">
                                            <div class="stat-card__label">Tanggal</div>
                                            <div class="stat-card__value">{{ formatDate(pembayaran.created_at) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="stat-card">
                                            <div class="stat-card__label">Jumlah Bayar</div>
                                            <div class="stat-card__value stat-card__value--primary">{{ formatRupiah(pembayaran.jumlah_bayar) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="stat-card">
                                            <div class="stat-card__label">Metode Pembayaran</div>
                                            <div class="stat-card__value">{{ pembayaran.metode_pembayaran?.nama_metode }}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="stat-card">
                                            <div class="stat-card__label">Nama Pengirim</div>
                                            <div class="stat-card__value">{{ pembayaran.nama_pengirim }}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="stat-card">
                                            <div class="stat-card__label">Tagihan</div>
                                            <div class="stat-card__value">Semester {{ pembayaran.tagihan?.semester }} - {{ pembayaran.tagihan?.tahun_akademik }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="m-card">
                            <div class="m-card-header">
                                <h3 class="m-card-title">Bukti Pembayaran</h3>
                            </div>
                            <div class="m-card-body">
                                <div v-if="pembayaran.bukti_pembayaran">
                                    <img :src="'/storage/' + pembayaran.bukti_pembayaran" class="img-fluid rounded" alt="Bukti Pembayaran" />
                                </div>
                                <div v-else class="text-center py-4 text-gray-500">Tidak ada bukti pembayaran</div>
                            </div>
                        </div>
                        <div v-if="pembayaran.catatan_admin" class="m-card mt-3">
                            <div class="m-card-header">
                                <h3 class="m-card-title">Catatan Admin</h3>
                            </div>
                            <div class="m-card-body">
                                <div class="alert alert-danger">{{ pembayaran.catatan_admin }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
