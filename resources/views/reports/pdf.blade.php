<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px 28px; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #17202a;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5px;
            line-height: 1.35;
            background: #ffffff;
        }
        .report {
            border: 1.4px solid #162d78;
            padding: 16px 18px 14px;
            position: relative;
        }
        .report:before {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            height: 7px;
            background: #162d78;
        }
        table { width: 100%; border-collapse: collapse; }
        .header {
            padding-top: 7px;
            padding-bottom: 12px;
            border-bottom: 2px solid #162d78;
            margin-bottom: 12px;
        }
        .logo-cell { width: 70px; vertical-align: middle; }
        .logo { width: 58px; height: 58px; object-fit: contain; }
        .school-title {
            margin: 0;
            color: #0b4f49;
            font-size: 18px;
            line-height: 1.15;
            font-weight: 800;
            text-transform: uppercase;
        }
        .school-subtitle {
            margin-top: 3px;
            color: #667085;
            font-size: 10px;
        }
        .doc-title {
            width: 250px;
            text-align: right;
            vertical-align: middle;
        }
        .doc-title h1 {
            margin: 0;
            color: #162d78;
            font-size: 20px;
            line-height: 1.1;
        }
        .doc-title p {
            margin: 4px 0 0;
            color: #667085;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        .summary-table { margin: 11px 0 12px; }
        .summary-table td { padding-right: 8px; }
        .summary-card {
            min-height: 58px;
            padding: 9px 10px;
            border: 1px solid #c8e5e0;
            background: #f1f8f7;
        }
        .summary-card.dark {
            background: #162d78;
            border-color: #162d78;
            color: #ffffff;
        }
        .summary-label {
            font-size: 8.5px;
            color: #667085;
            text-transform: uppercase;
            letter-spacing: .35px;
            font-weight: 800;
        }
        .summary-card.dark .summary-label { color: rgba(255,255,255,.78); }
        .summary-value {
            margin-top: 4px;
            font-size: 14px;
            font-weight: 800;
            color: #17202a;
        }
        .summary-card.dark .summary-value {
            color: #ffffff;
            font-size: 15px;
        }
        .filters {
            margin-bottom: 12px;
            border: 1px solid #d9e7e5;
        }
        .filters td {
            padding: 7px 9px;
            border-right: 1px solid #e6efed;
            vertical-align: top;
        }
        .filters td:last-child { border-right: 0; }
        .filter-label {
            color: #667085;
            font-size: 8.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .35px;
        }
        .filter-value {
            margin-top: 2px;
            color: #17202a;
            font-weight: 700;
        }
        .section-title {
            margin: 12px 0 8px;
            color: #0b4f49;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        .data-table th,
        .data-table td {
            border: 1px solid #d9e1e5;
            padding: 6px 7px;
            vertical-align: top;
        }
        .data-table th {
            background: #162d78;
            color: #ffffff;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .3px;
        }
        .data-table tbody tr:nth-child(even) td {
            background: #f8fbfb;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .status {
            display: inline-block;
            min-width: 82px;
            padding: 4px 7px;
            border-radius: 999px;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
        }
        .status-lunas { background: #087443; }
        .status-belum_lunas { background: #b42318; }
        .status-menunggu_konfirmasi { background: #b45309; }
        .status-default { background: #667085; }
        .group-table { margin-bottom: 11px; }
        .group-total { color: #162d78; font-weight: 800; }
        .range-pill {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 999px;
            background: #eef8fc;
            color: #162d78;
            font-weight: 800;
            font-size: 8.5px;
        }
        .empty {
            padding: 24px;
            text-align: center;
            color: #667085;
            border: 1px dashed #cfd8dc;
            background: #fbfcfd;
        }
        .footer {
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px solid #d9e7e5;
            color: #667085;
            font-size: 9px;
        }
        .footer-table td { vertical-align: top; }
        .signature {
            width: 210px;
            text-align: center;
        }
        .signature-space { height: 36px; }
        .signature-name {
            display: inline-block;
            min-width: 150px;
            padding-top: 4px;
            border-top: 1px solid #17202a;
            color: #17202a;
            font-weight: 800;
        }
    </style>
</head>
<body>
@php
    $logoPath = public_path('images/logo-sekolah.svg');
    $summary = $summary ?? [
        'pemasukan' => $tagihan->where('status', 'lunas')->sum('nominal'),
        'tunggakan' => $tagihan->whereNotIn('status', ['lunas', 'gratis'])->sum('nominal'),
        'lunas' => $tagihan->where('status', 'lunas')->count(),
        'gratis' => $tagihan->where('status', 'gratis')->count(),
        'belum_lunas' => $tagihan->where('status', 'belum_lunas')->count(),
        'menunggu' => $tagihan->where('status', 'menunggu_konfirmasi')->count(),
        'total' => $tagihan->count(),
    ];
    $filters = $filters ?? [
        'bulan' => 'Semua bulan',
        'tahun_ajaran' => 'Semua tahun',
        'kelas' => 'Semua kelas',
        'status' => 'Semua status',
        'keyword' => '-',
    ];
    $reportRows = $reportRows ?? collect();
@endphp

<div class="report">
    <div class="header">
        <table>
            <tr>
                <td class="logo-cell">
                    @if(file_exists($logoPath))
                        <img class="logo" src="{{ $logoPath }}" alt="Logo Sekolah">
                    @else
                        <div class="logo"></div>
                    @endif
                </td>
                <td>
                    <h2 class="school-title">MA Plus Taruna Teknik Al Jabbar</h2>
                    <div class="school-subtitle">Rekapitulasi pembayaran SPP siswa untuk arsip administrasi dan monitoring sekolah</div>
                </td>
                <td class="doc-title">
                    <h1>Laporan Pembayaran SPP</h1>
                    <p>Dicetak {{ now()->format('d/m/Y H:i') }}</p>
                </td>
            </tr>
        </table>
    </div>

    <table class="summary-table">
        <tr>
            <td>
                <div class="summary-card dark">
                    <div class="summary-label">Total Pemasukan</div>
                    <div class="summary-value">Rp {{ number_format($summary['pemasukan'], 0, ',', '.') }}</div>
                </div>
            </td>
            <td>
                <div class="summary-card">
                    <div class="summary-label">Total Tunggakan</div>
                    <div class="summary-value">Rp {{ number_format($summary['tunggakan'], 0, ',', '.') }}</div>
                </div>
            </td>
            <td>
                <div class="summary-card">
                    <div class="summary-label">Tagihan Lunas</div>
                    <div class="summary-value">{{ $summary['lunas'] }} data</div>
                </div>
            </td>
            <td>
                <div class="summary-card">
                    <div class="summary-label">Belum / Menunggu</div>
                    <div class="summary-value">{{ $summary['belum_lunas'] + $summary['menunggu'] }} data</div>
                </div>
            </td>
            <td style="padding-right:0">
                <div class="summary-card">
                    <div class="summary-label">Total Data</div>
                    <div class="summary-value">{{ $summary['total'] }} data</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="filters">
        <tr>
            <td>
                <div class="filter-label">Periode</div>
                <div class="filter-value">{{ $filters['bulan'] }}</div>
            </td>
            <td>
                <div class="filter-label">Basis</div>
                <div class="filter-value">{{ $filters['tahun_ajaran'] }}</div>
            </td>
            <td>
                <div class="filter-label">Kelas</div>
                <div class="filter-value">{{ $filters['kelas'] }}</div>
            </td>
            <td>
                <div class="filter-label">Status</div>
                <div class="filter-value">{{ $filters['status'] }}</div>
            </td>
            <td>
                <div class="filter-label">Kata Kunci</div>
                <div class="filter-value">{{ $filters['keyword'] }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Ringkasan Per Siswa</div>
    @if($reportRows->count())
        <table class="data-table group-table">
            <thead>
                <tr>
                    <th style="width:8%">NIS</th>
                    <th style="width:22%">Nama Siswa</th>
                    <th style="width:11%">Kelas</th>
                    <th style="width:13%" class="text-center">Status</th>
                    <th style="width:22%">Rentang Bulan</th>
                    <th style="width:10%" class="text-center">Jumlah</th>
                    <th style="width:14%" class="text-right">Total Nominal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportRows as $row)
                    @php
                        $statusClass = $row['status'] === 'lunas'
                            ? 'status-lunas'
                            : ($row['status'] === 'belum_lunas' ? 'status-belum_lunas' : 'status-default');
                    @endphp
                    <tr>
                        <td>{{ $row['siswa']->nis }}</td>
                        <td>{{ $row['siswa']->nama }}</td>
                        <td>{{ $row['siswa']->kelas->nama_kelas }}</td>
                        <td class="text-center"><span class="status {{ $statusClass }}">{{ $row['status_label'] }}</span></td>
                        <td><span class="range-pill">{{ $row['bulan_awal'] }} - {{ $row['bulan_akhir'] }}</span></td>
                        <td class="text-center">{{ $row['jumlah_bulan'] }} bulan</td>
                        <td class="text-right group-total">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">Data laporan tidak ditemukan untuk filter yang dipilih.</div>
    @endif

    <div class="section-title">Detail Tagihan dan Pembayaran</div>
    @if($reportRows->count())
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:8%">NIS</th>
                    <th style="width:20%">Nama Siswa</th>
                    <th style="width:10%">Kelas</th>
                    <th style="width:13%">Bulan</th>
                    <th style="width:13%" class="text-right">Nominal</th>
                    <th style="width:12%" class="text-center">Status</th>
                    <th style="width:13%">Tanggal Bayar</th>
                    <th style="width:11%">Invoice</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportRows as $row)
                    @foreach($row['details'] as $detail)
                        @php
                            $statusClass = in_array($detail['status'], ['lunas', 'belum_lunas', 'menunggu_konfirmasi'], true)
                                ? 'status-'.$detail['status']
                                : 'status-default';
                        @endphp
                        <tr>
                            <td>{{ $row['siswa']->nis }}</td>
                            <td>{{ $row['siswa']->nama }}</td>
                            <td>{{ $row['siswa']->kelas->nama_kelas }}</td>
                            <td>{{ $detail['bulan'] }}</td>
                            <td class="text-right">Rp {{ number_format($detail['nominal'], 0, ',', '.') }}</td>
                            <td class="text-center"><span class="status {{ $statusClass }}">{{ str_replace('_', ' ', $detail['status']) }}</span></td>
                            <td>{{ $detail['paid_at'] ? $detail['paid_at']->format('d/m/Y H:i') : '-' }}</td>
                            <td>{{ $detail['invoice'] ?: '-' }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td>
                    Laporan ini dibuat otomatis oleh sistem pembayaran SPP. Gunakan dokumen ini sebagai arsip monitoring penerimaan dan tunggakan.
                </td>
                <td class="signature">
                    Admin TU,
                    <div class="signature-space"></div>
                    <span class="signature-name">Admin TU Digital</span>
                </td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
