@extends('layouts.app')

@section('content')
@php
    $metrics = [
        ['label' => 'Pemasukan Bulan Ini', 'icon' => 'bi-cash-stack', 'tone' => 'is-success', 'value' => 'Rp '.number_format($stats['pemasukan_bulan_ini'], 0, ',', '.')],
        ['label' => 'Siswa Lunas', 'icon' => 'bi-patch-check', 'tone' => 'is-success', 'value' => $stats['siswa_lunas']],
        ['label' => 'Belum Lunas', 'icon' => 'bi-exclamation-circle', 'tone' => 'is-danger', 'value' => $stats['siswa_belum_lunas']],
        ['label' => 'Menunggu', 'icon' => 'bi-hourglass-split', 'tone' => 'is-warning', 'value' => $stats['menunggu']],
    ];
    $incomeTotal = collect($monthlyIncome)->sum();
@endphp

<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-shield-check"></i>Monitoring Read-only</span>
            <h3>Dashboard Kepala Sekolah</h3>
            <p>Pantau pemasukan, tunggakan, dan status pembayaran tanpa akses perubahan data.</p>
        </div>
        <div class="role-hero-actions">
            <a href="{{ route('kepala.laporan') }}" class="btn btn-primary"><i class="bi bi-calendar2-week"></i>Laporan Bulanan</a>
            <a href="{{ route('kepala.laporan.pdf') }}" class="btn btn-outline-danger"><i class="bi bi-filetype-pdf"></i>Cetak PDF</a>
        </div>
    </section>

    <div class="role-filter p-3 d-flex align-items-center gap-2 text-primary fw-semibold">
        <i class="bi bi-info-circle"></i>
        <span>Mode monitoring aktif. Data ditampilkan untuk evaluasi dan pelaporan.</span>
    </div>

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

    <section class="role-table-card p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h6 class="mb-1 fw-bold">Grafik Pemasukan 12 Bulan</h6>
                <span class="text-muted small">Total tahun berjalan: Rp {{ number_format($incomeTotal, 0, ',', '.') }}</span>
            </div>
            <span class="role-chip"><i class="bi bi-eye"></i>Read-only</span>
        </div>
        <div class="role-chart-box"><canvas id="incomeChart"></canvas></div>
    </section>
</div>

@push('scripts')
<script>
new Chart(document.getElementById('incomeChart'), {
    type: 'line',
    data: {
        labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
        datasets: [{ label: 'Pemasukan', data: @json($monthlyIncome), borderColor: '#16b7e8', backgroundColor: 'rgba(22,183,232,.14)', fill: true, tension: .34, pointRadius: 4, pointBackgroundColor: '#162d78' }]
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
