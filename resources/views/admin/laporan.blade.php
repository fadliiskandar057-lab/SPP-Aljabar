@extends('layouts.app')

@section('content')
<div class="report-hero page-title">
    <div>
        <span class="report-kicker">Laporan Keuangan</span>
        <h3>Laporan Bulanan</h3>
        <p>Filter laporan, export PDF/Excel, dan cari nama atau NIS memakai Sequential Search.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-danger" href="{{ auth()->user()->role === 'kepala_sekolah' ? route('kepala.laporan.pdf', request()->query()) : route('admin.laporan.pdf', request()->query()) }}"><i class="bi bi-filetype-pdf"></i>PDF</a>
        @if(auth()->user()->role === 'admin_tu')
            <a class="btn btn-outline-success" href="{{ route('admin.laporan.excel', request()->query()) }}"><i class="bi bi-file-earmark-spreadsheet"></i>Excel</a>
        @endif
    </div>
</div>

<div class="content-card p-3 mb-3 report-filter-card">
    <form class="row g-3 align-items-end" data-auto-submit>
        <div class="col-lg-2 col-md-4">
            <label class="form-label">Dari Tahun</label>
            <select name="tahun_awal" class="form-select">
                <option value="">Tahun awal</option>
                @foreach($yearOptions as $year)
                    <option value="{{ $year }}" @selected((int) request('tahun_awal') === $year)>{{ $year }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label">Dari Bulan</label>
            <select name="bulan_awal" class="form-select">
                <option value="">Januari</option>
                @foreach($months as $number => $name)
                    <option value="{{ $number }}" @selected((int) request('bulan_awal') === $number)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label">Sampai Tahun</label>
            <select name="tahun_akhir" class="form-select">
                <option value="">Tahun akhir</option>
                @foreach($yearOptions as $year)
                    <option value="{{ $year }}" @selected((int) request('tahun_akhir') === $year)>{{ $year }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label">Sampai Bulan</label>
            <select name="bulan_akhir" class="form-select">
                <option value="">Desember</option>
                @foreach($months as $number => $name)
                    <option value="{{ $number }}" @selected((int) request('bulan_akhir') === $number)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label">Kelas</label>
            <select name="kelas_id" class="form-select">
                <option value="">Semua</option>
                @foreach($kelas as $k)
                    <option value="{{ $k->id }}" @selected(request('kelas_id') == $k->id)>{{ $k->nama_kelas }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">Semua</option>
                <option value="lunas" @selected(request('status') === 'lunas')>Lunas</option>
                <option value="gratis" @selected(request('status') === 'gratis')>Gratis</option>
                <option value="belum_lunas" @selected(request('status') === 'belum_lunas')>Belum Lunas</option>
                <option value="menunggu_konfirmasi" @selected(request('status') === 'menunggu_konfirmasi')>Menunggu</option>
            </select>
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label">Nama / NIS</label>
            <div class="report-search-field">
                <i class="bi bi-search"></i>
                <input name="keyword" value="{{ request('keyword') }}" class="form-control" type="search" placeholder="Kata kunci">
            </div>
        </div>
    </form>
    @if(request()->hasAny(['bulan_awal', 'bulan_akhir', 'tahun_awal', 'tahun_akhir', 'kelas_id', 'status', 'keyword']))
        <div class="mt-3">
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.laporan') }}"><i class="bi bi-x-circle"></i> Reset Filter</a>
        </div>
    @endif
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="metric-card report-metric report-metric-income">
            <div class="metric-label">Total Pemasukan</div>
            <div class="metric-value">Rp {{ number_format($summary['pemasukan'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="metric-card report-metric report-metric-arrears">
            <div class="metric-label">Total Tunggakan</div>
            <div class="metric-value">Rp {{ number_format($summary['tunggakan'], 0, ',', '.') }}</div>
        </div>
    </div>
</div>

<div class="content-card p-3 report-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h6 class="mb-1 fw-bold">Ringkasan Laporan</h6>
            <div class="small text-muted">Sequential check: {{ $meta['checked'] }} data | {{ $meta['duration_ms'] }} ms | {{ $reportRows->count() }} baris ringkas</div>
        </div>
        <div>
            <input class="form-control form-control-sm" style="min-width:260px" placeholder="Filter tabel ringkas..." data-live-search="#reportTable" data-live-count="#reportCount">
            <div id="reportCount" class="live-search-count mt-1"></div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 report-table" id="reportTable">
            <thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Status</th><th>Rentang Bulan</th><th>Jumlah Bulan</th><th>Total Nominal</th></tr></thead>
            <tbody>
                @forelse($reportRows as $row)
                    @php $modalId = 'reportDetail'.$loop->iteration; @endphp
                    <tr class="report-row report-row-{{ $row['status'] }}">
                        <td><span class="report-nis">{{ $row['siswa']->nis }}</span></td>
                        <td>
                            <button class="report-name-button" type="button" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                                {{ $row['siswa']->nama }}
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </td>
                        <td>{{ $row['siswa']->kelas->nama_kelas }}</td>
                        <td><x-status :status="$row['status']"/></td>
                        <td><span class="report-range">{{ $row['bulan_awal'] }} - {{ $row['bulan_akhir'] }}</span></td>
                        <td><span class="report-month-count">{{ $row['jumlah_bulan'] }} bulan</span></td>
                        <td><strong class="report-total">Rp {{ number_format($row['total'], 0, ',', '.') }}</strong></td>
                    </tr>
                @empty
                    <tr data-empty-row><td colspan="7" class="empty-state">Data laporan tidak ditemukan.</td></tr>
                @endforelse
                <tr data-empty-row style="display:none"><td colspan="7" class="empty-state">Tidak ada laporan yang cocok dengan pencarian.</td></tr>
            </tbody>
        </table>
    </div>
</div>

@foreach($reportRows as $row)
    @php $modalId = 'reportDetail'.$loop->iteration; @endphp
    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1">{{ $row['siswa']->nama }} - {{ $row['status_label'] }}</h5>
                        <div class="small text-muted">{{ $row['bulan_awal'] }} sampai {{ $row['bulan_akhir'] }} | Total Rp {{ number_format($row['total'], 0, ',', '.') }}</div>
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
                                        <td>Rp {{ number_format($detail['nominal'], 0, ',', '.') }}</td>
                                        <td><x-status :status="$detail['status']"/></td>
                                        <td>{{ $detail['paid_at'] ? $detail['paid_at']->format('d/m/Y H:i') : '-' }}</td>
                                        <td>
                                            @if($detail['payment_id'])
                                                <a href="{{ route('invoice.show', $detail['payment_id']) }}">{{ $detail['invoice'] }}</a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
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
    .report-search-field { position:relative; }
    .report-search-field i { position:absolute; left:.75rem; top:50%; transform:translateY(-50%); color:#667085; pointer-events:none; }
    .report-search-field .form-control { padding-left:2.1rem; }
    .report-filter-card { border-color:#d6edf6; }
    .report-metric::after { display:none; }
    .report-metric { min-height:118px; }
    .report-metric-income::before { background:linear-gradient(180deg,#16b7e8,#0794c9); }
    .report-metric-arrears::before { background:linear-gradient(180deg,#fb7185,#e52632); }
    .report-card .table-responsive { border-color:#d6edf6; }
    .report-table tbody tr { transition:background .16s ease, box-shadow .16s ease; }
    .report-row-lunas { box-shadow:inset 3px 0 0 #0794c9; }
    .report-row-belum_lunas { box-shadow:inset 3px 0 0 #e52632; }
    .report-row-gratis { box-shadow:inset 3px 0 0 #16b7e8; }
    .report-nis { font-weight:760; color:#475467; }
    .report-name-button { display:inline-flex; align-items:center; gap:.35rem; border:0; background:transparent; color:#162d78; padding:0; font-weight:820; text-align:left; }
    .report-name-button:hover { color:#0794c9; }
    .report-name-button i { font-size:.75rem; }
    .report-range { display:inline-flex; padding:.26rem .5rem; border-radius:999px; background:#eef8fc; color:#0e205a; font-weight:720; font-size:.82rem; }
    .report-month-count { display:inline-flex; min-width:72px; justify-content:center; padding:.25rem .5rem; border-radius:999px; background:#f8fafc; color:#475467; border:1px solid #e6eaf0; font-weight:760; }
    .report-total { color:#102027; white-space:nowrap; }
    .report-hero { position:relative; overflow:hidden; align-items:center; padding:1.2rem; border-color:#d6edf6; background:linear-gradient(135deg,#fff,#eef8fc); box-shadow:0 18px 44px rgba(16,24,40,.07); }
    .report-hero::before { content:""; position:absolute; inset:0 auto 0 0; width:5px; background:linear-gradient(180deg,#16b7e8,#162d78); }
    .report-kicker { display:inline-flex; margin-bottom:.45rem; padding:.28rem .58rem; border:1px solid #c9edf8; border-radius:999px; background:#fff; color:#075f7f; font-size:.72rem; font-weight:820; text-transform:uppercase; letter-spacing:.04em; }
    .report-hero h3 { font-weight:850; }
    .report-filter-card { background:linear-gradient(180deg,#fff,#f8fbfc); box-shadow:0 18px 44px rgba(16,24,40,.07); }
    .report-filter-card .form-select,.report-filter-card .form-control { border-radius:999px; }
    .report-card { box-shadow:0 18px 44px rgba(16,24,40,.07); }
    .report-card > .d-flex { padding-bottom:.85rem; border-bottom:1px solid #edf1f5; }
    .report-card input[data-live-search] { border-radius:999px; }
    .report-metric { box-shadow:0 12px 32px rgba(16,24,40,.06); }
    .report-metric .metric-label { color:#667085; }
</style>
@endsection
