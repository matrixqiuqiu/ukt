<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { formatRupiah, formatDate } from '@/utils';

const page = usePage();

const props = defineProps({
    pembayaran: Object,
    tagihan: Object,
    mahasiswa: Object,
    canPrint: Boolean,
    institution: Object,
    header_image: String,
    verificationUrl: String,
    qrCode: String,
    invoiceNumber: String,
});

const isAdmin = page.props.auth?.user?.role === 'admin';

const backHref = isAdmin
    ? route('admin.tagihan.index')
    : route('mahasiswa.tagihan.index');

const printHref = isAdmin
    ? route('admin.tagihan.print', props.pembayaran?.tagihan_id || props.tagihan?.id)
    : route('mahasiswa.tagihan.print', props.pembayaran?.tagihan_id || props.tagihan?.id);

const printPage = () => window.print();

const metode = computed(() => props.pembayaran?.metode_pembayaran || null);

const isPaymentExpired = computed(() => {
    const s = props.pembayaran?.status;
    if (s === 'expired') return true;
    if (s === 'pending' && props.pembayaran?.va_expired_at) {
        return new Date(props.pembayaran.va_expired_at).getTime() <= Date.now();
    }
    return false;
});

const statusBadge = computed(() => {
    const s = props.pembayaran?.status;
    if (s === 'dikonfirmasi') return { text: 'Dikonfirmasi', cls: 'badge-success' };
    if (s === 'ditolak') return { text: 'Ditolak', cls: 'badge-danger' };
    if (s === 'expired' || isPaymentExpired.value) return { text: 'Expired', cls: 'badge-danger' };
    return { text: 'Menunggu Pembayaran', cls: 'badge-warning' };
});

const statusText = computed(() => {
    const s = props.pembayaran?.status;
    if (s === 'dikonfirmasi') return 'Sudah Bayar';
    if (s === 'expired' || isPaymentExpired.value) return 'Expired';
    return 'Menunggu Pembayaran';
});

// Track logo URLs that fail to load so the fallback icon can be shown instead.
const brokenLogos = ref({});
const onLogoError = (event) => {
    brokenLogos.value = { ...brokenLogos.value, [event.target.src]: true };
};

const paymentCode = () => {
    return props.pembayaran?.va_number || props.pembayaran?.id || '-';
};

// Generic instruction shown when the bank record has no custom instruction set.
const fallbackInstruksi = computed(() => {
    if (!metode.value) return '';
    if (metode.value.kategori === 'virtual_account') {
        return `Pembayaran dapat dilakukan melalui ATM, Mobile Banking, atau Internet Banking menggunakan nomor Virtual Account di atas.`;
    }
    return `Transfer dapat dilakukan melalui ATM, Mobile Banking, atau Internet Banking ke nomor rekening di atas.`;
});
</script>

<template>
    <Head title="Slip Pembayaran UKT" />
    <AuthenticatedLayout>
        <template #header>
            <div class="page-heading">
                <Link :href="backHref" class="btn btn-light btn-sm">&larr; Kembali</Link>
                <span>Slip Pembayaran</span>
                <div style="display:inline-flex;gap:0.5rem;">
                    <button type="button" class="btn btn-light btn-sm" @click="printPage">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <Link :href="printHref" class="btn btn-success btn-sm" target="_blank">
                        <i class="fas fa-download"></i> Download PDF
                    </Link>
                </div>
            </div>
        </template>
        
        <div class="page-body">
            <div class="container-xl">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="custom-card">
                            <div class="card-header bg-white">
                                <h3 class="card-title mb-0">SLIP PEMBAYARAN UKT</h3>
                                <span class="badge" :class="statusBadge.cls">{{ statusBadge.text }}</span>
                            </div>
                            <div class="card-body">
                                 <div class="text-center mb-4" v-if="header_image">
                                     <img :src="header_image" alt="Kop Surat" class="img-fluid" style="max-height:120px;object-fit:contain;" />
                                 </div>
                                 <div v-else class="text-center mb-4">
                                     <h4 class="mb-1 fw-bold">{{ institution?.name || $page.props.theme?.website_name || 'UKT System' }}</h4>
                                     <p class="mb-0 text-muted small">
                                         {{ institution?.address || $page.props.theme?.invoice_institution_address || '' }}
                                     </p>
                                     <p class="mb-0 text-muted small">
                                         Tel: {{ institution?.phone || '-' }} |
                                         Email: {{ institution?.email || '-' }} |
                                         Web: {{ institution?.website || '-' }}
                                     </p>
                                 </div>

                                <div class="row mb-4">
                                    <div class="col-12">
                                        <table class="table table-sm mb-0">
                                            <tr>
                                                <td class="fw-medium">KODE PEMBAYARAN</td>
                                                <td class="text-end text-primary fw-bold" style="font-family: monospace;">{{ paymentCode() }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-medium">UKT</td>
                                                <td class="text-end text-primary fw-bold">{{ formatRupiah(pembayaran?.jumlah_bayar || tagihan?.nominal) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-medium">STATUS PEMBAYARAN</td>
                                                <td class="text-end fw-bold" :class="statusText === 'Sudah Bayar' ? 'text-success' : statusText === 'Expired' ? 'text-danger' : 'text-warning'">{{ statusText }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-medium">KETERANGAN</td>
                                                <td class="text-end">{{ mahasiswa?.nama_lengkap }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-medium">BERLAKU HINGGA</td>
                                                <td class="text-end">{{ formatDate(tagihan?.jatuh_tempo) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-medium">Nomor Pendaftaran</td>
                                                <td class="text-end">{{ mahasiswa?.id }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-medium">Nama</td>
                                                <td class="text-end">{{ mahasiswa?.nama_lengkap }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-medium">Program Studi</td>
                                                <td class="text-end">{{ mahasiswa?.program_studi || mahasiswa?.jurusan }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-12">
                                        <table class="table table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>TANGGAL</th>
                                                    <th class="text-center">JUMLAH BAYAR</th>
                                                    <th class="text-center">METODE PEMBAYARAN</th>
                                                    <th class="text-center">VA NUMBER</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>{{ formatDate(pembayaran?.created_at) }}</td>
                                                    <td class="text-center fw-bold">{{ formatRupiah(pembayaran?.jumlah_bayar) }}</td>
                                                    <td class="text-center">
                                                        <span class="invoice-method-cell">
                                                            <img
                                                                v-if="metode?.logo && !brokenLogos[metode.logo]"
                                                                :src="metode.logo"
                                                                alt="Logo {{ metode?.nama_metode }}"
                                                                class="invoice-method-logo"
                                                                @error="onLogoError"
                                                            />
                                                            {{ metode?.nama_metode || 'Virtual Account' }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center" style="font-family: monospace;">{{ pembayaran?.va_number || '-' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div v-if="metode" class="alert alert-info invoice-bank-box">
                                    <div class="invoice-bank-head">
                                        <img
                                            v-if="metode.logo && !brokenLogos[metode.logo]"
                                            :src="metode.logo"
                                            alt="Logo {{ metode.nama_metode }}"
                                            class="invoice-bank-logo"
                                            @error="onLogoError"
                                        />
                                        <i v-else class="fas fa-university invoice-bank-icon"></i>
                                        <strong>{{ metode.nama_metode }}</strong>
                                    </div>
                                    <p class="mb-0 mt-2 small">
                                        {{ metode.instruksi || fallbackInstruksi }}
                                    </p>
                                </div>

                                <!-- QR Verifikasi -->
                                <div v-if="qrCode" class="invoice-verify">
                                    <div class="invoice-verify__qr">
                                        <img :src="qrCode" alt="QR Verifikasi" />
                                        <div class="invoice-verify__hint">Scan untuk verifikasi</div>
                                    </div>
                                    <div class="invoice-verify__info">
                                        <div class="invoice-verify__label">Verifikasi Keaslian</div>
                                        <div class="invoice-verify__desc">Pindai QR atau buka tautan untuk memastikan dokumen asli:</div>
                                        <a :href="verificationUrl" target="_blank" class="invoice-verify__url">{{ verificationUrl }}</a>
                                        <div class="invoice-verify__meta">{{ invoiceNumber }} · {{ mahasiswa?.nim }} · {{ formatRupiah(pembayaran?.jumlah_bayar || tagihan?.nominal) }}</div>
                                    </div>
                                    <div class="invoice-verify__sig">
                                        <div>Mataram, {{ formatDate(new Date().toISOString()) }}</div>
                                        <div class="fw-bold">Bagian Keuangan</div>
                                        <div class="sig-line">Stempel & Tanda Tangan</div>
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

<style scoped>
/* --- Utility classes (Bootstrap-style, tidak ada di CSS global) --- */
.card-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}
.badge-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
}
.badge-warning {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fcd34d;
}
.badge-danger {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}
.bg-white {
    background: #ffffff;
}
.mb-0 {
    margin-bottom: 0 !important;
}
.mb-1 {
    margin-bottom: 0.25rem !important;
}
.mb-4 {
    margin-bottom: 1.5rem !important;
}
.fw-bold {
    font-weight: 700 !important;
}
.fw-medium {
    font-weight: 500 !important;
}
.small {
    font-size: 0.8125rem !important;
}
.img-fluid {
    max-width: 100%;
    height: auto;
}
.text-danger {
    color: #dc2626 !important;
}
.justify-content-center {
    justify-content: center !important;
}

/* --- Table --- */
.table {
    font-size: 0.875rem;
}
.table-sm th,
.table-sm td {
    padding: 0.4rem 0.5rem;
}
.table-bordered {
    border: 1px solid #e5e7eb;
}
.table-bordered > :not(caption) > * > * {
    border: 1px solid #e5e7eb;
}
.table-light {
    background: #f8fafc;
}
.table-light th {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #475569;
}

/* --- Bank info box --- */
.alert-info {
    background: #e0f2fe;
    border: 1px solid #7dd3fc;
    color: #0c4a6e;
}

.invoice-bank-box {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.invoice-bank-head {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.invoice-bank-logo {
    width: 40px;
    height: 40px;
    object-fit: contain;
    border-radius: 0.375rem;
    background: white;
    border: 1px solid var(--gray-200);
    padding: 0.25rem;
}

.invoice-bank-icon {
    font-size: 1.5rem;
    color: var(--info);
}

.invoice-method-cell {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.invoice-method-logo {
    width: 24px;
    height: 24px;
    object-fit: contain;
}
.invoice-verify {
    border: 1.5px solid var(--primary, #4f46e5);
    border-radius: 0.9rem;
    padding: 1rem;
    margin-top: 1rem;
    background: #f8fafc;
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    flex-wrap: wrap;
}
.invoice-verify__qr { text-align: center; flex-shrink:0; }
.invoice-verify__qr img { width: 92px; height: 92px; border: 1px solid #e2e8f0; border-radius: 0.5rem; background:#fff; padding:4px; }
.invoice-verify__hint { font-size: 0.65rem; color: #64748b; margin-top: 0.25rem; }
.invoice-verify__info { flex:1; min-width: 180px; }
.invoice-verify__label { font-size: 0.75rem; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; color:#64748b; margin-bottom:0.25rem; }
.invoice-verify__desc { font-size: 0.8125rem; color:#334155; }
.invoice-verify__url { font-size: 0.7rem; color:#1e40af; word-break:break-all; display:block; margin-top:0.25rem; }
.invoice-verify__meta { font-size: 0.7rem; color:#94a3b8; margin-top:0.5rem; font-family: monospace; }
.invoice-verify__sig { min-width: 160px; text-align:center; font-size:0.8125rem; color:#334155; }
.invoice-verify__sig .sig-line { border-top:1px solid #334155; width:160px; margin:48px auto 0; padding-top:6px; font-size:0.75rem; }
</style>

<style>
@media print {
    .sidebar, .navbar, .bottom-nav, .content__header, .page-heading { display: none !important; }
    .app-shell, .app-shell__main, .page.content, .content__container, .page-body, .container-xl, .row, .col-lg-8 { margin:0 !important; padding:0 !important; max-width:100% !important; width:100% !important; background:#fff !important; }
    .custom-card { box-shadow:none !important; border:1px solid #e2e8f0 !important; border-radius:0 !important; }
    .card-header .badge, .btn, button, a.btn { display:none !important; }
    .invoice-verify { break-inside: avoid; }
    body { background:#fff !important; color:#000 !important; }
    @page { size: A4; margin: 14mm; }
}
</style>