<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { formatRupiah, formatDate } from '@/utils';

const props = defineProps({
    pembayarans: Object,
    expiredPembayarans: Array,
    expiredCount: Number,
});

const confirm = (id) => {
    if (window.confirm('Yakin ingin mengkonfirmasi pembayaran ini?')) {
        router.post(route('admin.pembayaran.verifikasi', id));
    }
};

const reject = (id) => {
    const catatan = window.prompt('Masukkan alasan penolakan:');
    if (catatan) {
        router.post(route('admin.pembayaran.tolak', id), { catatan_admin: catatan });
    }
};
</script>
<template>
    <Head title="Verifikasi Pembayaran" />
    <AuthenticatedLayout>
        <template #header>
            <div class="page-heading">
                <h2 class="page-heading">Verifikasi Pembayaran</h2>
            </div>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <!-- Banner VA Expired -->
                <div
                    v-if="expiredCount > 0"
                    style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;border-radius:0.75rem;padding:0.875rem 1.125rem;margin-bottom:1.25rem;font-size:0.875rem;display:flex;align-items:center;gap:0.625rem;"
                >
                    <i class="fas fa-hourglass-end"></i>
                    <strong>{{ expiredCount }} pembayaran VA expired</strong> — tidak ditampilkan di antrean verifikasi. Silakan cek bagian Expired di bawah.
                </div>

                <div class="custom-card">
                    <div class="card-header">
                        <h3 class="card-title">Pembayaran Menunggu Verifikasi</h3>
                    </div>
                    <div class="card-body">
                        <div v-if="pembayarans.data.length === 0" class="text-center" style="padding:2rem;color:var(--gray-500);">
                            <i class="fas fa-check-circle" style="font-size:2rem;color:var(--success);margin-bottom:0.5rem;display:block;"></i>
                            Tidak ada pembayaran yang perlu diverifikasi
                        </div>
                        <div v-else class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Mahasiswa</th>
                                        <th>NIM</th>
                                        <th>Semester</th>
                                        <th>Jumlah Bayar</th>
                                        <th>Bukti</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in pembayarans.data" :key="p.id">
                                        <td>{{ formatDate(p.created_at) }}</td>
                                        <td>{{ p.tagihan?.mahasiswa?.nama_lengkap }}</td>
                                        <td>{{ p.tagihan?.mahasiswa?.nim }}</td>
                                        <td>{{ p.tagihan?.semester }}</td>
                                        <td>{{ formatRupiah(p.jumlah_bayar) }}</td>
                                        <td>
                                            <a v-if="p.bukti_pembayaran" :href="`/storage/${p.bukti_pembayaran}`" target="_blank" class="btn btn-sm btn-info">Lihat</a>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td>
                                            <div style="display:flex;gap:0.375rem;">
                                                <Link :href="route('admin.pembayaran.show', p.id)" class="btn btn-sm btn-primary">Detail</Link>
                                                <button @click="confirm(p.id)" class="btn btn-sm btn-success">Konfirmasi</button>
                                                <button @click="reject(p.id)" class="btn btn-sm btn-danger">Tolak</button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Pembayaran VA Expired -->
                <div class="custom-card" style="margin-top:1.5rem;">
                    <div class="card-header">
                        <h3 class="card-title">
                            Pembayaran VA Expired
                            <span class="m-badge m-badge-danger" style="margin-left:0.5rem;">{{ expiredCount }}</span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <div v-if="expiredPembayarans.length === 0" class="text-center" style="padding:2rem;color:var(--gray-500);">
                            <i class="fas fa-hourglass-end" style="font-size:2rem;color:var(--danger);margin-bottom:0.5rem;display:block;"></i>
                            Tidak ada pembayaran expired
                        </div>
                        <div v-else class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Mahasiswa</th>
                                        <th>NIM</th>
                                        <th>Semester</th>
                                        <th>Jumlah Bayar</th>
                                        <th>VA Number</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in expiredPembayarans" :key="p.id">
                                        <td>{{ formatDate(p.created_at) }}</td>
                                        <td>{{ p.tagihan?.mahasiswa?.nama_lengkap }}</td>
                                        <td>{{ p.tagihan?.mahasiswa?.nim }}</td>
                                        <td>{{ p.tagihan?.semester }}</td>
                                        <td>{{ formatRupiah(p.jumlah_bayar) }}</td>
                                        <td style="font-family:monospace;font-size:0.8125rem;">{{ p.va_number || '-' }}</td>
                                        <td><span class="m-badge m-badge-danger">Expired</span></td>
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
