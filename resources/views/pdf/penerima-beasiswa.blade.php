<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Penerima {{ $beasiswa->kode }}</title>
<style>
@page { size: A4 landscape; margin: 12mm 10mm; }
body{ font-family: DejaVu Sans, sans-serif; font-size: 9px; color:#1e293b; }
h2{ margin:0; font-size:14px; }
.meta{ color:#64748b; font-size:9px; margin:4px 0 10px; }
table{ width:100%; border-collapse:collapse; }
th{ background:#f1f5f9; text-align:left; padding:6px 7px; border:1px solid #cbd5e1; font-size:8px; text-transform:uppercase; }
td{ padding:5px 7px; border:1px solid #cbd5e1; }
tr:nth-child(even) td{ background:#f8fafc; }
.footer{ text-align:center; color:#94a3b8; font-size:7px; margin-top:10px; border-top:1px solid #e2e8f0; padding-top:6px; }
.badge{ display:inline-block; padding:2px 6px; border-radius:9999px; color:#fff; font-size:7px; font-weight:700; background:#0ea5e9; }
</style>
</head>
<body>
<h2>Daftar Penerima Beasiswa — {{ $beasiswa->nama_beasiswa }} ({{ $beasiswa->kode }})</h2>
<div class="meta">
    Jenis: {{ $beasiswa->jenisBeasiswa?->nama ?? $beasiswa->jenis }} |
    Periode: {{ $beasiswa->tahunAkademik?->nama ?? 'Umum' }} {{ $beasiswa->semester ? ($beasiswa->semester==1?'Ganjil':'Genap') : '' }} |
    Kuota: {{ $beasiswa->terpakai }}/{{ $beasiswa->kuota ?: '∞' }} |
    Diskon: {{ $beasiswa->tipe_diskon }} {{ $beasiswa->nilai_diskon }} |
    Dicetak: {{ now()->format('d/m/Y H:i') }}
</div>
<table>
<thead><tr><th>No</th><th>NIM</th><th>Nama</th><th>Jurusan</th><th>Angk.</th><th>Tahun Akademik</th><th>Smt</th><th>Nominal</th><th>Diskon</th><th>Status</th></tr></thead>
<tbody>
@forelse($penerimas as $i => $p)
<tr>
    <td>{{ $i+1 }}</td>
    <td style="font-family: monospace;">{{ $p->mahasiswa?->nim ?? '-' }}</td>
    <td>{{ $p->mahasiswa?->nama_lengkap ?? '-' }}</td>
    <td>{{ $p->mahasiswa?->jurusan ?? '-' }}</td>
    <td style="text-align:center;">{{ $p->mahasiswa?->angkatan ?? '-' }}</td>
    <td>{{ $p->tagihan?->tahun_akademik ?? $beasiswa->tahunAkademik?->nama ?? '-' }}</td>
    <td style="text-align:center;">{{ $p->tagihan?->semester ?? $beasiswa->semester ?? '-' }}</td>
    <td style="text-align:right;">{{ $p->tagihan ? 'Rp '.number_format($p->tagihan->nominal,0,',','.') : '-' }}</td>
    <td style="text-align:right;">Rp {{ number_format($p->diskon_diterapkan,0,',','.') }}</td>
    <td><span class="badge">{{ $p->status }}</span></td>
</tr>
@empty
<tr><td colspan="10" style="text-align:center; color:#94a3b8;">Belum ada penerima</td></tr>
@endforelse
</tbody>
</table>
<div class="footer">Sistem UKT UBG — Dokumen penerima beasiswa {{ $beasiswa->kode }} — Halaman 1/1</div>
</body>
</html>
