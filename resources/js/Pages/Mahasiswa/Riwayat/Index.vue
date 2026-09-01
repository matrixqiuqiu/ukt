<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { formatRupiah, formatDate } from '@/utils';

const props = defineProps({
    riwayat: Object,
});

const statusInfo = (p) => {
    if (p.status === 'dikonfirmasi') {
        return { label: 'Lunas', class: 'm-badge-success' };
    }
    if (p.status === 'ditolak') {
        return { label: 'Ditolak', class: 'm-badge-danger' };
    }
    if (p.status === 'expired') {
        return { label: 'Expired', class: 'm-badge-danger' };
    }
    if (p.status === 'pending' && p.va_expired_at && new Date(p.va_expired_at).getTime() <= Date.now()) {
        return { label: 'Expired', class: 'm-badge-danger' };
    }
    return { label: 'Menunggu Pembayaran', class: 'm-badge-warning' };
};
</script>
<template>
    <Head title="Riwayat Pembayaran" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="page-heading">Riwayat Pembayaran</h2>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <div class="m-card">
                    <div class="m-card-header">
                        <h3 class="m-card-title">Daftar Riwayat Pembayaran</h3>
                    </div>
                    <div class="m-card-body">
                        <div v-if="riwayat.data.length === 0" class="empty-state">Belum ada riwayat pembayaran</div>
                        <div v-else>
                            <div class="table-responsive desktop-only">
                                <table class="m-data-table">
                                    <thead><tr><th>Tanggal</th><th>Semester</th><th>Jumlah</th><th>Metode</th><th>Status</th><th>Aksi</th></tr></thead>
                                    <tbody>
                                        <tr v-for="r in riwayat.data" :key="r.id">
                                            <td>{{ formatDate(r.created_at) }}</td>
                                            <td>{{ r.tagihan?.semester }} - {{ r.tagihan?.tahun_akademik }}</td>
                                            <td>{{ formatRupiah(r.jumlah_bayar) }}<div v-if="r.beasiswa" class="m-badge m-badge-success" style="font-size:0.6rem;margin-top:0.25rem;display:inline-flex;gap:0.25rem;"><i class="fas fa-graduation-cap"></i> {{ r.beasiswa.kode }}</div></td>
                                            <td>{{ r.metode_pembayaran?.nama_metode }}</td>
                                            <td><span class="m-badge" :class="statusInfo(r).class">{{ statusInfo(r).label }}</span></td>
                                            <td><Link :href="route('mahasiswa.pembayaran.show', r.id)" class="m-btn m-btn-primary m-btn-sm">Detail</Link></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mobile-cards">
                                <div v-for="r in riwayat.data" :key="r.id" class="m-card-mobile">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                                        <span style="font-size:0.75rem;color:var(--gray-500);">{{ formatDate(r.created_at) }}</span>
                                        <span class="m-badge" :class="statusInfo(r).class">{{ statusInfo(r).label }}</span>
                                    </div>
                                    <div style="font-weight:700;">Smt {{ r.tagihan?.semester }} · {{ r.tagihan?.tahun_akademik }}</div>
                                    <div style="font-size:1.125rem;font-weight:800;margin:0.25rem 0;">{{ formatRupiah(r.jumlah_bayar) }}</div>
                                    <div style="font-size:0.75rem;color:var(--gray-600);margin-bottom:0.75rem;">{{ r.metode_pembayaran?.nama_metode }}</div>
                                    <Link :href="route('mahasiswa.pembayaran.show', r.id)" class="m-btn m-btn-primary m-btn-sm" style="width:100%;">Detail</Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6b7280;
    font-size: 0.875rem;
}
.empty-state::before {
    content: '\f07b';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    display: block;
    font-size: 2.5rem;
    color: #9ca3af;
    margin-bottom: 0.75rem;
}
.desktop-only{display:block}
.mobile-cards{display:none}
.m-card-mobile{background:#fff;border:1px solid var(--gray-200);border-radius:1rem;padding:1rem;margin-bottom:0.75rem;box-shadow:var(--shadow-sm)}
@media(max-width:768px){.desktop-only{display:none}.mobile-cards{display:block}}
</style>
