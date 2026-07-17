@extends('layouts.app')

@section('content')
@php
    $unpaidCount = $tagihan->where('status', 'belum_lunas')->count();
    $paidCount = $tagihan->where('status', 'lunas')->count();
    $arrearsTotal = $tagihan->whereNotIn('status', ['lunas', 'gratis'])->sum('nominal');
    $latestBill = $tagihan->first();
    $metrics = [
        ['label' => 'Total Tagihan', 'icon' => 'bi-journal-text', 'tone' => '', 'value' => $tagihan->count()],
        ['label' => 'Belum Lunas', 'icon' => 'bi-exclamation-circle', 'tone' => 'is-danger', 'value' => $unpaidCount],
        ['label' => 'Lunas', 'icon' => 'bi-patch-check', 'tone' => 'is-success', 'value' => $paidCount],
        ['label' => 'Total Tunggakan', 'icon' => 'bi-cash-stack', 'tone' => 'is-warning', 'value' => 'Rp '.number_format($arrearsTotal, 0, ',', '.')],
    ];
@endphp

<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-person-badge"></i>Portal Siswa / Orang Tua</span>
            <h3>Dashboard Tagihan SPP</h3>
            <p>Pantau tagihan aktif, status pembayaran, dan kwitansi digital dari satu tempat.</p>
        </div>
        <div class="role-hero-actions">
            <a href="{{ route('siswa.tagihan') }}" class="btn btn-primary"><i class="bi bi-receipt"></i>Bayar Tagihan</a>
            <a href="{{ route('siswa.riwayat') }}" class="btn btn-outline-primary"><i class="bi bi-clock-history"></i>Riwayat</a>
        </div>
    </section>

    <section class="role-stat-grid">
        @foreach($metrics as $metric)
            <div class="role-stat-card {{ $metric['tone'] }}">
                <span class="role-stat-icon"><i class="bi {{ $metric['icon'] }}"></i></span>
                <div>
                    <p class="role-stat-label">{{ $metric['label'] }}</p>
                    <strong class="role-stat-value">{{ $metric['value'] }}</strong>
                </div>
            </div>
        @endforeach
    </section>

    @if($latestBill)
        <section class="role-filter p-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <span class="role-kicker mb-2"><i class="bi bi-calendar-check"></i>Tagihan Terbaru</span>
                <h5 class="mb-1 fw-bold">{{ $latestBill->bulan }} {{ $latestBill->tahun }}</h5>
                <p class="mb-0 text-muted">Nominal Rp {{ number_format($latestBill->nominal, 0, ',', '.') }} dengan status {{ str_replace('_', ' ', $latestBill->status) }}.</p>
            </div>
            <a href="{{ route('siswa.tagihan') }}" class="btn btn-outline-primary"><i class="bi bi-arrow-right-circle"></i>Lihat Tagihan</a>
        </section>
    @endif

    <section class="role-table-card">
        <div class="role-card-head">
            <div>
                <h6>Tagihan Terbaru</h6>
                <span>Pilih pembayaran online atau tunai untuk tagihan yang belum lunas.</span>
            </div>
            <div class="role-search">
                <i class="bi bi-search"></i>
                <input class="form-control form-control-sm" placeholder="Cari bulan/status..." data-live-search="#dashboardBillsTable">
            </div>
        </div>
        @include('partials.bills_table', ['tagihan' => $tagihan->take(8), 'tableId' => 'dashboardBillsTable'])
    </section>
</div>
@endsection
