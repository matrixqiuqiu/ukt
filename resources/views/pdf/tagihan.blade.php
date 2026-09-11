<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Daftar Tagihan UKT</title>
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
            color: #4338ca;
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
        .doc-bar {
            width: 100%;
            border-collapse: collapse;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            margin-bottom: 8px;
        }
        .doc-bar td {
            padding: 5px 10px;
            font-size: 8px;
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
        .row-lunas td {
            background: #f0fdf4 !important;
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
        .badge-belum {
            display: inline-block;
            background: #fee2e2;
            color: #b91c1c;
            font-weight: bold;
            font-size: 7px;
            padding: 1.5px 5px;
            border-radius: 3px;
        }
        .badge-terlambat {
            display: inline-block;
            background: #fef3c7;
            color: #b45309;
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
                <div class="kop-title">Universitas Bumi Gora &bull; Laporan Daftar Tagihan UKT</div>
                <div class="kop-sub">BAGIAN ADMINISTRASI KEUANGAN (BAK)</div>
            </td>
            <td align="right" valign="top">
                <div class="kop-meta">Tanggal Cetak: <strong>{{ now()->format('d/m/Y H:i:s') }}</strong></div>
                <div class="kop-meta">Total Data: <strong>{{ $data->count() }} Tagihan</strong></div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <table class="data-table">
        <thead>
            <tr>
                <th width="3%" class="text-center">No</th>
                <th width="10%">NIM</th>
                <th width="20%">Nama Mahasiswa</th>
                <th width="18%">Program Studi</th>
                <th width="5%" class="text-center">Angk.</th>
                <th width="11%" class="text-center">Tahun Akademik</th>
                <th width="5%" class="text-center">Smt</th>
                <th width="11%" class="text-right">Nominal (Rp)</th>
                <th width="9%" class="text-center">Status</th>
                <th width="8%" class="text-center">Jatuh Tempo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $i => $t)
                <tr class="{{ $t->status === 'sudah_dibayar' ? 'row-lunas' : '' }}">
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td class="mono"><strong>{{ $t->mahasiswa?->nim ?? '-' }}</strong></td>
                    <td>{{ $t->mahasiswa?->nama_lengkap ?? '-' }}</td>
                    <td>{{ $t->mahasiswa?->jurusan ?? '-' }}</td>
                    <td class="text-center">{{ $t->mahasiswa?->angkatan ?? '-' }}</td>
                    <td class="text-center">{{ $t->tahun_akademik }}</td>
                    <td class="text-center"><strong>{{ $t->semester }}</strong></td>
                    <td class="text-right mono"><strong>Rp {{ number_format($t->nominal, 0, ',', '.') }}</strong></td>
                    <td class="text-center">
                        @if($t->status === 'sudah_dibayar')
                            <span class="badge-lunas">LUNAS</span>
                        @elseif($t->status === 'terlambat')
                            <span class="badge-terlambat">TERLAMBAT</span>
                        @else
                            <span class="badge-belum">BELUM LUNAS</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $t->jatuh_tempo ? $t->jatuh_tempo->format('d/m/Y') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 12px; color: #94a3b8;">Tidak ada data tagihan yang ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        Dokumen ini diterbitkan resmi oleh Sistem Informasi UKT Universitas Bumi Gora &bull; Dicetak: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
