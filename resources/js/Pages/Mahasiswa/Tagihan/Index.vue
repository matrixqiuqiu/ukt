<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { formatRupiah, formatDate } from '@/utils';
import { computed } from 'vue';

const props = defineProps({
    tagihans: Object,
    semesterAktif: Object,
});

const isExpiredPayment = (p) => {
    if (p.status === 'expired') return true;
    if (p.status === 'pending' && p.va_expired_at) {
        return new Date(p.va_expired_at).getTime() <= Date.now();
    }
    return false;
};

const getTagihanStatus = (tagihan) => {
    if (tagihan.status === 'sudah_dibayar') {
        return { label: 'Lunas', class: 'm-badge-success' };
    }

    const confirmedPayment = tagihan.pembayarans?.find(p => p.status === 'dikonfirmasi');
    if (confirmedPayment) {
        return { label: 'Lunas', class: 'm-badge-success' };
    }

    // Dispensasi disetujui keuangan -> status Dispen
    if (tagihan.status === 'dispen') {
        return { label: 'Dispen', class: 'm-badge-dispen' };
    }

    const expiredPayment = tagihan.pembayarans?.find(p => isExpiredPayment(p));
    if (expiredPayment) {
        return { label: 'Expired', class: 'm-badge-danger' };
    }

    const pendingPayment = tagihan.pembayarans?.find(p => p.status === 'pending');
    if (pendingPayment) {
        return { label: 'Menunggu Konfirmasi', class: 'm-badge-warning' };
    }

    if (tagihan.status === 'terlambat') {
        return { label: 'Terlambat', class: 'm-badge-danger' };
    }

    return { label: 'Belum Dibayar', class: 'm-badge-danger' };
};

const getAksi = (tagihan) => {
    const confirmedPayment = tagihan.pembayarans?.find(p => p.status === 'dikonfirmasi');
    if (confirmedPayment) {
        return { 
            show: true, 
            label: 'Lihat Bukti', 
            href: route('mahasiswa.pembayaran.show', confirmedPayment.id), 
            class: 'm-btn-success' 
        };
    }

    const expiredPayment = tagihan.pembayarans?.find(p => isExpiredPayment(p));
    if (expiredPayment) {
        return { show: true, label: 'Bayar Ulang', href: route('mahasiswa.tagihan.show', tagihan.id), class: 'm-btn-success' };
    }

    const pendingPayment = tagihan.pembayarans?.find(p => p.status === 'pending');
    if (pendingPayment) {
        return { show: true, label: 'Lihat Pembayaran', href: route('mahasiswa.pembayaran.show', pendingPayment.id), class: 'm-btn-warning' };
    }

    return { show: true, label: 'Bayar', href: route('mahasiswa.tagihan.show', tagihan.id), class: 'm-btn-success' };
};

const getInvoiceLink = (tagihan) => {
    const confirmedPayment = tagihan.pembayarans?.find(p => p.status === 'dikonfirmasi');
    if (confirmedPayment) {
        return route('mahasiswa.tagihan.invoice', tagihan.id);
    }
    return null;
};

const isLunas = (tagihan) => {
    return tagihan.status === 'sudah_dibayar' || tagihan.pembayarans?.some(p => p.status === 'dikonfirmasi');
};

const canDispensasi = (tagihan) => {
    if (tagihan.status === 'dispen') return false;
    return !isLunas(tagihan);
};

const totalBelumBayar = computed(() => {
    return props.tagihans.data?.filter(t => !isLunas(t)).length || 0;
});
</script>

<template>
    <Head title="Tagihan UKT" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="page-heading">Tagihan UKT</h2>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <!-- Semester Info -->
                <div v-if="semesterAktif" class="semester-banner">
                    <div class="semester-info">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Semester Aktif: <strong>{{ semesterAktif.tahun_akademik }}</strong></span>
                    </div>
                    <div class="semester-deadline">
                        <i class="fas fa-clock"></i>
                        <span>Jatuh Tempo: <strong>{{ formatDate(semesterAktif.jatuh_tempo) }}</strong></span>
                    </div>
                </div>

                <!-- Summary -->
                <div class="summary-row" v-if="tagihans.data.length > 0">
                    <div class="summary-card summary-total">
                        <div class="summary-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                        <div class="summary-text">
                            <div class="summary-value">{{ tagihans.total }}</div>
                            <div class="summary-label">Total Tagihan</div>
                        </div>
                    </div>
                    <div class="summary-card summary-unpaid">
                        <div class="summary-icon"><i class="fas fa-exclamation-circle"></i></div>
                        <div class="summary-text">
                            <div class="summary-value">{{ totalBelumBayar }}</div>
                            <div class="summary-label">Belum Dibayar</div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="m-card">
                    <div class="m-card-header">
                        <h3 class="m-card-title">Daftar Tagihan UKT</h3>
                    </div>
                    <div class="m-card-body">
                        <div v-if="tagihans.data.length === 0" class="empty-state">
                            <i class="fas fa-check-circle empty-icon"></i>
                            <p>Belum ada tagihan untuk semester ini.</p>
                        </div>
                        <div v-else class="table-responsive">
                            <table class="m-data-table">
                                <thead>
                                    <tr>
                                        <th>Semester</th>
                                        <th>Tahun Akademik</th>
                                        <th>Nominal</th>
                                        <th>Jatuh Tempo</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="t in tagihans.data" :key="t.id" :class="{ 'row-paid': isLunas(t) }">
                                        <td>{{ t.semester }}<div v-if="t.beasiswa" class="m-badge m-badge-success" style="font-size:0.6rem;margin-top:0.2rem;display:inline-flex;gap:0.25rem;"><i class="fas fa-graduation-cap"></i> {{ t.beasiswa.kode }}</div></td>
                                        <td>{{ t.tahun_akademik }}</td>
                                        <td>
                                            <strong>{{ formatRupiah(t.nominal) }}</strong>
                                            <div v-if="t.beasiswa" style="font-size:0.7rem;color:#059669;"><i class="fas fa-tag"></i> {{ t.beasiswa.nama }} — potongan Rp {{ Number(t.beasiswa.diskon).toLocaleString('id-ID') }} <span class="m-badge m-badge-success" style="font-size:0.6rem;">{{ t.beasiswa.jenis }}</span></div>
                                            <div v-else-if="t.keterangan && t.keterangan.includes('Beasiswa')" style="font-size:0.7rem;color:#059669;"><i class="fas fa-graduation-cap"></i> {{ t.keterangan }}</div>
                                        </td>
                                        <td>{{ formatDate(t.jatuh_tempo) }}</td>
                                        <td>
                                            <span class="m-badge" :class="getTagihanStatus(t).class">
                                                <i v-if="isLunas(t)" class="fas fa-check-circle" style="margin-right:0.25rem;"></i>
                                                {{ getTagihanStatus(t).label }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <Link v-if="getAksi(t).show" :href="getAksi(t).href" class="m-btn m-btn--sm" :class="getAksi(t).class">
                                                    {{ getAksi(t).label }}
                                                </Link>
                                                <Link
                                                    v-if="canDispensasi(t)"
                                                    :href="route('mahasiswa.dispensasi.index')"
                                                    class="m-btn m-btn--sm m-btn-warning"
                                                    title="Ajukan perpanjangan tempo pembayaran"
                                                >
                                                    <i class="fas fa-clock"></i> Dispensasi
                                                </Link>
                                                <Link v-if="getInvoiceLink(t)" :href="getInvoiceLink(t)" class="m-btn m-btn--sm m-btn--outline">
                                                    <i class="fas fa-file-invoice"></i> Invoice
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="tagihans.data.length > 0" class="pagination-wrap">
                            <span class="pagination-info">
                                Menampilkan {{ tagihans.from }}-{{ tagihans.to }} dari {{ tagihans.total }} data
                            </span>
                            <div class="pagination">
                                <template v-for="link in tagihans.links" :key="link.label">
                                    <span v-if="!link.url" class="page-item disabled">
                                        <span class="page-link" v-html="link.label"></span>
                                    </span>
                                    <span v-else class="page-item" :class="{ active: link.active }">
                                        <Link :href="link.url" class="page-link" v-html="link.label" preserve-state />
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.m-badge-dispen {
    background: #dbeafe;
    color: #1d4ed8;
    border: 1px solid #93c5fd;
}
.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.semester-banner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: 1rem;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--shadow-sm);
}
.semester-info, .semester-deadline {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--gray-700);
}
.semester-info i, .semester-deadline i {
    color: var(--primary);
    font-size: 1rem;
}
.semester-deadline i {
    color: var(--warning);
}
.summary-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.summary-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: 1rem;
    padding: 1.25rem 1.5rem;
    box-shadow: var(--shadow-sm);
}
.summary-icon {
    width: 48px;
    height: 48px;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}
.summary-total .summary-icon {
    background: rgba(79, 70, 229, 0.1);
    color: var(--primary);
}
.summary-unpaid .summary-icon {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
}
.summary-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--gray-900);
}
.summary-label {
    font-size: 0.8125rem;
    color: var(--gray-500);
}
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--gray-500);
}
.empty-icon {
    font-size: 3rem;
    color: var(--success);
    margin-bottom: 1rem;
    display: block;
}
.row-paid {
    background: #f0fdf4 !important;
}
.row-paid td {
    color: var(--gray-600);
}
.pagination-wrap {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--gray-100);
}
.pagination-info {
    font-size: 0.8125rem;
    color: var(--gray-600);
}
.pagination {
    display: flex;
    gap: 0.25rem;
}
.page-item {
    display: inline-flex;
}
.page-link {
    padding: 0.375rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    color: var(--gray-700);
    text-decoration: none;
    border: 1px solid var(--gray-200);
    transition: all 0.2s;
}
.page-link:hover {
    background: var(--gray-50);
}
.page-item.active .page-link {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}
.page-item.disabled .page-link {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
