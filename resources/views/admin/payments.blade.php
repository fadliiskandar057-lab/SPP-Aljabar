@extends('layouts.app')

@section('content')
<div class="payments-hero page-title">
    <div>
        <span class="payments-kicker">Pembayaran SPP</span>
        <h3>Transaksi Pembayaran</h3>
        <p>Input pembayaran manual dan pantau riwayat transaksi pembayaran siswa.</p>
    </div>
    <div class="payments-hero-stat">
        <span>Tagihan tersedia</span>
        <strong>{{ $unpaidBills->count() }}</strong>
    </div>
</div>

<div class="content-card p-3 mb-3 payments-manual-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h6 class="mb-1 fw-bold">Input Pembayaran Manual</h6>
            <div class="text-muted small">Dipakai untuk pembayaran yang sudah diterima langsung oleh TU.</div>
        </div>
        <span class="payments-badge">{{ $unpaidBills->count() }} tagihan tersedia</span>
    </div>
    <form class="row g-3 align-items-end" method="post" action="{{ route('admin.payments.manual') }}">
        @csrf
        <div class="col-lg-3 col-md-6">
            <label class="form-label">Cari Tagihan</label>
            <input class="form-control" placeholder="Nama, NIS, bulan..." data-select-filter="#manualBillSelect">
        </div>
        <div class="col-lg-5 col-md-6">
            <label class="form-label">Tagihan Belum Lunas</label>
            <select id="manualBillSelect" name="tagihan_id" class="form-select" required>
                <option value="">Pilih tagihan belum lunas</option>
                @foreach($unpaidBills as $bill)
                    <option value="{{ $bill->id }}">{{ $bill->siswa->nis }} - {{ $bill->siswa->nama }} - {{ $bill->bulan }} {{ $bill->tahun }} - Rp {{ number_format($bill->nominal,0,',','.') }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="form-label">Tanggal Transaksi</label>
            <input name="paid_at" type="datetime-local" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
        </div>
        <div class="col-lg-2 col-md-6">
            <button class="btn btn-success w-100" type="submit"><i class="bi bi-check2-circle"></i>Simpan</button>
        </div>
    </form>
</div>

<div class="content-card p-3 payments-history-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h6 class="mb-1 fw-bold">Riwayat Transaksi</h6>
            <div id="adminPaymentsMeta" class="small text-muted">Data diperiksa: {{ $meta['checked'] }} | Hasil: {{ $payments->count() }} | Waktu: {{ $meta['duration_ms'] }} ms</div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="payments-search">
                <i class="bi bi-search"></i>
                <input class="form-control form-control-sm" placeholder="Cari invoice, NIS, nama, bulan, tanggal..." data-sequential-url="{{ route('admin.payments.search') }}" data-results-target="#adminPaymentsRows" data-meta-target="#adminPaymentsMeta" data-include-cancelled-target="#showCancelled">
            </div>
            <div class="form-check mb-0 payments-check">
                <input id="showCancelled" class="form-check-input" type="checkbox">
                <label class="form-check-label" for="showCancelled">Cancelled</label>
            </div>
        </div>
    </div>
    @include('partials.payments_table', ['payments' => $payments, 'tableId' => 'adminPaymentsTable', 'tbodyId' => 'adminPaymentsRows'])
</div>

<style>
    .payments-hero { position:relative; overflow:hidden; align-items:center; padding:1.2rem; border-color:#d6edf6; background:linear-gradient(135deg,#fff,#eef8fc); box-shadow:0 18px 44px rgba(16,24,40,.07); }
    .payments-hero::before { content:""; position:absolute; inset:0 auto 0 0; width:5px; background:linear-gradient(180deg,#16b7e8,#162d78); }
    .payments-kicker { display:inline-flex; margin-bottom:.45rem; padding:.28rem .58rem; border:1px solid #c9edf8; border-radius:999px; background:#fff; color:#075f7f; font-size:.72rem; font-weight:820; text-transform:uppercase; letter-spacing:.04em; }
    .payments-hero h3 { font-weight:850; }
    .payments-hero-stat { min-width:150px; padding:.85rem 1rem; border:1px solid #c9edf8; border-radius:8px; background:#fff; box-shadow:0 10px 26px rgba(16,24,40,.06); }
    .payments-hero-stat span { display:block; color:#667085; font-size:.76rem; font-weight:800; text-transform:uppercase; }
    .payments-hero-stat strong { display:block; color:#162d78; font-size:1.55rem; line-height:1; margin-top:.2rem; }
    .payments-manual-card,.payments-history-card { border-color:#d6edf6; box-shadow:0 18px 44px rgba(16,24,40,.07); }
    .payments-manual-card { background:linear-gradient(180deg,#fff,#f8fbfc); }
    .payments-badge { display:inline-flex; padding:.34rem .62rem; border:1px solid #c9edf8; border-radius:999px; background:#eef8fc; color:#075f7f; font-weight:760; }
    .payments-search { position:relative; min-width:min(360px,100%); }
    .payments-search i { position:absolute; left:.78rem; top:50%; transform:translateY(-50%); color:#667085; }
    .payments-search input { padding-left:2.2rem; border-radius:999px; }
    .payments-check { padding:.35rem .6rem; border:1px solid #e6eaf0; border-radius:999px; background:#f8fafc; }
    @media (max-width: 767px) { .payments-hero { display:grid; } .payments-hero-stat,.payments-search { width:100%; } }
</style>
@endsection
