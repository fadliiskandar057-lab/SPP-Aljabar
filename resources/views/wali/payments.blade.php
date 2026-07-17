@extends('layouts.app')

@section('content')
<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-clock-history"></i>Kelas {{ $kelas->nama_kelas ?? '-' }}</span>
            <h3>Riwayat Pembayaran Kelas</h3>
            <p>Pembayaran siswa kelas {{ $kelas->nama_kelas ?? '-' }} yang tercatat di sistem.</p>
        </div>
        <div class="role-hero-actions"><span class="role-icon-tile"><i class="bi bi-journal-check"></i></span></div>
    </section>

<div class="role-filter p-3">
    <form class="row g-2 align-items-end">
        <div class="col-md-10"><label class="form-label">Cari Pembayaran</label><input name="keyword" value="{{ request('keyword') }}" class="form-control" placeholder="Invoice, nama, atau NIS"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-search"></i>Cari</button></div>
    </form>
</div>

<div class="role-table-card">
    <div class="role-card-head"><div><h6>Daftar Pembayaran</h6><span>Riwayat transaksi siswa kelas.</span></div></div>
    @include('partials.payments_table', ['payments' => $payments, 'tableId' => 'waliPaymentsTable'])
</div>
</div>
@endsection
