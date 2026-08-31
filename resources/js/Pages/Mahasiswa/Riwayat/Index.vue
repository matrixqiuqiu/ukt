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
                        <div v-else class="table-responsive">
                            <table class="m-data-table">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Semester</th>
                                        <th>Jumlah</th>
                                        <th>Metode</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="r in riwayat.data" :key="r.id">
                                        <td>{{ formatDate(r.created_at) }}</td>
                                        <td>{{ r.tagihan?.semester }} - {{ r.tagihan?.tahun_akademik }}</td>
                                        <td>
                                            {{ formatRupiah(r.jumlah_bayar) }}
                                            <div v-if="r.beasiswa" class="m-badge m-badge-success" style="font-size:0.6rem;margin-top:0.25rem;display:inline-flex;gap:0.25rem;"><i class="fas fa-graduation-cap"></i> {{ r.beasiswa.kode }} potongan Rp {{ Number(r.beasiswa.diskon).toLocaleString('id-ID') }}</div>
                                        </td>
                                        <td>{{ r.metode_pembayaran?.nama_metode }}</td>
                                        <td>
                                            <span class="m-badge" :class="statusInfo(r).class">{{ statusInfo(r).label }}</span>
                                            <div v-if="r.beasiswa" style="font-size:0.6rem;color:#059669;margin-top:0.2rem;">{{ r.beasiswa.nama }}</div>
                                        </td>
                                        <td>
                                            <Link :href="route('mahasiswa.pembayaran.show', r.id)" class="m-btn m-btn-primary m-btn-sm">Detail</Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
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
</style>
