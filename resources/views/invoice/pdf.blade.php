<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 26px 30px; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #17202a;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.45;
            background: #ffffff;
        }
        .receipt {
            position: relative;
            border: 1.5px solid #162d78;
            padding: 20px 22px 18px;
        }
        .receipt:before {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            height: 8px;
            background: #162d78;
        }
        .header {
            padding-top: 8px;
            border-bottom: 2px solid #162d78;
            padding-bottom: 14px;
            margin-bottom: 16px;
        }
        .header-table,
        .meta-table,
        .detail-table,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        .logo-cell {
            width: 78px;
            vertical-align: middle;
        }
        .logo {
            width: 64px;
            height: 64px;
            object-fit: contain;
        }
        .school-title {
            margin: 0;
            font-size: 20px;
            line-height: 1.15;
            color: #0b4f49;
            font-weight: 800;
            text-transform: uppercase;
        }
        .school-subtitle {
            margin-top: 3px;
            color: #475467;
            font-size: 11px;
        }
        .document-title {
            text-align: right;
            vertical-align: middle;
            width: 190px;
        }
        .document-title h2 {
            margin: 0;
            color: #162d78;
            font-size: 18px;
            letter-spacing: .4px;
        }
        .document-title p {
            margin: 3px 0 0;
            color: #667085;
            font-size: 10px;
            text-transform: uppercase;
        }
        .summary-card {
            background: #f1f8f7;
            border: 1px solid #c8e5e0;
            padding: 12px 14px;
            margin-bottom: 14px;
        }
        .meta-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .meta-label {
            color: #667085;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .35px;
        }
        .meta-value {
            margin-top: 2px;
            font-size: 13px;
            font-weight: 700;
            color: #17202a;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: #162d78;
            color: #ffffff;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .4px;
            text-transform: uppercase;
        }
        .section-title {
            margin: 16px 0 8px;
            color: #0b4f49;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .45px;
        }
        .detail-table {
            border: 1px solid #d9e7e5;
            margin-bottom: 12px;
        }
        .detail-table td {
            padding: 9px 11px;
            border-bottom: 1px solid #e6efed;
            vertical-align: top;
        }
        .detail-table tr:last-child td {
            border-bottom: 0;
        }
        .detail-label {
            width: 32%;
            color: #667085;
            background: #f8fbfb;
            font-weight: 700;
        }
        .detail-value {
            color: #17202a;
            font-weight: 650;
        }
        .amount-box {
            margin-top: 14px;
            padding: 14px 16px;
            background: #162d78;
            color: #ffffff;
        }
        .amount-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .5px;
            opacity: .86;
        }
        .amount-value {
            margin-top: 3px;
            font-size: 25px;
            font-weight: 800;
        }
        .note {
            margin-top: 12px;
            padding: 10px 12px;
            color: #475467;
            background: #fbfcfd;
            border: 1px dashed #cfd8dc;
            font-size: 10.5px;
        }
        .signature-table {
            margin-top: 28px;
        }
        .signature-table td {
            vertical-align: top;
        }
        .stamp {
            color: #162d78;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        .signature {
            width: 230px;
            text-align: center;
        }
        .signature-space {
            height: 52px;
        }
        .signature-name {
            display: inline-block;
            min-width: 165px;
            padding-top: 5px;
            border-top: 1px solid #17202a;
            font-weight: 800;
        }
        .footer {
            margin-top: 16px;
            padding-top: 8px;
            border-top: 1px solid #d9e7e5;
            color: #667085;
            font-size: 9.5px;
            text-align: center;
        }
    </style>
</head>
<body>
@php
    $status = strtoupper(str_replace('_', ' ', $payment->tagihan->status));
    $paidAt = $payment->paid_at?->format('d/m/Y H:i') ?? '-';
    $logoPath = public_path('images/logo-sekolah.svg');
@endphp

<div class="receipt">
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if(file_exists($logoPath))
                        <img class="logo" src="{{ $logoPath }}" alt="Logo Sekolah">
                    @else
                        <div class="logo"></div>
                    @endif
                </td>
                <td>
                    <h1 class="school-title">MA Plus Taruna Teknik Al Jabbar</h1>
                    <div class="school-subtitle">Portal Pembayaran SPP Digital - Bukti transaksi resmi sekolah</div>
                </td>
                <td class="document-title">
                    <h2>Kwitansi SPP</h2>
                    <p>Bukti pembayaran</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="summary-card">
        <table class="meta-table">
            <tr>
                <td>
                    <div class="meta-label">Nomor Kwitansi</div>
                    <div class="meta-value">{{ $payment->kode_invoice }}</div>
                </td>
                <td>
                    <div class="meta-label">Tanggal Lunas</div>
                    <div class="meta-value">{{ $paidAt }}</div>
                </td>
                <td style="text-align:right">
                    <span class="status-badge">{{ $status }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Data Siswa</div>
    <table class="detail-table">
        <tr>
            <td class="detail-label">Nama Siswa</td>
            <td class="detail-value">{{ $payment->siswa->nama }}</td>
        </tr>
        <tr>
            <td class="detail-label">NIS</td>
            <td class="detail-value">{{ $payment->siswa->nis }}</td>
        </tr>
        <tr>
            <td class="detail-label">Kelas</td>
            <td class="detail-value">{{ $payment->siswa->kelas->nama_kelas }}</td>
        </tr>
    </table>

    <div class="section-title">Rincian Pembayaran</div>
    <table class="detail-table">
        <tr>
            <td class="detail-label">Pembayaran Untuk</td>
            <td class="detail-value">SPP Bulan {{ $payment->tagihan->bulan }} {{ $payment->tagihan->tahun }}</td>
        </tr>
        <tr>
            <td class="detail-label">Metode Pembayaran</td>
            <td class="detail-value">{{ strtoupper($payment->metode) }}</td>
        </tr>
        <tr>
            <td class="detail-label">Diverifikasi Oleh</td>
            <td class="detail-value">{{ $payment->verifier->name ?? 'Admin TU Digital' }}</td>
        </tr>
    </table>

    <div class="amount-box">
        <div class="amount-label">Total pembayaran diterima</div>
        <div class="amount-value">Rp {{ number_format($payment->nominal, 0, ',', '.') }}</div>
    </div>

    <div class="note">
        Kwitansi ini diterbitkan secara digital oleh sistem pembayaran SPP MA Plus Taruna Teknik Al Jabbar.
        Simpan dokumen ini sebagai arsip pembayaran siswa. Data dianggap sah sesuai status pembayaran pada portal sekolah.
    </div>

    <table class="signature-table">
        <tr>
            <td>
                <div class="stamp">Lunas melalui sistem</div>
                <div>Dicetak pada {{ now()->format('d/m/Y H:i') }}</div>
            </td>
            <td class="signature">
                Admin TU,
                <div class="signature-space"></div>
                <span class="signature-name">{{ $payment->verifier->name ?? 'Admin TU Digital' }}</span>
            </td>
        </tr>
    </table>

    <div class="footer">
        Dokumen ini tidak memerlukan tanda tangan basah apabila diunduh langsung dari portal pembayaran sekolah.
    </div>
</div>
</body>
</html>
