@extends('layouts.app')

@section('content')
<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-clock-history"></i>Riwayat Siswa</span>
            <h3>Riwayat Pembayaran</h3>
            <p>Kwitansi tersedia untuk pembayaran yang sudah settlement/success atau tunai yang sudah diverifikasi.</p>
        </div>
        <div class="role-hero-actions"><span class="role-icon-tile"><i class="bi bi-journal-check"></i></span></div>
    </section>

    <div class="role-table-card">
        <div class="role-card-head">
        <div>
            <h6>Daftar Pembayaran</h6>
            <span id="studentPaymentsCount" class="live-search-count"></span>
        </div>
        <div class="role-search">
            <i class="bi bi-search"></i>
            <input class="form-control form-control-sm" placeholder="Cari invoice, bulan, metode, status..." data-live-search="#studentPaymentsTable" data-live-count="#studentPaymentsCount">
        </div>
        </div>
        @include('partials.payments_table', ['payments' => $payments, 'tableId' => 'studentPaymentsTable'])
        <div class="p-3">{{ $payments->links() }}</div>
    </div>
</div>
@endsection
