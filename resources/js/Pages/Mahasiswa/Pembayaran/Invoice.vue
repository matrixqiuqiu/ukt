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

const isAdmin = computed(() => page.props.auth?.user?.role === 'admin');

const backHref = computed(() => {
    if (isAdmin.value) {
        return props.tagihan?.id
            ? route('admin.tagihan.show', props.tagihan.id)
            : route('admin.tagihan.index');
    }
    return props.tagihan?.id
        ? route('mahasiswa.tagihan.show', props.tagihan.id)
        : route('mahasiswa.tagihan.index');
});

const printHref = computed(() => {
    const id = props.tagihan?.id || props.pembayaran?.tagihan_id;
    if (isAdmin.value) {
        return route('admin.tagihan.print', id);
    }
    return route('mahasiswa.tagihan.print', id);
});

const printPage = () => {
    window.print();
};

const metode = computed(() => props.pembayaran?.metode_pembayaran || props.pembayaran?.metodePembayaran || null);

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
    if (s === 'dikonfirmasi') return { text: 'LUNAS / DIKONFIRMASI', cls: 'badge-solid-success', icon: 'fas fa-check-circle' };
    if (s === 'ditolak') return { text: 'DITOLAK', cls: 'badge-solid-danger', icon: 'fas fa-times-circle' };
    if (s === 'expired' || isPaymentExpired.value) return { text: 'KEDALUWARSA (EXPIRED)', cls: 'badge-solid-danger', icon: 'fas fa-hourglass-end' };
    return { text: 'MENUNGGU PEMBAYARAN', cls: 'badge-solid-warning', icon: 'fas fa-clock' };
});

const paymentCode = computed(() => {
    return props.pembayaran?.va_number || props.pembayaran?.kode_transaksi || props.pembayaran?.id || '-';
});

const brokenLogos = ref({});
const onLogoError = (event) => {
    brokenLogos.value = { ...brokenLogos.value, [event.target.src]: true };
};

const effectiveInvoiceNo = computed(() => {
    return props.invoiceNumber || ('INV-UKT-' + String(props.pembayaran?.id || props.tagihan?.id || '000000').padStart(6, '0'));
});

const fallbackInstruksi = computed(() => {
    if (!metode.value) return '';
    if (metode.value.kategori === 'virtual_account') {
        return `Pembayaran dapat dilakukan melalui ATM, Mobile Banking, atau Internet Banking menggunakan nomor Virtual Account resmi di atas.`;
    }
    return `Transfer pembayaran dapat dilakukan melalui ATM, Mobile Banking, atau Teller Bank ke nomor rekening resmi di atas.`;
});
</script>

<template>
    <Head :title="`Slip Faktur Pembayaran UKT - ${mahasiswa?.nama_lengkap || ''}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="invoice-page-header no-print">
                <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                    <Link :href="backHref" class="solid-btn btn-white-border" title="Kembali">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </Link>
                    <div>
                        <h2 style="font-size:1.25rem;font-weight:700;color:#0f172a;margin:0;">
                            Lembar Faktur & Bukti Pembayaran UKT
                        </h2>
                        <p style="font-size:0.8125rem;color:#64748b;margin:0;">
                            No. Dokumen: <strong style="font-family:monospace;color:#0f172a;">{{ effectiveInvoiceNo }}</strong> &bull; Mahasiswa: {{ mahasiswa?.nama_lengkap }}
                        </p>
                    </div>
                </div>

                <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                    <button type="button" class="solid-btn btn-white-border" @click="printPage" title="Cetak Cepat Halaman Web (Browser Print)">
                        <i class="fas fa-print"></i> Cetak Cepat (Web)
                    </button>
                    <a :href="printHref" target="_blank" class="solid-btn btn-indigo-solid" title="Buka Pratinjau PDF Stream Resmi untuk Dilihat & Dicetak">
                        <i class="fas fa-file-pdf"></i> Lihat / Cetak PDF Stream
                    </a>
                </div>
            </div>
        </template>

        <div class="page-body">
            <div class="container-xl">
                <!-- Outer Document Container -->
                <div class="invoice-container-wrapper">
                    <div class="invoice-paper">
                        <!-- 1. OFFICIAL LETTERHEAD (KOP SURAT) -->
                        <div v-if="header_image" class="invoice-header-banner">
                            <img :src="header_image" alt="Kop Surat Resmi" class="banner-img" />
                        </div>
                        <div v-else class="invoice-kop-surat">
                            <div class="kop-left">
                                <div class="kop-logo-box">
                                    <img
                                        v-if="$page.props.theme?.logo_url || $page.props.theme?.invoice_logo"
                                        :src="$page.props.theme?.logo_url || $page.props.theme?.invoice_logo"
                                        alt="Logo Kampus"
                                        class="kop-logo-img"
                                    />
                                    <i v-else class="fas fa-university kop-fallback-icon"></i>
                                </div>
                                <div class="kop-text">
                                    <h1 class="kop-institution-name">{{ institution?.name || $page.props.theme?.website_name || 'UNIVERSITAS BUMI GORA' }}</h1>
                                    <p class="kop-institution-sub">BAGIAN KEUANGAN & ADMINISTRASI AKADEMIK (UKT ONLINE)</p>
                                    <p class="kop-institution-meta">
                                        {{ institution?.address || $page.props.theme?.invoice_institution_address || 'Jl. Ismail Marzuki No. 22, Mataram, Nusa Tenggara Barat' }}
                                    </p>
                                    <p class="kop-institution-meta">
                                        Telp: {{ institution?.phone || '(0370) 638369' }} &bull; Email: {{ institution?.email || 'keuangan@ubg.ac.id' }} &bull; Web: {{ institution?.website || 'https://ubg.ac.id' }}
                                    </p>
                                </div>
                            </div>
                            <div class="kop-right">
                                <div class="kop-doc-badge">FAKTUR RESMI</div>
                                <div class="kop-doc-no">{{ effectiveInvoiceNo }}</div>
                                <div class="kop-doc-date">{{ formatDate(pembayaran?.created_at || new Date().toISOString()) }}</div>
                            </div>
                        </div>

                        <div class="kop-divider">
                            <div class="divider-thick"></div>
                            <div class="divider-thin"></div>
                        </div>

                        <!-- 2. DOCUMENT TITLE & STATUS BAR -->
                        <div class="invoice-title-bar">
                            <div class="title-meta">
                                <h2 class="invoice-heading">BUKTI PEMBAYARAN UANG KULIAH TUNGGAL</h2>
                                <p class="invoice-subheading">
                                    Semester {{ tagihan?.semester }} &bull; Tahun Akademik {{ tagihan?.tahun_akademik }}
                                </p>
                            </div>
                            <div class="status-pill-wrapper">
                                <span :class="['solid-badge', statusBadge.cls]" style="font-size:0.875rem;padding:0.45rem 1rem;">
                                    <i :class="statusBadge.icon"></i>
                                    <span style="margin-left:0.4rem;letter-spacing:0.02em;">{{ statusBadge.text }}</span>
                                </span>
                            </div>
                        </div>

                        <!-- 3. STUDENT & TRANSACTION METADATA GRID -->
                        <div class="invoice-meta-grid">
                            <!-- Left: Data Mahasiswa -->
                            <div class="meta-box">
                                <div class="meta-box-header">
                                    <i class="fas fa-user-graduate"></i>
                                    <span>DATA MAHASISWA</span>
                                </div>
                                <div class="meta-box-body">
                                    <div class="meta-row">
                                        <span class="meta-label">Nama Lengkap</span>
                                        <span class="meta-value font-semibold">{{ mahasiswa?.nama_lengkap || '-' }}</span>
                                    </div>
                                    <div class="meta-row">
                                        <span class="meta-label">NIM</span>
                                        <span class="meta-value font-mono">{{ mahasiswa?.nim || '-' }}</span>
                                    </div>
                                    <div class="meta-row">
                                        <span class="meta-label">Program Studi</span>
                                        <span class="meta-value">{{ mahasiswa?.jurusan || mahasiswa?.program_studi || '-' }}</span>
                                    </div>
                                    <div class="meta-row">
                                        <span class="meta-label">Angkatan / Kelas</span>
                                        <span class="meta-value">{{ mahasiswa?.angkatan || '-' }} / {{ mahasiswa?.kelas || 'Reguler' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Data Transaksi / Tagihan -->
                            <div class="meta-box">
                                <div class="meta-box-header">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                    <span>INFORMASI TAGIHAN & PEMBAYARAN</span>
                                </div>
                                <div class="meta-box-body">
                                    <div class="meta-row">
                                        <span class="meta-label">Kode Transaksi / VA</span>
                                        <span class="meta-value font-mono text-indigo">{{ paymentCode }}</span>
                                    </div>
                                    <div class="meta-row">
                                        <span class="meta-label">Metode Pembayaran</span>
                                        <span class="meta-value">
                                            <span v-if="metode?.nama_metode" style="font-weight:600;">{{ metode.nama_metode }}</span>
                                            <span v-else>Virtual Account / Bank Transfer</span>
                                        </span>
                                    </div>
                                    <div class="meta-row">
                                        <span class="meta-label">Waktu Transaksi</span>
                                        <span class="meta-value">{{ formatDate(pembayaran?.created_at || tagihan?.created_at, true) }}</span>
                                    </div>
                                    <div class="meta-row">
                                        <span class="meta-label">Jatuh Tempo</span>
                                        <span class="meta-value">{{ formatDate(tagihan?.jatuh_tempo) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. INVOICE BREAKDOWN TABLE -->
                        <div class="invoice-table-section">
                            <table class="invoice-table">
                                <thead>
                                    <tr>
                                        <th style="width:6%;text-align:center;">No</th>
                                        <th>Rincian Pembayaran</th>
                                        <th style="text-align:center;width:28%;">Periode / Semester</th>
                                        <th style="text-align:right;width:24%;">Jumlah (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="text-align:center;font-weight:600;color:#64748b;">1</td>
                                        <td>
                                            <div style="font-weight:700;color:#0f172a;font-size:0.9375rem;">
                                                Uang Kuliah Tunggal (UKT) Mahasiswa
                                            </div>
                                            <div style="font-size:0.8125rem;color:#64748b;margin-top:0.15rem;">
                                                Tagihan semester aktif mahasiswa program sarjana / diploma
                                            </div>
                                        </td>
                                        <td style="text-align:center;font-weight:500;color:#334155;">
                                            Semester {{ tagihan?.semester }} ({{ tagihan?.tahun_akademik }})
                                        </td>
                                        <td style="text-align:right;font-weight:700;font-family:monospace;font-size:0.9375rem;color:#0f172a;">
                                            {{ formatRupiah(tagihan?.nominal) }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="tfoot-subtotal">
                                        <td colspan="3" style="text-align:right;font-weight:600;color:#475569;">Subtotal Tagihan:</td>
                                        <td style="text-align:right;font-weight:700;font-family:monospace;color:#0f172a;">{{ formatRupiah(tagihan?.nominal) }}</td>
                                    </tr>
                                    <tr class="tfoot-grandtotal">
                                        <td colspan="3" style="text-align:right;font-weight:800;font-size:1rem;color:#0f172a;">TOTAL PEMBAYARAN:</td>
                                        <td style="text-align:right;font-weight:800;font-size:1.1875rem;font-family:monospace;color:#059669;">
                                            {{ formatRupiah(pembayaran?.jumlah_bayar || tagihan?.nominal) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- 5. BANK INSTRUCTION / GATEWAY BOX -->
                        <div v-if="metode" class="invoice-bank-card">
                            <div class="bank-card-icon-col">
                                <img
                                    v-if="metode.logo && !brokenLogos[metode.logo]"
                                    :src="metode.logo"
                                    :alt="metode.nama_metode"
                                    class="bank-logo-img"
                                    @error="onLogoError"
                                />
                                <div v-else class="bank-logo-fallback">
                                    <i class="fas fa-university"></i>
                                </div>
                            </div>
                            <div class="bank-card-info-col">
                                <div class="bank-title-row">
                                    <strong>{{ metode.nama_metode }}</strong>
                                    <span v-if="metode.kategori" class="bank-badge">{{ metode.kategori.toUpperCase() }}</span>
                                </div>
                                <p class="bank-instruction-text">
                                    {{ metode.instruksi || fallbackInstruksi }}
                                </p>
                            </div>
                        </div>

                        <!-- 6. VERIFICATION & DIGITAL STAMP SECTION -->
                        <div class="invoice-verification-section">
                            <div class="verify-qr-col">
                                <div class="qr-box">
                                    <img v-if="qrCode" :src="qrCode" alt="QR Verifikasi" class="qr-img" />
                                    <div v-else class="qr-placeholder">
                                        <i class="fas fa-qrcode"></i>
                                    </div>
                                    <span class="qr-label">Scan untuk Verifikasi</span>
                                </div>
                                <div class="qr-desc-box">
                                    <div class="qr-auth-title">
                                        <i class="fas fa-shield-alt text-emerald"></i>
                                        <span>VERIFIKASI KEASLIAN DOKUMEN</span>
                                    </div>
                                    <p class="qr-auth-desc">
                                        Pindai kode QR atau buka tautan di bawah ini untuk memastikan dokumen ini sah dan terdaftar resmi di basis data universitas:
                                    </p>
                                    <a v-if="verificationUrl" :href="verificationUrl" target="_blank" class="qr-auth-link">
                                        {{ verificationUrl }}
                                    </a>
                                </div>
                            </div>

                            <div class="signature-col">
                                <div class="sig-date">Mataram, {{ formatDate(pembayaran?.created_at || new Date().toISOString()) }}</div>
                                <div class="sig-title">Bagian Keuangan & Administrasi UKT</div>
                                <div class="sig-stamp-box">
                                    <div class="digital-stamp">
                                        <div class="stamp-inner">
                                            <i class="fas fa-check-circle stamp-icon"></i>
                                            <span class="stamp-text">TERVERIFIKASI SISTEM</span>
                                            <span class="stamp-meta">DIGITALLY SIGNED</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="sig-line">Biro Administrasi Keuangan (BAK)</div>
                            </div>
                        </div>

                        <!-- 7. INVOICE FOOTER NOTICE -->
                        <div class="invoice-document-footer">
                            <p>
                                <em>Catatan: Dokumen ini diterbitkan secara elektronik oleh Sistem Informasi Pembayaran UKT Universitas Bumi Gora dan sah tanpa tanda tangan basah.</em>
                            </p>
                            <p class="footer-timestamp">
                                Dicetak pada: {{ formatDate(new Date().toISOString(), true) }} &bull; Ref: {{ effectiveInvoiceNo }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Page Layout */
.invoice-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.invoice-container-wrapper {
    display: flex;
    justify-content: center;
    padding: 1.5rem 0 3rem;
}

/* Paper Document */
.invoice-paper {
    background: #ffffff;
    width: 100%;
    max-width: 860px;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
    padding: 2.5rem;
    position: relative;
    box-sizing: border-box;
}

/* 1. Kop Surat */
.invoice-header-banner {
    text-align: center;
    margin-bottom: 1.5rem;
}

.banner-img {
    max-width: 100%;
    max-height: 120px;
    object-fit: contain;
}

.invoice-kop-surat {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1.5rem;
}

.kop-left {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    flex: 1;
}

.kop-logo-box {
    width: 72px;
    height: 72px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.kop-logo-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.kop-fallback-icon {
    font-size: 3rem;
    color: #4f46e5;
}

.kop-text {
    flex: 1;
}

.kop-institution-name {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.01em;
    margin: 0 0 0.2rem;
    line-height: 1.2;
}

.kop-institution-sub {
    font-size: 0.8125rem;
    font-weight: 700;
    color: #4f46e5;
    letter-spacing: 0.04em;
    margin: 0 0 0.35rem;
}

.kop-institution-meta {
    font-size: 0.75rem;
    color: #64748b;
    margin: 0 0 0.15rem;
    line-height: 1.4;
}

.kop-right {
    text-align: right;
    flex-shrink: 0;
}

.kop-doc-badge {
    display: inline-block;
    background: #0f172a;
    color: #ffffff;
    font-size: 0.6875rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    padding: 0.25rem 0.6rem;
    border-radius: 4px;
}

.kop-doc-no {
    font-family: monospace;
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
    margin-top: 0.35rem;
}

.kop-doc-date {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 0.15rem;
}

.kop-divider {
    margin: 1.25rem 0 1.5rem;
}

.divider-thick {
    height: 3px;
    background: #0f172a;
    border-radius: 2px;
}

.divider-thin {
    height: 1px;
    background: #cbd5e1;
    margin-top: 2px;
}

/* 2. Document Title & Status */
.invoice-title-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    background: #f8fafc;
    padding: 1rem 1.25rem;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    margin-bottom: 1.5rem;
}

.invoice-heading {
    font-size: 1.125rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: 0.02em;
    margin: 0 0 0.2rem;
}

.invoice-subheading {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
    margin: 0;
}

/* 3. Metadata 2-Column Grid */
.invoice-meta-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
    margin-bottom: 1.5rem;
}

.meta-box {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #ffffff;
    overflow: hidden;
}

.meta-box-header {
    background: #f1f5f9;
    padding: 0.5rem 0.875rem;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.meta-box-body {
    padding: 0.875rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.meta-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8125rem;
    gap: 0.75rem;
}

.meta-label {
    color: #64748b;
    font-weight: 500;
    flex-shrink: 0;
}

.meta-value {
    color: #0f172a;
    font-weight: 500;
    text-align: right;
}

.font-semibold {
    font-weight: 600;
}

.font-mono {
    font-family: monospace;
    font-weight: 700;
}

.text-indigo {
    color: #4f46e5;
}

.text-emerald {
    color: #059669;
}

/* 4. Table */
.invoice-table-section {
    margin-bottom: 1.5rem;
}

.invoice-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
}

.invoice-table thead th {
    background: #f8fafc;
    color: #334155;
    font-weight: 700;
    font-size: 0.8125rem;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e2e8f0;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.invoice-table tbody td {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
}

.tfoot-subtotal td {
    padding: 0.625rem 1rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    font-size: 0.875rem;
}

.tfoot-grandtotal td {
    padding: 0.875rem 1rem;
    background: #f1f5f9;
    border-top: 2px solid #cbd5e1;
}

/* 5. Bank Gateway Box */
.invoice-bank-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    padding: 0.875rem 1.25rem;
    margin-bottom: 1.5rem;
}

.bank-card-icon-col {
    flex-shrink: 0;
}

.bank-logo-img {
    width: 52px;
    height: 38px;
    object-fit: contain;
    background: #ffffff;
    padding: 4px;
    border-radius: 6px;
    border: 1px solid #dbeafe;
}

.bank-logo-fallback {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    background: #dbeafe;
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.bank-card-info-col {
    flex: 1;
}

.bank-title-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #1e3a8a;
}

.bank-badge {
    background: #dbeafe;
    color: #1e40af;
    font-size: 0.6875rem;
    font-weight: 700;
    padding: 0.15rem 0.45rem;
    border-radius: 4px;
}

.bank-instruction-text {
    font-size: 0.8125rem;
    color: #1e40af;
    margin: 0.25rem 0 0;
    line-height: 1.4;
}

/* 6. Verification & Signatures */
.invoice-verification-section {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1.5rem;
    border: 1px dashed #cbd5e1;
    background: #fafafa;
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.verify-qr-col {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    flex: 1;
    min-width: 0;
}

.qr-box {
    text-align: center;
    flex-shrink: 0;
}

.qr-img {
    width: 90px;
    height: 90px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #ffffff;
    padding: 4px;
}

.qr-placeholder {
    width: 90px;
    height: 90px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: #64748b;
}

.qr-label {
    display: block;
    font-size: 0.65rem;
    color: #64748b;
    margin-top: 0.25rem;
}

.qr-desc-box {
    flex: 1;
    min-width: 0;
    overflow: hidden;
}

.qr-auth-title {
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin-bottom: 0.35rem;
}

.qr-auth-desc {
    font-size: 0.75rem;
    color: #475569;
    margin: 0 0 0.35rem;
    line-height: 1.4;
}

.qr-auth-link {
    font-size: 0.6875rem;
    color: #2563eb;
    font-family: monospace;
    word-break: break-all;
    overflow-wrap: break-word;
    display: block;
    max-width: 100%;
    text-decoration: underline;
}

.signature-col {
    width: 220px;
    text-align: center;
    flex-shrink: 0;
}

.sig-date {
    font-size: 0.75rem;
    color: #64748b;
}

.sig-title {
    font-size: 0.8125rem;
    font-weight: 700;
    color: #0f172a;
    margin-top: 0.2rem;
}

.sig-stamp-box {
    margin: 0.75rem 0;
    display: flex;
    justify-content: center;
}

.digital-stamp {
    border: 2px dashed #059669;
    border-radius: 8px;
    padding: 0.4rem 0.75rem;
    background: rgba(16, 185, 129, 0.06);
    transform: rotate(-3deg);
}

.stamp-inner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.15rem;
}

.stamp-icon {
    color: #059669;
    font-size: 1rem;
}

.stamp-text {
    font-size: 0.6875rem;
    font-weight: 800;
    color: #065f46;
    letter-spacing: 0.05em;
}

.stamp-meta {
    font-size: 0.5625rem;
    color: #059669;
    font-family: monospace;
}

.sig-line {
    border-top: 1px solid #475569;
    font-size: 0.75rem;
    font-weight: 600;
    color: #334155;
    padding-top: 0.35rem;
    margin-top: 0.5rem;
}

/* 7. Footer */
.invoice-document-footer {
    text-align: center;
    border-top: 1px solid #f1f5f9;
    padding-top: 1rem;
    color: #94a3b8;
    font-size: 0.7188rem;
    line-height: 1.4;
}

.invoice-document-footer p {
    margin: 0 0 0.2rem;
}

.footer-timestamp {
    font-size: 0.6563rem;
    color: #cbd5e1;
    font-family: monospace;
}

/* Global Solid Tokens */
.solid-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-weight: 600;
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-white-border {
    background: #ffffff;
    color: #334155;
    border: 1px solid #cbd5e1;
}

.btn-white-border:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a;
}

.btn-indigo-solid {
    background: #4f46e5;
    color: #ffffff;
    border: 1px solid #4f46e5;
}

.btn-indigo-solid:hover {
    background: #4338ca;
    color: #ffffff;
}

.solid-badge {
    display: inline-flex;
    align-items: center;
    font-weight: 700;
    border-radius: 9999px;
    letter-spacing: 0.025em;
}

.badge-solid-success {
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.badge-solid-danger {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.badge-solid-warning {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
}

/* Responsive adjustments (Screen only) */
@media screen and (max-width: 768px) {
    .invoice-paper {
        padding: 1.5rem;
        border-radius: 0;
        border-left: none;
        border-right: none;
    }
    
    .invoice-kop-surat {
        flex-direction: column;
        align-items: flex-start;
    }

    .kop-right {
        text-align: left;
    }

    .invoice-meta-grid {
        grid-template-columns: 1fr;
    }

    .invoice-verification-section {
        flex-direction: column;
    }

    .signature-col {
        width: 100%;
        margin-top: 1rem;
    }

    .sig-line {
        max-width: 240px;
        margin-left: auto;
        margin-right: auto;
    }
}

/* Scoped Print Compact Adjustments */
@media print {
    .invoice-paper {
        max-width: 100% !important;
        width: 100% !important;
        padding: 0 10mm !important;
        margin: 0 auto !important;
        box-sizing: border-box !important;
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        background: #ffffff !important;
    }

    .invoice-kop-surat {
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-between !important;
        align-items: flex-start !important;
        gap: 1rem !important;
    }

    .kop-left {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        gap: 0.875rem !important;
        flex: 1 1 0% !important;
        min-width: 0 !important;
    }

    .kop-logo-box {
        width: 55px !important;
        height: 55px !important;
        flex-shrink: 0 !important;
    }

    .kop-institution-name {
        font-size: 1.15rem !important;
        margin: 0 0 0.15rem !important;
        line-height: 1.2 !important;
    }

    .kop-institution-sub {
        font-size: 0.75rem !important;
        margin: 0 0 0.2rem !important;
    }

    .kop-institution-meta {
        font-size: 0.6875rem !important;
        margin: 0 0 0.1rem !important;
        line-height: 1.3 !important;
    }

    .kop-right {
        text-align: right !important;
        flex-shrink: 0 !important;
    }

    .kop-doc-badge {
        font-size: 0.625rem !important;
        padding: 0.2rem 0.5rem !important;
    }

    .kop-doc-no {
        font-size: 0.875rem !important;
        margin-top: 0.25rem !important;
    }

    .kop-divider {
        margin: 0.75rem 0 0.875rem !important;
    }

    .invoice-title-bar {
        padding: 0.5rem 0.875rem !important;
        margin-bottom: 0.875rem !important;
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-between !important;
        align-items: center !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .invoice-heading {
        font-size: 0.95rem !important;
        margin: 0 0 0.15rem !important;
    }

    .invoice-subheading {
        font-size: 0.75rem !important;
    }

    .invoice-meta-grid {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 0.75rem !important;
        margin-bottom: 0.875rem !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .meta-box-header {
        padding: 0.35rem 0.65rem !important;
        font-size: 0.6875rem !important;
    }

    .meta-box-body {
        padding: 0.5rem 0.65rem !important;
        gap: 0.35rem !important;
    }

    .meta-row {
        font-size: 0.75rem !important;
    }

    .invoice-table-section {
        margin-bottom: 0.875rem !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .invoice-table thead th {
        padding: 0.45rem 0.75rem !important;
        font-size: 0.75rem !important;
    }

    .invoice-table tbody td {
        padding: 0.55rem 0.75rem !important;
        font-size: 0.8125rem !important;
    }

    .tfoot-subtotal td {
        padding: 0.35rem 0.75rem !important;
        font-size: 0.75rem !important;
    }

    .tfoot-grandtotal td {
        padding: 0.5rem 0.75rem !important;
        font-size: 0.9375rem !important;
    }

    .invoice-bank-card {
        padding: 0.5rem 0.75rem !important;
        margin-bottom: 0.875rem !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .invoice-verification-section {
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-between !important;
        align-items: flex-start !important;
        gap: 0.75rem !important;
        padding: 0.75rem 0.875rem !important;
        margin-bottom: 0.875rem !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        box-sizing: border-box !important;
        width: 100% !important;
        max-width: 100% !important;
        overflow: hidden !important;
    }

    .verify-qr-col {
        display: flex !important;
        flex-direction: row !important;
        align-items: flex-start !important;
        gap: 0.75rem !important;
        flex: 1 1 0% !important;
        min-width: 0 !important;
        max-width: calc(100% - 190px) !important;
        overflow: hidden !important;
    }

    .qr-box {
        flex-shrink: 0 !important;
        width: 68px !important;
    }

    .qr-img, .qr-placeholder {
        width: 64px !important;
        height: 64px !important;
    }

    .qr-label {
        font-size: 0.58rem !important;
    }

    .qr-desc-box {
        flex: 1 1 0% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        overflow: hidden !important;
    }

    .qr-auth-title {
        font-size: 0.6875rem !important;
        margin-bottom: 0.2rem !important;
    }

    .qr-auth-desc {
        font-size: 0.6875rem !important;
        line-height: 1.25 !important;
        margin: 0 0 0.2rem !important;
    }

    .qr-auth-link {
        font-size: 0.625rem !important;
        word-break: break-all !important;
        overflow-wrap: anywhere !important;
        white-space: normal !important;
        display: block !important;
        max-width: 100% !important;
    }

    .signature-col {
        width: 180px !important;
        flex-shrink: 0 !important;
        text-align: center !important;
        margin: 0 !important;
    }

    .sig-date {
        font-size: 0.6875rem !important;
    }

    .sig-title {
        font-size: 0.75rem !important;
        margin-top: 0.15rem !important;
    }

    .sig-stamp-box {
        margin: 0.35rem 0 !important;
    }

    .digital-stamp {
        padding: 0.25rem 0.5rem !important;
        transform: rotate(-2deg) !important;
    }

    .stamp-icon {
        font-size: 0.75rem !important;
    }

    .stamp-text {
        font-size: 0.58rem !important;
    }

    .stamp-meta {
        font-size: 0.5rem !important;
    }

    .sig-line {
        font-size: 0.6875rem !important;
        padding-top: 0.25rem !important;
        margin-top: 0.35rem !important;
    }

    .invoice-document-footer {
        font-size: 0.65rem !important;
        padding-top: 0.5rem !important;
    }

    @page {
        size: A4 portrait;
        margin: 12mm 15mm 12mm 15mm;
    }
}
</style>