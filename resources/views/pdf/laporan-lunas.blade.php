<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Pembayaran UKT Lunas</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 12mm 10mm 12mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5px;
            color: #0f172a;
            margin: 0;
            padding: 0;
            line-height: 1.35;
        }
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .kop-title {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .kop-sub {
            font-size: 8.5px;
            font-weight: bold;
            color: #059669;
            margin-top: 1px;
        }
        .kop-meta {
            font-size: 7.5px;
            color: #64748b;
        }
        .divider {
            height: 2px;
            background: #0f172a;
            margin: 4px 0 10px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .data-table th {
            background: #f1f5f9;
            color: #1e293b;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 5px 6px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        .data-table td {
            padding: 4.5px 6px;
            border: 1px solid #cbd5e1;
            font-size: 8px;
        }
        .data-table tr:nth-child(even) td {
            background: #f8fafc;
        }
        .badge-lunas {
            display: inline-block;
            background: #dcfce7;
            color: #15803d;
            font-weight: bold;
            font-size: 7px;
            padding: 1.5px 5px;
            border-radius: 3px;
        }
        .mono {
            font-family: DejaVu Sans Mono, monospace;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .footer-note {
            text-align: center;
            font-size: 7px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <table class="kop-table">
        <tr>
            <td>
                <div class="kop-title">Universitas Bumi Gora &bull; Laporan Pembayaran UKT Lunas</div>
                <div class="kop-sub">REKAPITULASI PEMBAYARAN MAHASISWA TERVALIDASI</div>
            </td>
            <td align="right" valign="top">
                <div class="kop-meta">Tanggal Cetak: <strong>{{ now()->format('d/m/Y H:i:s') }}</strong></div>
                <div class="kop-meta">Total Data: <strong>{{ $data->count() }} Transaksi Lunas</strong></div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <table class="data-table">
        <thead>
            <tr>
                <th width="3%" class="text-center">No</th>
                <th width="9%">NIM</th>
                <th width="18%">Nama Mahasiswa</th>
                <th width="15%">Program Studi</th>
                <th width="4%" class="text-center">Angk.</th>
                <th width="10%" class="text-center">Tahun Akademik</th>
                <th width="4%" class="text-center">Smt</th>
                <th width="11%" class="text-right">Nominal Bayar (Rp)</th>
                <th width="11%" class="text-center">Waktu Validasi</th>
                <th width="8%" class="text-center">Metode</th>
                <th width="7%" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $i => $p)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td class="mono"><strong>{{ $p->tagihan?->mahasiswa?->nim ?? '-' }}</strong></td>
                    <td>{{ $p->tagihan?->mahasiswa?->nama_lengkap ?? '-' }}</td>
                    <td>{{ $p->tagihan?->mahasiswa?->jurusan ?? '-' }}</td>
                    <td class="text-center">{{ $p->tagihan?->mahasiswa?->angkatan ?? '-' }}</td>
                    <td class="text-center">{{ $p->tagihan?->tahun_akademik ?? '-' }}</td>
                    <td class="text-center"><strong>{{ $p->tagihan?->semester ?? '-' }}</strong></td>
                    <td class="text-right mono" style="color:#047857;"><strong>Rp {{ number_format($p->jumlah_bayar, 0, ',', '.') }}</strong></td>
                    <td class="text-center">{{ $p->verified_at ? $p->verified_at->format('d/m/Y H:i') : ($p->updated_at ? $p->updated_at->format('d/m/Y H:i') : '-') }}</td>
                    <td class="text-center">{{ $p->metodePembayaran?->nama_metode ?? 'Virtual Account' }}</td>
                    <td class="text-center"><span class="badge-lunas">LUNAS</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center" style="padding: 12px; color: #94a3b8;">Tidak ada data pembayaran lunas yang ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        Dokumen ini diterbitkan resmi oleh Sistem Informasi UKT Universitas Bumi Gora &bull; Dicetak: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
