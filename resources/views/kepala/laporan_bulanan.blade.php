@extends('layouts.app')

@section('content')
<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-calendar2-week"></i>Laporan Read-only</span>
            <h3>Laporan Per Bulan</h3>
            <p>Monitoring pemasukan, tunggakan, dan status tagihan berdasarkan rentang bulan yang dipilih.</p>
        </div>
        <div class="role-hero-actions">
            <a class="btn btn-outline-danger" href="{{ route('kepala.laporan.pdf', request()->query()) }}"><i class="bi bi-filetype-pdf"></i>Cetak PDF</a>
        </div>
    </section>

<div class="role-filter p-3 principal-report-filter">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h6 class="mb-1 fw-bold">Rentang Laporan</h6>
            <span class="text-muted small">Pilih periode awal dan akhir, hasil akan otomatis berubah.</span>
        </div>
        @if(request()->hasAny(['bulan_awal', 'bulan_akhir', 'tahun_awal', 'tahun_akhir']))
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('kepala.laporan') }}"><i class="bi bi-x-circle"></i>Reset</a>
        @endif
    </div>
    <form class="row g-3 align-items-end" data-auto-submit>
        <div class="col-md-3">
            <label class="form-label">Dari Tahun</label>
            <select name="tahun_awal" class="form-select">
                <option value="">Tahun awal</option>
                @foreach($yearOptions as $year)
                    <option value="{{ $year }}" @selected((int) request('tahun_awal') === $year)>{{ $year }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Dari Bulan</label>
            <select name="bulan_awal" class="form-select">
                <option value="">Januari</option>
                @foreach($months as $number => $name)
                    <option value="{{ $number }}" @selected((int) request('bulan_awal') === $number)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Sampai Tahun</label>
            <select name="tahun_akhir" class="form-select">
                <option value="">Tahun akhir</option>
                @foreach($yearOptions as $year)
                    <option value="{{ $year }}" @selected((int) request('tahun_akhir') === $year)>{{ $year }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Sampai Bulan</label>
            <select name="bulan_akhir" class="form-select">
                <option value="">Desember</option>
                @foreach($months as $number => $name)
                    <option value="{{ $number }}" @selected((int) request('bulan_akhir') === $number)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    </form>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="metric-card principal-metric principal-metric-income"><div class="metric-label">Pemasukan</div><div class="metric-value">Rp {{ number_format($summary['pemasukan'],0,',','.') }}</div></div></div>
    <div class="col-md-3"><div class="metric-card principal-metric principal-metric-arrears"><div class="metric-label">Tunggakan</div><div class="metric-value">Rp {{ number_format($summary['tunggakan'],0,',','.') }}</div></div></div>
    <div class="col-md-3"><div class="metric-card principal-metric"><div class="metric-label">Tagihan Lunas</div><div class="metric-value">{{ $summary['lunas'] }}</div></div></div>
    <div class="col-md-3"><div class="metric-card principal-metric principal-metric-unpaid"><div class="metric-label">Belum Lunas</div><div class="metric-value">{{ $summary['belum_lunas'] }}</div></div></div>
</div>

<div class="role-table-card principal-report-card">
    <div class="role-card-head">
        <div>
            <h6>Ringkasan Per Siswa</h6>
            <span>{{ $reportRows->count() }} baris ringkas berdasarkan status pembayaran.</span>
        </div>
        <div class="role-search">
            <i class="bi bi-search"></i>
            <input class="form-control form-control-sm" placeholder="Cari laporan..." data-live-search="#principalMonthlyTable">
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle principal-report-table" id="principalMonthlyTable">
            <thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Status</th><th>Rentang Bulan</th><th>Jumlah Bulan</th><th>Total Nominal</th></tr></thead>
            <tbody>
                @forelse($reportRows as $row)
                    @php $modalId = 'principalReportDetail'.$loop->iteration; @endphp
                    <tr class="principal-report-row principal-report-row-{{ $row['status'] }}">
                        <td><span class="principal-nis">{{ $row['siswa']->nis }}</span></td>
                        <td><button class="principal-name-button" type="button" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">{{ $row['siswa']->nama }} <i class="bi bi-chevron-right"></i></button></td>
                        <td>{{ $row['siswa']->kelas->nama_kelas }}</td>
                        <td><x-status :status="$row['status']"/></td>
                        <td><span class="principal-range">{{ $row['bulan_awal'] }} - {{ $row['bulan_akhir'] }}</span></td>
                        <td><span class="principal-month-count">{{ $row['jumlah_bulan'] }} bulan</span></td>
                        <td><strong class="principal-total">Rp {{ number_format($row['total'],0,',','.') }}</strong></td>
                    </tr>
                @empty
                    <tr data-empty-row><td colspan="7" class="empty-state">Data tidak tersedia.</td></tr>
                @endforelse
                <tr data-empty-row style="display:none"><td colspan="7" class="empty-state">Data tidak ditemukan.</td></tr>
            </tbody>
        </table>
    </div>
</div>

@foreach($reportRows as $row)
    @php $modalId = 'principalReportDetail'.$loop->iteration; @endphp
    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1">{{ $row['siswa']->nama }} - {{ $row['status_label'] }}</h5>
                        <div class="small text-muted">{{ $row['bulan_awal'] }} sampai {{ $row['bulan_akhir'] }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Bulan</th><th>Nominal</th><th>Status</th><th>Tanggal Bayar</th><th>Invoice</th></tr></thead>
                            <tbody>
                                @foreach($row['details'] as $detail)
                                    <tr>
                                        <td>{{ $detail['bulan'] }}</td>
                                        <td>Rp {{ number_format($detail['nominal'],0,',','.') }}</td>
                                        <td><x-status :status="$detail['status']"/></td>
                                        <td>{{ $detail['paid_at'] ? $detail['paid_at']->format('d/m/Y H:i') : '-' }}</td>
                                        <td>@if($detail['payment_id'])<a href="{{ route('invoice.show', $detail['payment_id']) }}">{{ $detail['invoice'] }}</a>@else<span class="text-muted">-</span>@endif</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach

<style>
    .principal-report-filter { border-color:#d6edf6; }
    .principal-metric::after { display:none; }
    .principal-metric-income::before { background:linear-gradient(180deg,#16b7e8,#0794c9); }
    .principal-metric-arrears::before,.principal-metric-unpaid::before { background:linear-gradient(180deg,#fb7185,#e52632); }
    .principal-report-card .table-responsive { border-color:#d6edf6; }
    .principal-report-row-lunas { box-shadow:inset 3px 0 0 #0794c9; }
    .principal-report-row-belum_lunas { box-shadow:inset 3px 0 0 #e52632; }
    .principal-report-row-gratis { box-shadow:inset 3px 0 0 #16b7e8; }
    .principal-nis { font-weight:760; color:#475467; }
    .principal-name-button { display:inline-flex; align-items:center; gap:.35rem; border:0; background:transparent; color:#162d78; padding:0; font-weight:820; text-align:left; }
    .principal-name-button:hover { color:#0794c9; }
    .principal-name-button i { font-size:.75rem; }
    .principal-range { display:inline-flex; padding:.26rem .5rem; border-radius:999px; background:#eef8fc; color:#0e205a; font-weight:720; font-size:.82rem; }
    .principal-month-count { display:inline-flex; min-width:72px; justify-content:center; padding:.25rem .5rem; border-radius:999px; background:#f8fafc; color:#475467; border:1px solid #e6eaf0; font-weight:760; }
    .principal-total { color:#102027; white-space:nowrap; }
</style>
</div>
@endsection
