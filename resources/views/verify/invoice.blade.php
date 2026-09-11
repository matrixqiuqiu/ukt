<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isValid && $data ? 'Verifikasi Dokumen: ' . $data['invoice_number'] . ' - ' . ($data['student']['name'] ?? '') : 'Verifikasi Dokumen Tidak Valid' }} | Universitas Bumi Gora</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />

    @php
        $theme = \App\Models\ThemeSetting::instance();
        $logoUrl = $theme->logo_url ?: ($theme->invoice_logo ?: '/assets/images/logo_ubg.png');
        $siteName = $theme->website_name ?: 'UNIVERSITAS BUMI GORA';
    @endphp

    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --emerald: #059669;
            --emerald-light: #ecfdf5;
            --emerald-border: #a7f3d0;
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-300: #cbd5e1;
            --slate-500: #64748b;
            --slate-700: #334155;
            --slate-900: #0f172a;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
            --shadow-xl: 0 20px 25px -5px rgba(15, 23, 42, 0.1), 0 8px 10px -6px rgba(15, 23, 42, 0.05);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f1f5f9;
            color: var(--slate-900);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* Top Navbar */
        .public-navbar {
            background: #ffffff;
            border-bottom: 1px solid var(--slate-200);
            padding: 0.875rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: var(--shadow-sm);
        }

        .nav-container {
            max-width: 960px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            text-decoration: none;
            color: inherit;
        }

        .nav-logo-box {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .nav-logo-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .nav-title-group h1 {
            font-size: 1rem;
            font-weight: 800;
            color: var(--slate-900);
            line-height: 1.2;
            letter-spacing: -0.01em;
        }

        .nav-title-group p {
            font-size: 0.6875rem;
            font-weight: 600;
            color: var(--slate-500);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .nav-badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.6875rem;
            font-weight: 700;
            background: var(--slate-100);
            border: 1px solid var(--slate-200);
            color: var(--slate-700);
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            position: relative;
        }

        .pulse-dot::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: #10b981;
            animation: pulse-ring 1.8s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
        }

        @keyframes pulse-ring {
            0% { transform: scale(0.9); opacity: 0.8; }
            70% { transform: scale(2.4); opacity: 0; }
            100% { transform: scale(2.4); opacity: 0; }
        }

        /* Main Content Container */
        .main-wrapper {
            flex: 1;
            padding: 2rem 1rem 3rem;
            display: flex;
            justify-content: center;
        }

        .content-container {
            width: 100%;
            max-width: 860px;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* Verification Card */
        .verify-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid var(--slate-200);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        /* Hero Banner Valid */
        .hero-banner-valid {
            background: linear-gradient(135deg, #065f46 0%, #047857 50%, #059669 100%);
            color: #ffffff;
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .hero-banner-valid::after {
            content: '';
            position: absolute;
            right: -2rem;
            bottom: -2rem;
            width: 180px;
            height: 180px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero-icon-box {
            width: 68px;
            height: 68px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #ffffff;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .hero-text-box h2 {
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -0.01em;
            margin-bottom: 0.35rem;
            line-height: 1.2;
        }

        .hero-text-box p {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.4;
        }

        /* Hero Banner Invalid */
        .hero-banner-invalid {
            background: linear-gradient(135deg, #991b1b 0%, #b91c1c 50%, #dc2626 100%);
            color: #ffffff;
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        /* Quick Info Bar */
        .meta-strip {
            background: var(--slate-50);
            border-bottom: 1px solid var(--slate-200);
            padding: 0.875rem 1.5rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            font-size: 0.75rem;
            color: var(--slate-500);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .meta-item strong {
            color: var(--slate-900);
            font-weight: 700;
        }

        /* Card Body */
        .card-body {
            padding: 1.75rem 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.75rem;
        }

        /* 2-Column Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .info-panel {
            border: 1px solid var(--slate-200);
            border-radius: 12px;
            background: #ffffff;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .panel-header {
            background: var(--slate-100);
            border-bottom: 1px solid var(--slate-200);
            padding: 0.625rem 1rem;
            font-size: 0.75rem;
            font-weight: 800;
            color: var(--slate-700);
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .panel-body {
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            flex: 1;
        }

        .data-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.8125rem;
        }

        .data-label {
            color: var(--slate-500);
            font-weight: 500;
            flex-shrink: 0;
        }

        .data-value {
            color: var(--slate-900);
            font-weight: 600;
            text-align: right;
            word-break: break-word;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
        }

        .text-indigo {
            color: var(--primary);
        }

        .amount-pill {
            display: inline-block;
            background: var(--emerald-light);
            color: #065f46;
            border: 1px solid var(--emerald-border);
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9375rem;
            font-weight: 800;
            padding: 0.25rem 0.625rem;
            border-radius: 6px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        .badge-lunas {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .badge-pending {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .badge-danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Breakdown Table */
        .table-wrap {
            border: 1px solid var(--slate-200);
            border-radius: 12px;
            overflow: hidden;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
        }

        .detail-table th {
            background: var(--slate-100);
            color: var(--slate-700);
            font-weight: 700;
            padding: 0.625rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--slate-200);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .detail-table td {
            padding: 0.875rem 1rem;
            border-bottom: 1px solid var(--slate-200);
            vertical-align: middle;
        }

        .detail-table tr:last-child td {
            border-bottom: none;
        }

        .detail-table tfoot td {
            background: var(--slate-50);
            padding: 0.75rem 1rem;
            font-weight: 700;
            border-top: 2px solid var(--slate-300);
        }

        /* Verification Security Certificate Box */
        .security-box {
            border: 1px dashed var(--slate-300);
            background: var(--slate-50);
            border-radius: 12px;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .sec-left {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            flex: 1;
            min-width: 260px;
        }

        .qr-thumb {
            width: 76px;
            height: 76px;
            background: #ffffff;
            border: 1px solid var(--slate-200);
            border-radius: 8px;
            padding: 4px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qr-thumb img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .sec-text h4 {
            font-size: 0.875rem;
            font-weight: 800;
            color: var(--slate-900);
            display: flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 0.25rem;
        }

        .sec-text p {
            font-size: 0.75rem;
            color: var(--slate-500);
            line-height: 1.4;
            margin-bottom: 0.35rem;
        }

        .signature-hash {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.6875rem;
            color: var(--slate-700);
            background: #ffffff;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            border: 1px solid var(--slate-200);
            word-break: break-all;
            display: inline-block;
        }

        .sec-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-shrink: 0;
        }

        .stamp-badge {
            border: 2px dashed #059669;
            background: rgba(16, 185, 129, 0.08);
            border-radius: 8px;
            padding: 0.5rem 0.875rem;
            text-align: center;
            transform: rotate(-2deg);
        }

        .stamp-badge-title {
            font-size: 0.75rem;
            font-weight: 800;
            color: #065f46;
            letter-spacing: 0.05em;
        }

        .stamp-badge-sub {
            font-size: 0.5625rem;
            font-family: 'JetBrains Mono', monospace;
            color: #059669;
            font-weight: 600;
        }

        /* Action Toolbar */
        .action-toolbar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
            flex-wrap: wrap;
            padding-top: 0.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-family: inherit;
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid transparent;
        }

        .btn-secondary {
            background: #ffffff;
            color: var(--slate-700);
            border-color: var(--slate-300);
        }

        .btn-secondary:hover {
            background: var(--slate-50);
            color: var(--slate-900);
            border-color: var(--slate-400);
        }

        .btn-primary {
            background: var(--primary);
            color: #ffffff;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            color: #ffffff;
        }

        /* Footer */
        .public-footer {
            text-align: center;
            padding: 1.5rem;
            color: var(--slate-500);
            font-size: 0.75rem;
            line-height: 1.5;
            border-top: 1px solid var(--slate-200);
            background: #ffffff;
            margin-top: auto;
        }

        .public-footer p {
            margin-bottom: 0.25rem;
        }

        /* Responsive */
        @media (max-width: 640px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .hero-banner-valid, .hero-banner-invalid {
                flex-direction: column;
                text-align: center;
                padding: 1.5rem;
            }

            .card-body {
                padding: 1.25rem;
            }

            .action-toolbar {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                width: 100%;
            }

            .sec-left {
                flex-direction: column;
                text-align: center;
            }

            .sec-right {
                width: 100%;
                justify-content: center;
            }
        }

        /* Print Mode */
        @media print {
            .public-navbar, .action-toolbar, .public-footer {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .main-wrapper {
                padding: 0 !important;
            }
            .verify-card {
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
            @page {
                size: A4 portrait;
                margin: 10mm 12mm;
            }
        }
    </style>
</head>
<body>

    <!-- 1. TOP NAVBAR -->
    <header class="public-navbar">
        <div class="nav-container">
            <a href="/" class="nav-brand">
                <div class="nav-logo-box">
                    <img src="{{ $logoUrl }}" alt="Logo Kampus" class="nav-logo-img" onerror="this.onerror=null;this.src='/favicon.ico';">
                </div>
                <div class="nav-title-group">
                    <h1>{{ $data['institution']['name'] ?? $siteName }}</h1>
                    <p>Sistem Informasi & Verifikasi Keuangan UKT</p>
                </div>
            </a>
            <div class="nav-badge-pill">
                <span class="pulse-dot"></span>
                <span>Basis Data Terhubung</span>
            </div>
        </div>
    </header>

    <!-- 2. MAIN BODY -->
    <main class="main-wrapper">
        <div class="content-container">

            @if($isValid && $data)
                <!-- VALID DOCUMENT CARD -->
                <div class="verify-card">
                    <!-- Hero Status Valid -->
                    <div class="hero-banner-valid">
                        <div class="hero-icon-box">
                            <i class="fas fa-shield-check"></i>
                        </div>
                        <div class="hero-text-box">
                            <h2>DOKUMEN FAKTUR SAH & TERVERIFIKASI</h2>
                            <p>
                                Faktur Pembayaran UKT dengan nomor dokumen <strong style="color:#ffffff;font-family:'JetBrains Mono',monospace;">{{ $data['invoice_number'] }}</strong> terdaftar sah di basis data resmi Universitas.
                            </p>
                        </div>
                    </div>

                    <!-- Meta Quick Strip -->
                    <div class="meta-strip">
                        <div class="meta-item">
                            <i class="fas fa-clock text-indigo"></i>
                            <span>Waktu Verifikasi: <strong>{{ now()->translatedFormat('d F Y, H:i:s') }} WITA</strong></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-fingerprint text-indigo"></i>
                            <span>Otentikasi: <strong>HMAC-SHA256 Signed</strong></span>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- 2-Column Info Grid -->
                        <div class="info-grid">
                            <!-- Left: Data Mahasiswa -->
                            <div class="info-panel">
                                <div class="panel-header">
                                    <i class="fas fa-user-graduate"></i>
                                    <span>IDENTITAS MAHASISWA</span>
                                </div>
                                <div class="panel-body">
                                    <div class="data-row">
                                        <span class="data-label">Nama Lengkap</span>
                                        <span class="data-value">{{ $data['student']['name'] ?? '-' }}</span>
                                    </div>
                                    <div class="data-row">
                                        <span class="data-label">NIM</span>
                                        <span class="data-value font-mono">{{ $data['student']['nim'] ?? '-' }}</span>
                                    </div>
                                    <div class="data-row">
                                        <span class="data-label">Program Studi</span>
                                        <span class="data-value">{{ $data['student']['jurusan'] ?? $data['student']['program_studi'] ?? '-' }}</span>
                                    </div>
                                    <div class="data-row">
                                        <span class="data-label">Angkatan</span>
                                        <span class="data-value">{{ $data['student']['angkatan'] ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Data Tagihan & Pembayaran -->
                            <div class="info-panel">
                                <div class="panel-header">
                                    <i class="fas fa-receipt"></i>
                                    <span>INFORMASI TAGIHAN & STATUS</span>
                                </div>
                                <div class="panel-body">
                                    <div class="data-row">
                                        <span class="data-label">No. Dokumen</span>
                                        <span class="data-value font-mono text-indigo">{{ $data['invoice_number'] }}</span>
                                    </div>
                                    <div class="data-row">
                                        <span class="data-label">Status Pembayaran</span>
                                        <span class="data-value">
                                            @if(strtolower($data['status_label']) === 'paid')
                                                <span class="status-badge badge-lunas">
                                                    <i class="fas fa-check-circle"></i> LUNAS / TERKONFIRMASI
                                                </span>
                                            @elseif(strtolower($data['status_label']) === 'expired')
                                                <span class="status-badge badge-danger">
                                                    <i class="fas fa-times-circle"></i> KEDALUWARSA (EXPIRED)
                                                </span>
                                            @else
                                                <span class="status-badge badge-pending">
                                                    <i class="fas fa-clock"></i> MENUNGGU PEMBAYARAN
                                                </span>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="data-row">
                                        <span class="data-label">Total Nominal</span>
                                        <span class="data-value">
                                            <span class="amount-pill">{{ $data['total_label'] }}</span>
                                        </span>
                                    </div>
                                    <div class="data-row">
                                        <span class="data-label">Metode Pembayaran</span>
                                        <span class="data-value">{{ $data['payment']['method'] ?? 'Virtual Account / Bank Transfer' }}</span>
                                    </div>
                                    <div class="data-row">
                                        <span class="data-label">Waktu Transaksi</span>
                                        <span class="data-value">{{ $data['payment']['paid_at_label'] ?? $data['invoice_date_label'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Itemized Table -->
                        <div class="table-wrap">
                            <table class="detail-table">
                                <thead>
                                    <tr>
                                        <th style="width: 50px; text-align: center;">No</th>
                                        <th>Rincian Biaya</th>
                                        <th style="text-align: right; width: 220px;">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="text-align: center; color: var(--slate-500); font-weight: 700;">1</td>
                                        <td>
                                            <div style="font-weight: 700; color: var(--slate-900); font-size: 0.875rem;">
                                                {{ $data['item']['description'] ?? 'Uang Kuliah Tunggal (UKT)' }}
                                            </div>
                                            @if(!empty($data['item']['meta']))
                                                <div style="font-size: 0.75rem; color: var(--slate-500); margin-top: 0.2rem;">
                                                    {{ implode(' • ', $data['item']['meta']) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td style="text-align: right; font-family: 'JetBrains Mono', monospace; font-weight: 700; color: var(--slate-900);">
                                            {{ $data['item']['amount_label'] }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2" style="text-align: right; color: var(--slate-700);">TOTAL DIVERIFIKASI:</td>
                                        <td style="text-align: right; font-family: 'JetBrains Mono', monospace; font-size: 1rem; color: #065f46;">
                                            {{ $data['total_label'] }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Security Certificate & Stamp Box -->
                        <div class="security-box">
                            <div class="sec-left">
                                @if(!empty($data['qr_code']))
                                    <div class="qr-thumb">
                                        <img src="{{ $data['qr_code'] }}" alt="QR Verifikasi">
                                    </div>
                                @endif
                                <div class="sec-text">
                                    <h4>
                                        <i class="fas fa-certificate text-indigo"></i>
                                        JAMINAN KEABSAHAN ELEKTRONIK
                                    </h4>
                                    <p>
                                        Dokumen ini diterbitkan secara sah dan ditandatangani secara kriptografis oleh Sistem Administrasi Keuangan UKT Universitas Bumi Gora.
                                    </p>
                                    @if($signature)
                                        <span class="signature-hash" title="Signature Hash">
                                            SIG: {{ $signature }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="sec-right">
                                <div class="stamp-badge">
                                    <div class="stamp-badge-title"><i class="fas fa-check-circle"></i> TERVERIFIKASI</div>
                                    <div class="stamp-badge-sub">BAK UBG OFFICIAL</div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Toolbar -->
                        <div class="action-toolbar">
                            <button type="button" onclick="window.print()" class="btn btn-secondary">
                                <i class="fas fa-print"></i> Cetak Halaman Verifikasi
                            </button>
                            @if(!empty($pdfUrl))
                                <a href="{{ $pdfUrl }}" target="_blank" class="btn btn-primary">
                                    <i class="fas fa-file-pdf"></i> Unduh / Buka Dokumen PDF Resmi
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

            @else
                <!-- INVALID DOCUMENT CARD -->
                <div class="verify-card">
                    <div class="hero-banner-invalid">
                        <div class="hero-icon-box">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="hero-text-box">
                            <h2>TANDA TANGAN DOKUMEN TIDAK VALID</h2>
                            <p>
                                Tautan verifikasi dokumen telah kedaluwarsa, tidak terdaftar, atau parameter tanda tangan digital telah dimodifikasi.
                            </p>
                        </div>
                    </div>
                    <div class="card-body" style="text-align: center; padding: 3rem 2rem;">
                        <i class="fas fa-shield-slash" style="font-size: 3.5rem; color: #ef4444; margin-bottom: 1rem;"></i>
                        <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--slate-900); margin-bottom: 0.5rem;">
                            Dokumen Tidak Dapat Divalidasi
                        </h3>
                        <p style="font-size: 0.875rem; color: var(--slate-500); max-width: 480px; margin: 0 auto 1.5rem;">
                            Pastikan Anda memindai kode QR resmi langsung dari lembar dokumen asli atau hubungi Bagian Administrasi Keuangan untuk verifikasi manual.
                        </p>
                        <a href="/" class="btn btn-secondary">
                            <i class="fas fa-home"></i> Kembali ke Halaman Utama
                        </a>
                    </div>
                </div>
            @endif

        </div>
    </main>

    <!-- 3. FOOTER -->
    <footer class="public-footer">
        <p>&copy; {{ date('Y') }} {{ $data['institution']['name'] ?? $siteName }}. Seluruh hak cipta dilindungi undang-undang.</p>
        <p style="color: var(--slate-400); font-size: 0.6875rem;">
            Layanan Verifikasi Dokumen Elektronik Resmi &bull; {{ $data['institution']['address'] ?? 'Jl. Ismail Marzuki No. 22, Mataram, Nusa Tenggara Barat' }}
        </p>
    </footer>

</body>
</html>
