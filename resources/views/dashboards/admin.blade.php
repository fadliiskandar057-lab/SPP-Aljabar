@extends('layouts.app')

@section('content')
@php
    $metrics = [
        ['label' => 'Pemasukan Bulan Ini', 'icon' => 'bi-cash-stack', 'tone' => 'is-success', 'value' => 'Rp '.number_format($stats['pemasukan_bulan_ini'], 0, ',', '.')],
        ['label' => 'Siswa Lunas', 'icon' => 'bi-patch-check', 'tone' => 'is-success', 'value' => $stats['siswa_lunas']],
        ['label' => 'Belum Lunas', 'icon' => 'bi-exclamation-circle', 'tone' => 'is-danger', 'value' => $stats['siswa_belum_lunas']],
        ['label' => 'Menunggu Konfirmasi', 'icon' => 'bi-hourglass-split', 'tone' => 'is-warning', 'value' => $stats['menunggu']],
    ];
    $incomeTotal = collect($monthlyIncome)->sum();
@endphp

<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-speedometer2"></i>Operasional Admin TU</span>
            <h3>Dashboard Pembayaran SPP</h3>
            <p>Ringkasan pemasukan, status pembayaran siswa, dan transaksi terbaru dalam satu tampilan kerja.</p>
        </div>
        <div class="role-hero-actions">
            <a href="{{ route('admin.laporan') }}" class="btn btn-primary"><i class="bi bi-file-earmark-bar-graph"></i>Laporan Bulanan</a>
            <a href="{{ route('admin.payments') }}" class="btn btn-outline-primary"><i class="bi bi-credit-card"></i>Transaksi</a>
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

    <section class="role-dashboard-grid">
        <div class="role-table-card p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h6 class="mb-1 fw-bold">Grafik Pemasukan Bulanan</h6>
                    <span class="text-muted small">Total tahun berjalan: Rp {{ number_format($incomeTotal, 0, ',', '.') }}</span>
                </div>
                <span class="role-chip"><i class="bi bi-calendar3"></i>Tahun berjalan</span>
            </div>
            <div class="role-chart-box"><canvas id="incomeChart"></canvas></div>
        </div>

        <div class="role-table-card">
            <div class="role-card-head">
                <div>
                    <h6>Transaksi Terbaru</h6>
                    <span>Riwayat pembayaran yang baru masuk.</span>
                </div>
                <div class="role-search">
                    <i class="bi bi-search"></i>
                    <input class="form-control form-control-sm" placeholder="Cari transaksi..." data-live-search="#recentPaymentsTable">
                </div>
            </div>
            @include('partials.payments_table', ['payments' => $recentPayments, 'tableId' => 'recentPaymentsTable'])
        </div>
    </section>
</div>

@push('scripts')
<script>
new Chart(document.getElementById('incomeChart'), {
    type: 'bar',
    data: {
        labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
        datasets: [{ label: 'Pemasukan', data: @json($monthlyIncome), backgroundColor: '#16b7e8', borderRadius: 8, maxBarThickness: 38 }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grid: { color: '#edf1f5' } }, x: { grid: { display: false } } }
    }
});
</script>
@endpush
@endsection
