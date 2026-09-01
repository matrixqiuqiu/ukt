<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { formatRupiah } from '@/utils';

const props = defineProps({ stats: Object, tagihans: Array });
</script>

<template>
    <Head title="Dashboard Mahasiswa" />
    <AuthenticatedLayout>
        <div class="page-body">
            <div class="container-xl">
                <!-- Welcome -->
                <div class="custom-card" style="margin-bottom:1.25rem;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border:none;">
                    <div class="card-body" style="padding:1.5rem;">
                        <h2 style="margin:0;font-size:1.25rem;font-weight:800;">Halo, {{ $page.props.auth.user?.name }} 👋</h2>
                        <p style="margin:0.25rem 0 0;opacity:0.9;font-size:0.875rem;">Ringkasan tagihan UKT Anda</p>
                    </div>
                </div>

                <!-- Stats simpel 2 cards -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.25rem;">
                    <div class="custom-card"><div class="card-body" style="display:flex;justify-content:space-between;align-items:center;">
                        <div><div style="font-size:1.75rem;font-weight:800;color:#059669;">{{ stats.sudahBayar }}</div><div style="font-size:0.8125rem;color:var(--gray-600);">Lunas</div></div>
                        <div style="width:44px;height:44px;border-radius:0.75rem;background:#dcfce7;display:flex;align-items:center;justify-content:center;color:#059669;"><i class="fas fa-check-circle"></i></div>
                    </div></div>
                    <div class="custom-card"><div class="card-body" style="display:flex;justify-content:space-between;align-items:center;">
                        <div><div style="font-size:1.75rem;font-weight:800;color:#d97706;">{{ stats.belumBayar }}</div><div style="font-size:0.8125rem;color:var(--gray-600);">Belum Bayar</div></div>
                        <div style="width:44px;height:44px;border-radius:0.75rem;background:#fef3c7;display:flex;align-items:center;justify-content:center;color:#d97706;"><i class="fas fa-exclamation-circle"></i></div>
                    </div></div>
                </div>

                <!-- Aksi cepat simpel -->
                <div style="display:flex;gap:0.75rem;margin-bottom:1.25rem;flex-wrap:wrap;">
                    <Link :href="route('mahasiswa.tagihan.index')" class="m-btn m-btn-primary"><i class="fas fa-credit-card"></i> Bayar UKT</Link>
                    <Link :href="route('mahasiswa.riwayat.index')" class="m-btn m-btn-secondary"><i class="fas fa-history"></i> Riwayat</Link>
                </div>

                <!-- Tagihan table simpel -->
                <div class="custom-card">
                    <div class="card-header"><h4>Daftar Tagihan</h4><Link :href="route('mahasiswa.tagihan.index')" class="m-btn m-btn-sm m-btn-secondary">Lihat Semua</Link></div>
                    <div class="card-body" style="padding:0;">
                        <div v-if="tagihans && tagihans.length" class="table-responsive">
                            <table class="m-data-table">
                                <thead><tr><th>Semester</th><th>Tahun</th><th>Nominal</th><th>Status</th><th>Aksi</th></tr></thead>
                                <tbody>
                                    <tr v-for="t in tagihans" :key="t.id">
                                        <td style="text-align:center;"><span class="m-badge m-badge-secondary">{{ t.semester }}</span></td>
                                        <td>{{ t.tahun_akademik }}</td>
                                        <td style="font-weight:700;">{{ formatRupiah(t.nominal) }}</td>
                                        <td><span class="m-badge" :class="t.status==='sudah_dibayar'?'m-badge-success':t.status==='dispen'?'m-badge-dispen':'m-badge-warning'">{{ t.status==='sudah_dibayar'?'Lunas':t.status==='dispen'?'Dispen':'Belum Lunas' }}</span></td>
                                        <td>
                                            <Link v-if="t.status!=='sudah_dibayar' && t.pending_pembayaran_id" :href="route('mahasiswa.pembayaran.show', t.pending_pembayaran_id)" class="m-btn m-btn-sm m-btn-secondary">Lihat</Link>
                                            <Link v-else-if="t.status!=='sudah_dibayar' && t.status!=='dispen'" :href="route('mahasiswa.tagihan.show', t.id)" class="m-btn m-btn-sm m-btn-primary">Bayar</Link>
                                            <span v-else style="color:var(--gray-500);font-size:0.75rem;">✓ Lunas</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else style="text-align:center;padding:2rem;color:var(--gray-500);">Belum ada tagihan</div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
