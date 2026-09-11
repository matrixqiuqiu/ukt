<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { formatRupiah } from '@/utils';

const props = defineProps({
    summary: Object,
});
</script>

<template>
    <Head title="Ringkasan & Rekapitulasi Verifikasi" />
    <AuthenticatedLayout>
        <template #header>
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div>
                    <h2 class="page-heading" style="font-size:1.375rem;font-weight:700;color:#0f172a;margin-bottom:0.25rem;">
                        Rekapitulasi Verifikasi Pembayaran
                    </h2>
                    <p style="font-size:0.875rem;color:#64748b;margin:0;">
                        Statistik transaksi terkonfirmasi, antrean tertunda, dan total pendapatan UKT
                    </p>
                </div>
                <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                    <Link :href="route('admin.verifikasi.index')" class="solid-btn btn-indigo-solid">
                        <i class="fas fa-arrow-left"></i> Antrean Verifikasi
                    </Link>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">
                <!-- 4 KPI Cards -->
                <div class="summary-grid">
                    <div class="summary-card card-amber">
                        <div class="summary-icon-box bg-amber-light text-amber-dark">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="summary-info">
                            <div class="summary-label">Menunggu Verifikasi</div>
                            <div class="summary-value" style="color:#d97706;">{{ summary.pending }}</div>
                            <div class="summary-sub">Transaksi perlu diperiksa</div>
                        </div>
                    </div>

                    <div class="summary-card card-emerald">
                        <div class="summary-icon-box bg-emerald-light text-emerald-dark">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="summary-info">
                            <div class="summary-label">Dikonfirmasi Lunas</div>
                            <div class="summary-value" style="color:#059669;">{{ summary.confirmed }}</div>
                            <div class="summary-sub">Pembayaran telah sah</div>
                        </div>
                    </div>

                    <div class="summary-card card-rose">
                        <div class="summary-icon-box bg-rose-light text-rose-dark">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="summary-info">
                            <div class="summary-label">Ditolak / Dibatalkan</div>
                            <div class="summary-value" style="color:#dc2626;">{{ summary.rejected }}</div>
                            <div class="summary-sub">Bukti tidak valid</div>
                        </div>
                    </div>

                    <div class="summary-card card-blue">
                        <div class="summary-icon-box bg-blue-light text-blue-dark">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="summary-info">
                            <div class="summary-label">Total Realisasi Pendapatan</div>
                            <div class="summary-value" style="color:#2563eb;font-size:1.15rem;">{{ formatRupiah(summary.totalPendapatan) }}</div>
                            <div class="summary-sub">Akumulasi pembayaran sah</div>
                        </div>
                    </div>
                </div>

                <!-- Info Navigation Card -->
                <div class="data-card" style="margin-top:1.5rem;padding:1.5rem;text-align:center;">
                    <i class="fas fa-file-invoice-dollar" style="font-size:2.5rem;color:#4f46e5;margin-bottom:0.75rem;display:block;"></i>
                    <h3 style="font-size:1.125rem;font-weight:700;color:#0f172a;margin:0 0 0.375rem;">Kelola dan Verifikasi Pembayaran Mahasiswa</h3>
                    <p style="font-size:0.875rem;color:#64748b;max-width:560px;margin:0 auto 1.25rem;">
                        Periksa keabsahan bukti transfer struk bank dan mutasi rekening pada tab antrean verifikasi untuk mengubah status tagihan mahasiswa menjadi lunas.
                    </p>
                    <div style="display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap;">
                        <Link :href="route('admin.verifikasi.index')" class="solid-btn btn-indigo-solid">
                            <i class="fas fa-tasks"></i> Buka Antrean Verifikasi
                        </Link>
                        <Link :href="route('admin.pembayaran.index')" class="solid-btn btn-white-border">
                            <i class="fas fa-list"></i> Riwayat Pembayaran Lengkap
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}
@media (max-width: 1024px) {
    .summary-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 640px) {
    .summary-grid {
        grid-template-columns: 1fr;
    }
}

.summary-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
}
.summary-icon-box {
    width: 48px;
    height: 48px;
    border-radius: 0.625rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.375rem;
    flex-shrink: 0;
}
.bg-amber-light { background: #fffbeb; }
.text-amber-dark { color: #d97706; }
.bg-emerald-light { background: #ecfdf5; }
.text-emerald-dark { color: #059669; }
.bg-rose-light { background: #fef2f2; }
.text-rose-dark { color: #dc2626; }
.bg-blue-light { background: #eff6ff; }
.text-blue-dark { color: #2563eb; }

.summary-info {
    min-width: 0;
}
.summary-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin-bottom: 0.125rem;
}
.summary-value {
    font-size: 1.5rem;
    font-weight: 800;
    line-height: 1.2;
}
.summary-sub {
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 0.125rem;
}

.data-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
}

.solid-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.875rem;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: opacity 0.15s;
}
.solid-btn:hover {
    opacity: 0.9;
}
.btn-indigo-solid {
    background: #4f46e5;
    color: #ffffff;
}
.btn-white-border {
    background: #ffffff;
    color: #334155;
    border: 1px solid #cbd5e1;
}
.btn-white-border:hover {
    background: #f8fafc;
}
</style>
