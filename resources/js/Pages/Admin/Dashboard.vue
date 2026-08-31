<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { formatRupiah } from '@/utils';

const props = defineProps({
    stats: Object,
    recentPayments: Array,
});
</script>

<template>
    <Head title="Admin Dashboard" />
    <AuthenticatedLayout>
        <div class="admin-dashboard">
            <!-- Section Header -->
            <div style="background: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <h1 style="font-size: 1.5rem; font-weight: 800; color: #191d21; margin: 0;">Dashboard</h1>
                <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem;">
                    <span style="color: #6c757d;"><a href="#" style="color: #6c757d; text-decoration: none;">Dashboard</a></span>
                    <span style="color: var(--primary-color); font-weight: 600;">Admin UKT</span>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="quick-stats">
                <div class="quick-stat-item primary">
                    <div class="value">{{ stats.totalMahasiswa }}</div>
                    <div class="label">Total Mahasiswa</div>
                </div>
                <div class="quick-stat-item success">
                    <div class="value">{{ stats.confirmedPayments }}</div>
                    <div class="label">Sudah Bayar</div>
                </div>
                <div class="quick-stat-item warning">
                    <div class="value">{{ stats.pendingPayments }}</div>
                    <div class="label">Menunggu Verifikasi</div>
                </div>
                <div class="quick-stat-item danger">
                    <div class="value">{{ formatRupiah(stats.totalPendapatan) }}</div>
                    <div class="label">Total Pendapatan</div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row">
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="stat-card">
                        <div class="stat-icon primary"><i class="fas fa-users"></i></div>
                        <div class="stat-title">Total Mahasiswa Aktif</div>
                        <div class="stat-value">{{ stats.totalMahasiswa }}</div>
                        <div class="stat-change positive"><i class="fas fa-arrow-up"></i> Data terkini</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="stat-card">
                        <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-title">Pembayaran Dikonfirmasi</div>
                        <div class="stat-value">{{ stats.confirmedPayments }}</div>
                        <div class="stat-change positive"><i class="fas fa-arrow-up"></i> Pembayaran lunas</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="stat-card">
                        <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
                        <div class="stat-title">Menunggu Verifikasi</div>
                        <div class="stat-value">{{ stats.pendingPayments }}</div>
                        <div class="stat-change negative"><i class="fas fa-arrow-down"></i> Perlu tindak lanjut</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="stat-card">
                        <div class="stat-icon info"><i class="fas fa-money-bill-wave"></i></div>
                        <div class="stat-title">Total Pendapatan</div>
                        <div class="stat-value">{{ formatRupiah(stats.totalPendapatan) }}</div>
                        <div class="stat-change positive"><i class="fas fa-arrow-up"></i> Dari semua pembayaran</div>
                    </div>
                </div>
            </div>

            <!-- Recent Payments -->
            <div class="row">
                <div class="col-lg-4 col-md-12">
                    <div class="custom-card">
                        <div class="card-header">
                            <h4>Pembayaran Terbaru</h4>
                            <Link :href="route('admin.pembayaran.index')" class="btn btn-light btn-sm">Lihat Semua</Link>
                        </div>
                        <div class="card-body">
                            <div class="activities">
                                <div v-for="(p, idx) in recentPayments" :key="p.id" class="activity">
                                    <div class="activity-icon" :class="{
                                        'bg-success': p.status === 'dikonfirmasi',
                                        'bg-warning': p.status === 'pending',
                                        'bg-primary': idx % 2 === 0 && p.status !== 'dikonfirmasi' && p.status !== 'pending',
                                    }">
                                        <i :class="p.status === 'dikonfirmasi' ? 'fas fa-check' : 'fas fa-clock'"></i>
                                    </div>
                                    <div class="activity-detail">
                                        <div class="activity-meta">
                                            <span class="text-job" :class="{
                                                'text-success': p.status === 'dikonfirmasi',
                                                'text-warning': p.status === 'pending',
                                                'text-primary': p.status !== 'dikonfirmasi' && p.status !== 'pending',
                                            }">{{ p.created_at }}</span>
                                            <span class="bullet"></span>
                                            <Link :href="route('admin.pembayaran.show', p.id)" class="text-primary">Detail</Link>
                                        </div>
                                        <p>{{ p.tagihan?.mahasiswa?.nama_lengkap }} - {{ formatRupiah(p.jumlah_bayar) }}</p>
                                    </div>
                                </div>
                                <div v-if="!recentPayments || recentPayments.length === 0" class="text-center py-4" style="color: #6c757d;">
                                    Belum ada pembayaran
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="custom-card">
                <div class="card-header">
                    <h4>Aksi Cepat</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-sm-6 col-12" style="margin-bottom: 1rem;">
                            <Link :href="route('admin.mahasiswa.index')" class="btn btn-primary" style="width: 100%; justify-content: center;">
                                <i class="fas fa-users"></i> Data Mahasiswa
                            </Link>
                        </div>
                        <div class="col-md-3 col-sm-6 col-12" style="margin-bottom: 1rem;">
                            <Link :href="route('admin.tagihan.index')" class="btn btn-success" style="width: 100%; justify-content: center;">
                                <i class="fas fa-file-invoice"></i> Tagihan UKT
                            </Link>
                        </div>
                        <div class="col-md-3 col-sm-6 col-12" style="margin-bottom: 1rem;">
                            <Link :href="route('admin.pembayaran.index')" class="btn btn-info" style="width: 100%; justify-content: center;">
                                <i class="fas fa-credit-card"></i> Pembayaran
                            </Link>
                        </div>
                        <div class="col-md-3 col-sm-6 col-12" style="margin-bottom: 1rem;">
                            <Link :href="route('admin.verifikasi.index')" class="btn btn-warning" style="width: 100%; justify-content: center;">
                                <i class="fas fa-check-circle"></i> Verifikasi
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
