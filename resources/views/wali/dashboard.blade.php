@extends('layouts.app')

@section('content')
@php
    $metrics = [
        ['label' => 'Jumlah Siswa', 'icon' => 'bi-people', 'tone' => '', 'value' => $stats['jumlah_siswa']],
        ['label' => 'Siswa Lunas', 'icon' => 'bi-patch-check', 'tone' => 'is-success', 'value' => $stats['lunas']],
        ['label' => 'Belum Lunas', 'icon' => 'bi-exclamation-circle', 'tone' => 'is-danger', 'value' => $stats['belum_lunas']],
        ['label' => 'Total Tunggakan', 'icon' => 'bi-cash-stack', 'tone' => 'is-warning', 'value' => 'Rp '.number_format($stats['tunggakan'], 0, ',', '.')],
    ];
@endphp

<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-person-workspace"></i>Wali Kelas {{ $kelas->nama_kelas ?? '-' }}</span>
            <h3>Dashboard Wali Kelas</h3>
            <p>Ringkasan pembayaran, tunggakan, dan transaksi terbaru siswa kelas {{ $kelas->nama_kelas ?? '-' }}.</p>
        </div>
        <div class="role-hero-actions">
            <a class="btn btn-primary" href="{{ route('wali.students') }}"><i class="bi bi-people"></i>Siswa Kelas</a>
            <a class="btn btn-outline-primary" href="{{ route('wali.arrears') }}"><i class="bi bi-exclamation-circle"></i>Tunggakan</a>
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

    <section class="role-table-card">
        <div class="role-card-head">
            <div>
                <h6>Pembayaran Terbaru</h6>
                <span>Transaksi siswa di kelas {{ $kelas->nama_kelas ?? '-' }}.</span>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('wali.payments') }}"><i class="bi bi-clock-history"></i>Lihat Riwayat</a>
        </div>
        @include('partials.payments_table', ['payments' => $recentPayments, 'tableId' => 'waliRecentPayments'])
    </section>
</div>
@endsection
