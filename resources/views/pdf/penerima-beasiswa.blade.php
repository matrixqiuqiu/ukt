<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Daftar Penerima Beasiswa {{ $beasiswa->kode }}</title>
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
        .meta-bar {
            width: 100%;
            border-collapse: collapse;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            margin-bottom: 10px;
        }
        .meta-bar td {
            padding: 6px 10px;
            font-size: 8px;
            color: #334155;
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
        .badge-aktif {
            display: inline-block;
            background: #dcfce7;
            color: #15803d;
            font-weight: bold;
            font-size: 7px;
            padding: 1.5px 5px;
            border-radius: 3px;
            text-transform: uppercase;
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
                <div class="kop-title">Universitas Bumi Gora &bull; Laporan Penerima Beasiswa</div>
                <div class="kop-sub">{{ $beasiswa->nama_beasiswa }} (Kode: {{ $beasiswa->kode }})</div>
            </td>
            <td align="right" valign="top">
                <div class="kop-meta">Tanggal Cetak: <strong>{{ now()->format('d/m/Y H:i:s') }}</strong></div>
                <div class="kop-meta">Total Penerima: <strong>{{ $penerimas->count() }} Mahasiswa</strong></div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <table class="meta-bar">
        <tr>
            <td width="25%"><strong>Jenis Beasiswa:</strong> {{ $beasiswa->jenisBeasiswa?->nama ?? $beasiswa->jenis }}</td>
            <td width="25%"><strong>Periode Berlaku:</strong> {{ $beasiswa->tahunAkademik?->nama ?? 'Semua Periode' }}</td>
            <td width="25%"><strong>Diskon/Bantuan:</strong> {{ $beasiswa->tipe_diskon === 'persen' ? $beasiswa->nilai_diskon.'%' : 'Rp '.number_format($beasiswa->nilai_diskon,0,',','.') }}</td>
            <td width="25%"><strong>Penggunaan Kuota:</strong> {{ $beasiswa->terpakai }} / {{ $beasiswa->kuota ? $beasiswa->kuota.' Mahasiswa' : 'Tidak Terbatas' }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="3%" class="text-center">No</th>
                <th width="10%">NIM</th>
                <th width="20%">Nama Mahasiswa</th>
                <th width="18%">Program Studi</th>
                <th width="5%" class="text-center">Angk.</th>
                <th width="12%" class="text-center">Tahun Akademik</th>
                <th width="5%" class="text-center">Smt</th>
                <th width="13%" class="text-right">Nominal Tagihan</th>
                <th width="14%" class="text-right">Potongan Beasiswa</th>
            </tr>
        </thead>
        <tbody>
            @forelse($penerimas as $i => $p)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td class="mono"><strong>{{ $p->mahasiswa?->nim ?? '-' }}</strong></td>
                    <td>{{ $p->mahasiswa?->nama_lengkap ?? '-' }}</td>
                    <td>{{ $p->mahasiswa?->jurusan ?? '-' }}</td>
                    <td class="text-center">{{ $p->mahasiswa?->angkatan ?? '-' }}</td>
                    <td class="text-center">{{ $p->tagihan?->tahun_akademik ?? $beasiswa->tahunAkademik?->nama ?? '-' }}</td>
                    <td class="text-center"><strong>{{ $p->tagihan?->semester ?? $beasiswa->semester ?? '-' }}</strong></td>
                    <td class="text-right mono">{{ $p->tagihan ? 'Rp '.number_format($p->tagihan->nominal, 0, ',', '.') : '-' }}</td>
                    <td class="text-right mono" style="color: #047857; font-weight: bold;">Rp {{ number_format($p->diskon_diterapkan, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 12px; color: #94a3b8;">Belum ada mahasiswa penerima beasiswa ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        Dokumen ini diterbitkan resmi oleh Sistem Informasi UKT Universitas Bumi Gora &bull; Dicetak: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
