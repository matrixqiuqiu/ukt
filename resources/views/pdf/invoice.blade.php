<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $data['document_title'] }}</title>
    <style>
        @page { size: A4; margin: 18mm 14mm 18mm 14mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: {{ $data['colors']['content_text'] }};
            margin: 0; padding: 0;
            line-height: 1.5;
        }
        .kop {
            border-bottom: 3px solid {{ $data['colors']['primary'] }};
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .kop-nama { font-size: 17px; font-weight: 800; margin-bottom: 2px; letter-spacing: -0.02em; }
        .kop-meta { color: #64748b; font-size: 9.5px; line-height: 1.4; }
        .kop-logo { width: 56px; height: 56px; object-fit: contain; }
        .title-block { margin-bottom: 14px; }
        .judul { font-size: 15px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 2px; }
        .judul-sub { color: #64748b; font-size: 9.5px; }
        .badge { display:inline-block; padding:4px 14px; border-radius:9999px; color:#fff; font-size:10px; font-weight:800; letter-spacing:0.04em; text-transform:uppercase; }
        .box {
            border: 1px solid {{ $data['colors']['card_border'] }};
            background: #fff;
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 12px;
        }
        .box-title { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#64748b; margin-bottom:8px; }
        table.detail {
            width: 100%; border-collapse: collapse; margin-bottom: 12px;
        }
        table.detail th {
            text-align: left; background: #f1f5f9; padding: 7px 10px; border: 1px solid {{ $data['colors']['card_border'] }};
            font-size: 9.5px; text-transform: uppercase; letter-spacing:0.05em; color:#334155;
        }
        table.detail td {
            padding: 7px 10px; border: 1px solid {{ $data['colors']['card_border'] }};
        }
        table.detail tr:nth-child(even) td { background:#f8fafc; }
        .amount { font-weight:800; font-size: 12px; color: #0f172a; }
        .instruksi {
            border: 1px dashed #94a3b8; background: {{ $data['colors']['card_bg'] }};
            padding: 10px 12px; border-radius: 8px; margin-bottom: 14px; line-height: 1.6; font-size:10px;
        }
        .verify {
            border: 1.5px solid {{ $data['colors']['primary'] }};
            border-radius: 10px; padding: 12px; margin-top: 16px; background:#f8fafc;
        }
        .verify-qr { width: 88px; height: 88px; }
        .verify-url { font-size: 7.5px; color:#475569; word-break:break-all; line-height:1.3; }
        .verify-label { font-size:8px; text-transform:uppercase; letter-spacing:0.08em; color:#64748b; font-weight:700; margin-bottom:4px; }
        .sig-line { border-top: 1px solid #334155; width: 160px; margin-top: 48px; padding-top:6px; font-size:10px; text-align:center; color:#334155; }
        .footer {
            text-align: center; color: #94a3b8; font-size: 8px; margin-top: 18px; border-top:1px solid #e2e8f0; padding-top:8px;
        }
        .mono { font-family: DejaVu Sans Mono, monospace; }
    </style>
</head>
<body>
    {{-- Kop Surat --}}
    <table class="kop" width="100%">
        <tr>
            <td width="68">
                @if (!empty($data['logo_url']))
                    <img class="kop-logo" src="{{ $data['logo_url'] }}" alt="Logo" />
                @endif
            </td>
            <td>
                <div class="kop-nama">{{ $data['institution']['name'] }}</div>
                <div class="kop-meta">{{ $data['institution']['address'] }}</div>
                <div class="kop-meta">Tel: {{ $data['institution']['phone'] ?: '-' }} &nbsp;|&nbsp; Email: {{ $data['institution']['email'] ?: '-' }} &nbsp;|&nbsp; Web: {{ $data['institution']['website'] }}</div>
            </td>
            <td width="160" align="right" valign="top">
                <div style="font-size:8px;text-transform:uppercase;letter-spacing:0.08em;color:#64748b;">No. Dokumen</div>
                <div class="mono" style="font-size:10px;font-weight:700;">{{ $data['invoice_number'] }}</div>
                <div style="font-size:8px;color:#64748b;margin-top:2px;">{{ $data['invoice_date_label'] }}</div>
            </td>
        </tr>
    </table>

    {{-- Judul & Status --}}
    <table width="100%" class="title-block">
        <tr>
            <td>
                <div class="judul">Bukti Pembayaran UKT</div>
                <div class="judul-sub">{{ $data['item']['description'] }}
                    @if(!empty($data['item']['meta'])) &middot; {{ implode(' · ', $data['item']['meta']) }} @endif
                </div>
            </td>
            <td align="right" valign="top">
                <span class="badge" style="background: {{ $data['status_color'] }};">{{ $data['status_label'] }}</span>
            </td>
        </tr>
    </table>

    {{-- Info Mahasiswa --}}
    <div class="box">
        <div class="box-title">Data Mahasiswa</div>
        <table width="100%">
            <tr>
                <td width="50%" style="padding:3px 0;">Nama: <strong>{{ $data['student']['name'] }}</strong></td>
                <td style="padding:3px 0;">NIM: <strong class="mono">{{ $data['student']['nim'] }}</strong></td>
            </tr>
            <tr>
                <td style="padding:3px 0;">Program Studi: <strong>{{ $data['student']['program_studi'] }}</strong></td>
                <td style="padding:3px 0;">Angkatan: <strong>{{ $data['student']['angkatan'] }}</strong></td>
            </tr>
            <tr>
                <td style="padding:3px 0;">Email: <span class="mono" style="font-size:10px;">{{ $data['student']['email'] ?: '-' }}</span></td>
                <td style="padding:3px 0;">Jurusan: <strong>{{ $data['student']['jurusan'] }}</strong></td>
            </tr>
        </table>
    </div>

    {{-- Detail Pembayaran --}}
    <table class="detail">
        <tr>
            <th width="32%">Uraian</th>
            <th>Detail</th>
        </tr>
        <tr>
            <td>Deskripsi Tagihan</td>
            <td><strong>{{ $data['item']['description'] }}</strong><br><span style="font-size:9px;color:#64748b;">{{ implode(' · ', $data['item']['meta']) }}</span></td>
        </tr>
        <tr>
            <td>Nominal UKT</td>
            <td class="amount">{{ $data['item']['amount_label'] }}</td>
        </tr>
        <tr>
            <td>Metode Pembayaran</td>
            <td>
                @php
                    $bankLogo = $data['payment']['method_logo'] ?? '';
                    $bankLogoSrc = '';
                    if (str_starts_with($bankLogo, 'data:')) {
                        $bankLogoSrc = $bankLogo;
                    } elseif (str_starts_with($bankLogo, '/storage/') || str_starts_with($bankLogo, 'storage/')) {
                        $path = public_path(ltrim($bankLogo, '/'));
                        if (is_file($path)) {
                            $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'png';
                            $bankLogoSrc = 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($path));
                        }
                    }
                @endphp
                @if ($bankLogoSrc !== '')
                    <img src="{{ $bankLogoSrc }}" style="width: 24px; height: 24px; object-fit: contain; vertical-align: middle; margin-right: 6px; border:1px solid #e2e8f0; border-radius:4px; padding:2px; background:#fff;" alt="Logo" />
                @endif
                <strong>{{ $data['payment']['method'] }}</strong>
            </td>
        </tr>
        <tr>
            <td>Nomor Virtual Account</td>
            <td class="mono" style="font-size:11px;letter-spacing:0.04em;">{{ $data['payment']['va_number'] ?: '-' }}</td>
        </tr>
        <tr>
            <td>Waktu Pembayaran</td>
            <td>{{ $data['payment']['paid_at_label'] }} @if(!empty($data['payment']['va_expired_at'])) <span style="color:#64748b;font-size:9px;">(Exp: {{ $data['payment']['va_expired_at'] }})</span> @endif</td>
        </tr>
        <tr>
            <td>No. Referensi</td>
            <td class="mono">#{{ $data['payment']['reference_no'] }}</td>
        </tr>
    </table>

    @if (!empty($data['payment']['method_instruksi']))
        <div class="instruksi">
            <strong style="color:#0f172a;">Instruksi Pembayaran — {{ $data['payment']['method'] }}</strong>
            <div style="margin-top: 4px; white-space: pre-line;">{{ $data['payment']['method_instruksi'] }}</div>
        </div>
    @endif

    {{-- QR Verifikasi + Tanda Tangan --}}
    <div class="verify">
        <table width="100%">
            <tr>
                <td width="100" align="center" valign="top">
                    @if(!empty($data['qr_code']))
                        <img class="verify-qr" src="{{ $data['qr_code'] }}" alt="QR Verifikasi" />
                    @endif
                    <div style="font-size:6.5px;color:#64748b;margin-top:4px;">Scan untuk verifikasi</div>
                </td>
                <td valign="top" style="padding-left:10px;">
                    <div class="verify-label">Verifikasi Keaslian Dokumen</div>
                    <div style="font-size:9px;color:#334155;margin-bottom:4px;">Pindai QR di samping atau buka tautan berikut untuk memastikan dokumen ini asli dan diterbitkan oleh {{ $data['institution']['name'] }}:</div>
                    <div class="verify-url mono">{{ $data['verification_url'] }}</div>
                    <div style="font-size:7.5px;color:#94a3b8;margin-top:6px;">No. {{ $data['invoice_number'] }} &middot; {{ $data['student']['nim'] }} &middot; {{ $data['item']['amount_label'] }}</div>
                </td>
                <td width="180" align="center" valign="top">
                    <div style="font-size:10px;color:#334155;">Mataram, {{ $data['invoice_date_label'] }}</div>
                    <div style="font-size:10px;color:#0f172a;font-weight:700;margin-top:2px;">Bagian Keuangan</div>
                    <div class="sig-line">Stempel & Tanda Tangan</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Dokumen ini dihasilkan otomatis oleh Sistem Pembayaran UKT {{ $data['institution']['name'] }}. Simpan sebagai arsip. &middot; Halaman 1/1 &middot; Dicetak: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
