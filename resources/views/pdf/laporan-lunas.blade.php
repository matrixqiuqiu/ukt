<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><title>Laporan Lunas</title>
<style>@page{size:A4 landscape;margin:10mm} body{font-family:DejaVu Sans,sans-serif;font-size:8px;color:#1e293b} h2{margin:0;font-size:14px} .meta{color:#64748b;font-size:8px;margin:4px 0 10px} table{width:100%;border-collapse:collapse} th{background:#f1f5f9;padding:5px 6px;border:1px solid #cbd5e1;font-size:7px;text-transform:uppercase} td{padding:4px 6px;border:1px solid #cbd5e1} tr:nth-child(even) td{background:#f8fafc} .footer{text-align:center;color:#94a3b8;font-size:7px;margin-top:8px;border-top:1px solid #e2e8f0;padding-top:6px}</style>
</head><body>
<h2>Laporan Pembayaran Lunas</h2>
<div class="meta">Dicetak: {{ now()->format('d/m/Y H:i') }} — Total: {{ $data->count() }} data</div>
<table><thead><tr><th>No</th><th>NIM</th><th>Nama</th><th>Jurusan</th><th>Angk.</th><th>Tahun Akademik</th><th>Smt</th><th>Nominal</th><th>Tgl Lunas</th><th>Metode</th><th>VA</th></tr></thead>
<tbody>
@forelse($data as $i=>$p)
<tr><td>{{ $i+1 }}</td><td style="font-family:monospace">{{ $p->tagihan?->mahasiswa?->nim ?? '-' }}</td><td>{{ $p->tagihan?->mahasiswa?->nama_lengkap ?? '-' }}</td><td>{{ $p->tagihan?->mahasiswa?->jurusan ?? '-' }}</td><td style="text-align:center">{{ $p->tagihan?->mahasiswa?->angkatan ?? '-' }}</td><td>{{ $p->tagihan?->tahun_akademik ?? '-' }}</td><td style="text-align:center">{{ $p->tagihan?->semester ?? '-' }}</td><td style="text-align:right">Rp {{ number_format($p->jumlah_bayar,0,',','.') }}</td><td>{{ $p->verified_at?->format('d/m/Y H:i') ?? $p->updated_at?->format('d/m/Y H:i') }}</td><td>{{ $p->metodePembayaran?->nama_metode ?? '-' }}</td><td style="font-family:monospace;font-size:7px">{{ $p->va_number ?? '-' }}</td></tr>
@empty<tr><td colspan="11" style="text-align:center;color:#94a3b8">Tidak ada data</td></tr>
@endforelse
</tbody></table>
<div class="footer">Sistem UKT UBG — Laporan Lunas — {{ now()->format('d/m/Y') }}</div>
</body></html>
