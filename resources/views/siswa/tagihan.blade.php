@extends('layouts.app')

@section('content')
<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-receipt"></i>Portal Siswa</span>
            <h3>Tagihan Saya</h3>
            <p>Daftar tagihan per bulan. Pembayaran bisa online melalui Midtrans atau tunai ke TU.</p>
        </div>
        <div class="role-hero-actions"><span class="role-icon-tile"><i class="bi bi-receipt-cutoff"></i></span></div>
    </section>

    <div class="role-table-card">
        <div class="role-card-head">
        <div>
            <h6>Daftar Tagihan</h6>
            <span id="studentBillsCount" class="live-search-count"></span>
        </div>
        <div class="role-search">
            <i class="bi bi-search"></i>
            <input class="form-control form-control-sm" placeholder="Cari bulan, tahun, nominal, status..." data-live-search="#studentBillsTable" data-live-count="#studentBillsCount">
        </div>
        </div>
        <div class="p-0">
            @include('partials.bills_table', ['tagihan' => $tagihan, 'tableId' => 'studentBillsTable'])
        </div>
        <div class="p-3">{{ $tagihan->links() }}</div>
    </div>
</div>
@endsection
