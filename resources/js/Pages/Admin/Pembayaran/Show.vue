<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { formatRupiah, formatDate } from '@/utils';

const props = defineProps({
    pembayaran: Object,
    beasiswa: Object,
});

const confirm = () => {
    if (window.confirm('Yakin ingin mengkonfirmasi pembayaran ini?')) {
        router.post(route('admin.pembayaran.verifikasi', props.pembayaran.id));
    }
};

const reject = () => {
    const catatan = window.prompt('Masukkan alasan penolakan:');
    if (catatan) {
        router.post(route('admin.pembayaran.tolak', props.pembayaran.id), {
            catatan_admin: catatan,
        });
    }
};
</script>
<template>
    <Head title="Detail Pembayaran" />
    <AuthenticatedLayout>
        <template #header>
            <div class="page-heading">
                <Link :href="route('admin.pembayaran.index')" class="btn btn-secondary btn-sm">&larr; Kembali</Link>
                <span>Detail Pembayaran</span>
            </div>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <div class="detail-layout">
                    <div class="detail-main">
                        <div class="custom-card">
                            <div class="card-header">
                                <h3 class="card-title">Informasi Pembayaran</h3>
                            </div>
                            <div class="card-body">
                                <!-- VA Number Section (if VA) -->
                                <div v-if="pembayaran.va_number" class="mb-4">
                                    <div class="stat-card">
                                        <div class="stat-card__label">Nomor Virtual Account</div>
                                        <div class="stat-card__value font-mono" style="font-size:1.5rem;">{{ pembayaran.va_number }}</div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <div class="stat-card">
                                            <div class="stat-card__label">ID Pembayaran</div>
                                            <div class="stat-card__value">#{{ pembayaran.id }}</div>
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
                                            <div class="stat-card__label">Mahasiswa</div>
                                            <div class="stat-card__value">{{ pembayaran.tagihan?.mahasiswa?.nama_lengkap }}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="stat-card">
                                            <div class="stat-card__label">NIM</div>
                                            <div class="stat-card__value">{{ pembayaran.tagihan?.mahasiswa?.nim }}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="stat-card">
                                            <div class="stat-card__label">Semester</div>
                                            <div class="stat-card__value">{{ pembayaran.tagihan?.semester }}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="stat-card">
                                            <div class="stat-card__label">Tahun Akademik</div>
                                            <div class="stat-card__value">{{ pembayaran.tagihan?.tahun_akademik }}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="stat-card">
                                            <div class="stat-card__label">Jumlah Bayar</div>
                                            <div class="stat-card__value" style="color:var(--primary-color);font-weight:700;">{{ formatRupiah(pembayaran.jumlah_bayar) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="stat-card">
                                            <div class="stat-card__label">Tagihan</div>
                                            <div class="stat-card__value">{{ formatRupiah(pembayaran.tagihan?.nominal) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="stat-card">
                                            <div class="stat-card__label">Metode Pembayaran</div>
                                            <div class="stat-card__value">{{ pembayaran.metode_pembayaran?.nama_metode || '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="stat-card">
                                            <div class="stat-card__label">Nama Pengirim</div>
                                            <div class="stat-card__value">{{ pembayaran.nama_pengirim || '-' }}</div>
                                        </div>
                                    </div>
                                    <div v-if="pembayaran.verified_at" class="col-sm-6">
                                        <div class="stat-card">
                                            <div class="stat-card__label">Tanggal Verifikasi</div>
                                            <div class="stat-card__value">{{ formatDate(pembayaran.verified_at) }}</div>
                                        </div>
                                    </div>
                                    <div v-if="pembayaran.va_expired_at" class="col-sm-6">
                                        <div class="stat-card">
                                            <div class="stat-card__label">Batas Bayar</div>
                                            <div class="stat-card__value">{{ formatDate(pembayaran.va_expired_at) }}</div>
                                        </div>
                                    </div>
                                    <div v-if="beasiswa" class="col-12">
                                        <div class="stat-card" style="background:linear-gradient(135deg,#ecfdf5 0%,#f0fdfa 100%);border-color:#6ee7b7;">
                                            <div class="stat-card__label" style="color:#065f46;"><i class="fas fa-graduation-cap"></i> Beasiswa</div>
                                            <div class="stat-card__value" style="color:#065f46;">{{ beasiswa.nama }} ({{ beasiswa.kode }}) — {{ beasiswa.jenis }}</div>
                                            <div style="font-size:0.8125rem;color:#047857;margin-top:0.25rem;">Potongan Rp {{ Number(beasiswa.diskon).toLocaleString('id-ID') }} · {{ beasiswa.tipe==='persen' ? beasiswa.nilai+'%' : beasiswa.tipe==='full' ? 'Gratis' : 'Rp '+Number(beasiswa.nilai).toLocaleString('id-ID') }} · {{ beasiswa.sumber }} · Status {{ beasiswa.status }}</div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="stat-card">
                                            <div class="stat-card__label">Status</div>
                                            <div class="stat-card__value">
                                                <span :class="{
                                                    'badge-custom': true,
                                                    'badge-paid': pembayaran.status === 'dikonfirmasi',
                                                    'badge-pending': pembayaran.status === 'pending',
                                                    'badge-overdue': pembayaran.status === 'ditolak'
                                                }">{{ pembayaran.status === 'dikonfirmasi' ? 'Dikonfirmasi' : pembayaran.status === 'pending' ? 'Menunggu' : 'Ditolak' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="detail-side">
                        <div class="custom-card">
                            <div class="card-header">
                                <h3 class="card-title">Bukti Pembayaran</h3>
                            </div>
                            <div class="card-body">
                                <div v-if="pembayaran.bukti_pembayaran">
                                    <img :src="'/storage/' + pembayaran.bukti_pembayaran" class="img-fluid rounded" style="border:1px solid var(--gray-200);border-radius:0.75rem;" alt="Bukti Pembayaran" />
                                </div>
                                <div v-else class="empty-bukti">
                                <i class="fas fa-image"></i>
                                <span>Tidak ada bukti pembayaran</span>
                            </div>
                            </div>
                        </div>
                        <div v-if="pembayaran.status === 'pending'" class="custom-card mt-3">
                            <div class="card-header">
                                <h3 class="card-title">Aksi</h3>
                            </div>
                            <div class="card-body" style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                                <button @click="confirm" class="btn btn-success" style="flex:1;">
                                    <i class="fas fa-check"></i> Konfirmasi
                                </button>
                                <button @click="reject" class="btn btn-danger" style="flex:1;">
                                    <i class="fas fa-times"></i> Tolak
                                </button>
                            </div>
                        </div>
                        <div v-if="pembayaran.status === 'dikonfirmasi'" class="custom-card mt-3">
                            <div class="card-header">
                                <h3 class="card-title">Unduh</h3>
                            </div>
                            <div class="card-body" style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                                <Link :href="route('admin.tagihan.invoice', pembayaran.tagihan_id)" class="btn btn-primary" style="flex:1;justify-content:center;">
                                    <i class="fas fa-file-invoice"></i> Invoice
                                </Link>
                            </div>
                        </div>
                        <div v-if="pembayaran.catatan_admin" class="custom-card mt-3">
                            <div class="card-header">
                                <h3 class="card-title">Catatan</h3>
                            </div>
                            <div class="card-body" style="color:var(--danger);">{{ pembayaran.catatan_admin }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.page-heading { display:flex; align-items:center; gap:0.75rem; font-size:1.25rem; font-weight:700; }
.detail-layout { display:grid; grid-template-columns: minmax(0,1fr) 380px; gap:1.5rem; align-items:start; }
.detail-main, .detail-side { min-width:0; }
.custom-card { border:1px solid var(--gray-200); border-radius:1rem; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.05); background:#fff; }
.card-header { padding:1rem 1.25rem; border-bottom:1px solid var(--gray-100); background:linear-gradient(to bottom,#fff,#f9fafb); display:flex; justify-content:space-between; align-items:center; }
.card-title { font-size:1rem; font-weight:700; margin:0; color:var(--gray-900); }
.card-body { padding:1.25rem; }
.stat-card { background:#f8fafc; border:1px solid var(--gray-200); border-radius:0.75rem; padding:0.875rem 1rem; }
.stat-card__label { font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--gray-500); margin-bottom:0.25rem; }
.stat-card__value { font-size:0.9375rem; font-weight:700; color:var(--gray-900); word-break:break-word; }
.font-mono { font-family: ui-monospace, monospace; }
.badge-custom { display:inline-flex; padding:0.25rem 0.625rem; border-radius:9999px; font-size:0.75rem; font-weight:700; }
.badge-paid { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; }
.badge-pending { background:#fef3c7; color:#92400e; border:1px solid #fcd34d; }
.badge-overdue { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
.empty-bukti { color:var(--gray-500); text-align:center; padding:2rem 0; display:flex; align-items:center; justify-content:center; gap:0.5rem; flex-direction:column; }
.empty-bukti i { font-size:1.5rem; color:var(--gray-300); }
.mt-3 { margin-top:1rem; }
@media (max-width: 1024px) { .detail-layout { grid-template-columns: minmax(0,1fr) 340px; } }
@media (max-width: 900px) { .detail-layout { grid-template-columns: 1fr; } }
@media (max-width: 640px) {
    .container-xl { padding-left:1rem; padding-right:1rem; }
    .page-heading { font-size:1.1rem; }
    .card-body { padding:1rem; }
    .detail-layout { gap:1rem; }
}
</style>
