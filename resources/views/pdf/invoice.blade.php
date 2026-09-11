<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $data['document_title'] }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 14mm 10mm 14mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #0f172a;
            margin: 0;
            padding: 0;
            line-height: 1.4;
            background: #ffffff;
        }

        /* Kop Surat */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .kop-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }
        .kop-name {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .kop-sub {
            font-size: 8.5px;
            font-weight: bold;
            color: #4338ca;
            letter-spacing: 0.04em;
            margin-bottom: 3px;
        }
        .kop-meta {
            font-size: 8px;
            color: #475569;
            line-height: 1.35;
        }
        .kop-divider-thick {
            height: 2.5px;
            background: #0f172a;
            margin-top: 6px;
        }
        .kop-divider-thin {
            height: 0.75px;
            background: #94a3b8;
            margin-top: 1.5px;
            margin-bottom: 12px;
        }

        /* Title & Status Bar */
        .title-table {
            width: 100%;
            border-collapse: collapse;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            margin-bottom: 12px;
        }
        .title-table td {
            padding: 8px 12px;
        }
        .doc-title {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .doc-subtitle {
            font-size: 8.5px;
            font-weight: bold;
            color: #475569;
            margin-top: 2px;
        }
        .badge-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* 2-Column Info Grid Table */
        .info-grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .info-grid-table > tbody > tr > td {
            width: 50%;
            vertical-align: top;
        }
        .info-card {
            border: 1px solid #cbd5e1;
            background: #ffffff;
        }
        .info-card-header {
            background: #f1f5f9;
            padding: 5px 10px;
            font-size: 8.5px;
            font-weight: bold;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #cbd5e1;
        }
        .info-card-body {
            padding: 8px 10px;
        }
        .info-row-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-row-table td {
            padding: 2.5px 0;
            font-size: 8.5px;
        }
        .label-col {
            width: 40%;
            color: #64748b;
            font-weight: normal;
        }
        .val-col {
            width: 60%;
            color: #0f172a;
            font-weight: bold;
        }

        /* Itemized Breakdown Table */
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .item-table th {
            background: #f1f5f9;
            color: #1e293b;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 6px 10px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        .item-table td {
            padding: 7px 10px;
            border: 1px solid #cbd5e1;
            font-size: 9px;
            vertical-align: middle;
        }
        .item-table tr:nth-child(even) td {
            background: #f8fafc;
        }
        .tfoot-subtotal td {
            background: #f8fafc;
            font-weight: bold;
            font-size: 9px;
        }
        .tfoot-total td {
            background: #e2e8f0;
            font-weight: bold;
            font-size: 10.5px;
            color: #0f172a;
        }

        /* Payment Channel Box */
        .gateway-box {
            border: 1px solid #93c5fd;
            background: #eff6ff;
            padding: 8px 12px;
            margin-bottom: 12px;
            font-size: 8.5px;
            color: #1e3a8a;
        }
        .gateway-title {
            font-size: 9px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 2px;
        }

        /* Verification & Signatures Table */
        .verify-sig-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px dashed #94a3b8;
            background: #fafafa;
            margin-bottom: 10px;
            table-layout: fixed;
        }
        .verify-sig-table td {
            padding: 8px 10px;
            vertical-align: top;
            overflow: hidden;
        }
        .qr-img {
            width: 72px;
            height: 72px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 2px;
        }
        .verify-label {
            font-size: 8px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 2px;
        }
        .verify-desc {
            font-size: 7.5px;
            color: #475569;
            line-height: 1.3;
            margin-bottom: 3px;
        }
        .verify-url {
            font-size: 6.5px;
            color: #2563eb;
            font-family: DejaVu Sans Mono, monospace;
            word-break: break-all;
            word-wrap: break-word;
            line-height: 1.25;
            display: block;
        }
        .verify-meta {
            font-size: 6.5px;
            color: #64748b;
            font-family: DejaVu Sans Mono, monospace;
            margin-top: 3px;
        }

        /* Signature Column */
        .sig-box {
            text-align: center;
        }
        .sig-date {
            font-size: 8.5px;
            color: #475569;
            margin-bottom: 2px;
        }
        .sig-title {
            font-size: 9px;
            font-weight: bold;
            color: #0f172a;
        }
        .stamp-badge {
            display: inline-block;
            border: 1.5px dashed #059669;
            background: #f0fdf4;
            color: #065f46;
            padding: 3px 8px;
            margin: 6px 0;
            font-size: 7.5px;
            font-weight: bold;
            letter-spacing: 0.04em;
        }
        .sig-line {
            border-top: 1px solid #334155;
            width: 140px;
            margin: 6px auto 0;
            padding-top: 3px;
            font-size: 8px;
            font-weight: bold;
            color: #334155;
        }

        /* Footer */
        .footer-note {
            text-align: center;
            font-size: 7.5px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
            line-height: 1.4;
        }

        .mono {
            font-family: DejaVu Sans Mono, monospace;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- KOP SURAT RESMI -->
    <table class="kop-table">
        <tr>
            <td width="65" valign="middle">
                @if (!empty($data['logo_url']))
                    <img class="kop-logo" src="{{ $data['logo_url'] }}" alt="Logo" />
                @endif
            </td>
            <td valign="middle" style="padding-left: 8px;">
                <div class="kop-name">{{ $data['institution']['name'] }}</div>
                <div class="kop-sub">BAGIAN KEUANGAN & ADMINISTRASI AKADEMIK (UKT ONLINE)</div>
                <div class="kop-meta">{{ $data['institution']['address'] }}</div>
                <div class="kop-meta">Telp: {{ $data['institution']['phone'] ?: '(0370) 638369' }} &nbsp;|&nbsp; Email: {{ $data['institution']['email'] ?: 'keuangan@ubg.ac.id' }} &nbsp;|&nbsp; Web: {{ $data['institution']['website'] }}</div>
            </td>
            <td width="150" align="right" valign="top">
                <div style="font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; font-weight: bold;">NO. DOKUMEN</div>
                <div class="mono" style="font-size: 9.5px; font-weight: bold; color: #0f172a; margin-top: 2px;">{{ $data['invoice_number'] }}</div>
                <div style="font-size: 7.5px; color: #64748b; margin-top: 2px;">Tgl Terbit: {{ $data['invoice_date_label'] }}</div>
            </td>
        </tr>
    </table>

    <div class="kop-divider-thick"></div>
    <div class="kop-divider-thin"></div>

    <!-- JUDUL & STATUS DOKUMEN -->
    <table class="title-table">
        <tr>
            <td>
                <div class="doc-title">Bukti Pembayaran Uang Kuliah Tunggal</div>
                <div class="doc-subtitle">{{ $data['item']['description'] }} @if(!empty($data['item']['meta'])) &bull; {{ implode(' · ', $data['item']['meta']) }} @endif</div>
            </td>
            <td align="right" width="160">
                <span class="badge-status" style="background: {{ $data['status_color'] }};">{{ $data['status_label'] }}</span>
            </td>
        </tr>
    </table>

    <!-- 2 KOLOM IDENTITAS MAHASISWA & TRANSAKSI -->
    <table class="info-grid-table">
        <tr>
            <!-- Left: Data Mahasiswa -->
            <td style="padding-right: 6px;">
                <div class="info-card">
                    <div class="info-card-header">DATA MAHASISWA</div>
                    <div class="info-card-body">
                        <table class="info-row-table">
                            <tr>
                                <td class="label-col">Nama Lengkap</td>
                                <td class="val-col">{{ $data['student']['name'] }}</td>
                            </tr>
                            <tr>
                                <td class="label-col">NIM</td>
                                <td class="val-col mono">{{ $data['student']['nim'] }}</td>
                            </tr>
                            <tr>
                                <td class="label-col">Program Studi</td>
                                <td class="val-col">{{ $data['student']['program_studi'] ?: $data['student']['jurusan'] }}</td>
                            </tr>
                            <tr>
                                <td class="label-col">Angkatan</td>
                                <td class="val-col">{{ $data['student']['angkatan'] ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="label-col">Email</td>
                                <td class="val-col" style="font-size:8px;">{{ $data['student']['email'] ?: '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>

            <!-- Right: Data Pembayaran -->
            <td style="padding-left: 6px;">
                <div class="info-card">
                    <div class="info-card-header">INFORMASI PEMBAYARAN</div>
                    <div class="info-card-body">
                        <table class="info-row-table">
                            <tr>
                                <td class="label-col">No. Referensi</td>
                                <td class="val-col mono">#{{ $data['payment']['reference_no'] }}</td>
                            </tr>
                            <tr>
                                <td class="label-col">Metode Pembayaran</td>
                                <td class="val-col">{{ $data['payment']['method'] }}</td>
                            </tr>
                            <tr>
                                <td class="label-col">No. Virtual Account</td>
                                <td class="val-col mono">{{ $data['payment']['va_number'] ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="label-col">Waktu Bayar / Valid</td>
                                <td class="val-col">{{ $data['payment']['paid_at_label'] }}</td>
                            </tr>
                            <tr>
                                <td class="label-col">Status Transaksi</td>
                                <td class="val-col" style="color: {{ $data['status_color'] }};">{{ $data['status_label'] }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- TABEL RINCIAN ITEM PEMBAYARAN -->
    <table class="item-table">
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="55%">Uraian Pembayaran</th>
                <th width="20%" class="text-center">Periode / Semester</th>
                <th width="20%" class="text-right">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center" style="font-weight: bold; color: #64748b;">1</td>
                <td>
                    <div style="font-weight: bold; color: #0f172a;">{{ $data['item']['description'] }}</div>
                    <div style="font-size: 7.5px; color: #64748b; margin-top: 1px;">Biaya kuliah tunggal semester mahasiswa aktif program akademik</div>
                </td>
                <td class="text-center" style="font-weight: bold; color: #334155;">
                    @if(!empty($data['item']['meta']))
                        {{ implode(', ', $data['item']['meta']) }}
                    @else
                        -
                    @endif
                </td>
                <td class="text-right mono" style="font-weight: bold; font-size: 9.5px; color: #0f172a;">
                    {{ $data['item']['amount_label'] }}
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="tfoot-subtotal">
                <td colspan="3" class="text-right">Subtotal Tagihan:</td>
                <td class="text-right mono">{{ $data['subtotal_label'] }}</td>
            </tr>
            <tr class="tfoot-total">
                <td colspan="3" class="text-right" style="text-transform: uppercase;">TOTAL DIBAYARKAN:</td>
                <td class="text-right mono" style="color: #047857; font-size: 11px;">{{ $data['total_label'] }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- KOTAK GATEWAY & INSTRUKSI -->
    @if (!empty($data['payment']['method_instruksi']))
        <div class="gateway-box">
            <div class="gateway-title">Panduan Pembayaran — {{ $data['payment']['method'] }}</div>
            <div style="margin-top: 2px; white-space: pre-line;">{{ $data['payment']['method_instruksi'] }}</div>
        </div>
    @endif

    <!-- SEKSI VERIFIKASI QR CODE & TANDA TANGAN RESMI -->
    <table class="verify-sig-table" width="100%">
        <tr>
            <!-- Left: QR Code -->
            <td width="80" align="center" valign="middle">
                @if(!empty($data['qr_code']))
                    <img class="qr-img" src="{{ $data['qr_code'] }}" alt="QR Verifikasi" />
                @endif
                <div style="font-size: 6px; color: #64748b; margin-top: 2px;">Pindai validasi</div>
            </td>

            <!-- Center: Penjelasan Verifikasi -->
            <td width="270" valign="top" style="padding-left: 6px; padding-right: 6px;">
                <div class="verify-label">Verifikasi Keaslian Dokumen</div>
                <div class="verify-desc">
                    Pindai kode QR atau buka tautan di bawah ini untuk memverifikasi keabsahan dokumen di basis data universitas:
                </div>
                <div class="verify-url">{{ $data['verification_url'] }}</div>
                <div class="verify-meta">{{ $data['invoice_number'] }} &bull; NIM: {{ $data['student']['nim'] }} &bull; {{ $data['total_label'] }}</div>
            </td>

            <!-- Right: Tanda Tangan & Stempel -->
            <td width="150" align="center" valign="top">
                <div class="sig-box">
                    <div class="sig-date">Mataram, {{ $data['invoice_date_label'] }}</div>
                    <div class="sig-title">Biro Administrasi Keuangan (BAK)</div>
                    <div class="stamp-badge">
                        TERVERIFIKASI SISTEM<br>
                        <span style="font-size: 5.5px; font-weight: normal; font-family: monospace;">DIGITALLY SIGNED</span>
                    </div>
                    <div class="sig-line">Bagian Keuangan</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- FOOTER RESMI -->
    <div class="footer-note">
        Dokumen ini diterbitkan secara elektronik oleh Sistem Pembayaran UKT {{ $data['institution']['name'] }} dan sah tanpa tanda tangan basah.<br>
        Dicetak pada: {{ now()->format('d/m/Y H:i:s') }} &bull; Lembar 1 dari 1
    </div>
</body>
</html>
