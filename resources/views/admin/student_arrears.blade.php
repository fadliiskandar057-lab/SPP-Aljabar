@extends('layouts.app')

@section('content')
<div class="arrears-page-title page-title">
    <div>
        <span class="arrears-kicker">Monitoring Tunggakan</span>
        <h3>Tunggakan Siswa</h3>
        <p>Atur bulan terakhir siswa membayar, siapkan tagihan sesuai jadwal tagihan otomatis, lalu konfirmasi tunggakan dari bubble bulan.</p>
    </div>
    <button class="btn btn-primary arrears-primary-action" type="button" data-bs-toggle="modal" data-bs-target="#setLastPaidModal">
        <i class="bi bi-calendar-plus"></i> Set Terakhir Bayar
    </button>
</div>

<div class="modal fade" id="setLastPaidModal" tabindex="-1" aria-labelledby="setLastPaidModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="{{ route('admin.arrears.set-last-paid') }}" class="arrears-form">
                @csrf
                <div class="modal-header">
                    <div class="d-flex align-items-start gap-3">
                        <span class="arrears-icon"><i class="bi bi-calendar-plus"></i></span>
                        <div>
                            <h5 class="modal-title mb-1" id="setLastPaidModalLabel">Set Terakhir Bayar</h5>
                            <p class="small text-muted mb-0">Jika terakhir bayar Januari 2025, sistem membuat tagihan mulai Februari 2025 sampai bulan tagihan terakhir yang sudah waktunya keluar.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Kelas</label>
                            <select id="arrearsClassFilter" name="kelas_id" class="form-select">
                                <option value="">Semua kelas</option>
                                @foreach($kelas as $k)
                                    <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tahun Ajaran</label>
                            <select name="tahun_ajaran_id" class="form-select">
                                @foreach($tahun as $t)
                                    <option value="{{ $t->id }}" @selected($activeYear?->id === $t->id)>{{ $t->nama }}{{ $t->is_active ? ' (aktif)' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Siswa</label>
                            <input class="form-control mb-1" placeholder="Cari NIS, nama, atau kelas..." data-select-filter="#arrearsStudentSelect" data-select-class-filter="#arrearsClassFilter" data-select-meta="#arrearsStudentMeta">
                            <div id="arrearsStudentMeta" class="small text-muted mb-2">Data tersedia: {{ $siswaOptions->count() }} | Ditampilkan: {{ $siswa->count() }} dari {{ $siswa->total() }}</div>
                            <select id="arrearsStudentSelect" name="siswa_id" class="form-select" required>
                                <option value="all" data-kelas-id="">Semua siswa aktif</option>
                                @foreach($siswaOptions as $item)
                                    <option value="{{ $item->id }}" data-kelas-id="{{ $item->kelas_id }}">{{ $item->nis }} - {{ $item->nama }} - {{ $item->kelas->nama_kelas ?? '-' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Bulan terakhir bayar</label>
                            <select name="last_paid_month" class="form-select" required>
                                @foreach($months as $number => $name)
                                    <option value="{{ $number }}" @selected(now()->subMonth()->month === $number)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tahun</label>
                            <input name="last_paid_year" type="number" class="form-control" value="{{ now()->year }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tanggal jatuh tempo</label>
                            <input name="due_day" type="number" class="form-control" value="{{ old('due_day', $autoBillSetting->due_day ?? 10) }}" min="1" max="28">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary"><i class="bi bi-magic me-1"></i>Siapkan Tagihan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12">
        <div class="content-card p-3 arrears-board">
            <div class="arrears-toolbar mb-3">
                <div>
                    <h6 class="mb-1">Daftar Tunggakan</h6>
                    <div class="small text-muted">Sequential check: {{ $meta['checked'] }} data | Hasil: {{ $meta['count'] }} | {{ $meta['duration_ms'] }} ms</div>
                </div>
                <form class="arrears-search-panel" method="get" data-auto-submit>
                    <div class="search-field">
                        <i class="bi bi-search"></i>
                        <input name="keyword" value="{{ request('keyword') }}" class="form-control form-control-sm" type="search" placeholder="Cari siswa, NIS, atau kelas">
                    </div>
                    <select name="kelas_id" class="form-select form-select-sm">
                        <option value="">Semua kelas</option>
                        @foreach($kelas as $k)
                            <option value="{{ $k->id }}" @selected(request('kelas_id') == $k->id)>{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                    <select name="min_arrears_months" class="form-select form-select-sm">
                        <option value="">Semua tunggakan</option>
                        @for($monthCount = 2; $monthCount <= 12; $monthCount++)
                            <option value="{{ $monthCount }}" @selected((int) request('min_arrears_months') === $monthCount)>Minimal {{ $monthCount }} bulan</option>
                        @endfor
                    </select>
                    @if(request()->hasAny(['keyword', 'kelas_id', 'min_arrears_months']))
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.arrears.students') }}">Reset</a>
                    @endif
                </form>
            </div>

            <div class="arrears-summary-strip mb-3">
                <div class="summary-students">
                    <i class="bi bi-people"></i>
                    <span>Siswa Terfilter</span>
                    <strong>{{ $arrearsSummary['students'] }}</strong>
                </div>
                <div class="summary-months">
                    <i class="bi bi-calendar-x"></i>
                    <span>Total Bulan Tunggakan</span>
                    <strong>{{ $arrearsSummary['months'] }}</strong>
                </div>
                <div class="summary-total">
                    <i class="bi bi-cash-stack"></i>
                    <span>Total Nominal Harus Dibayar</span>
                    <strong>Rp {{ number_format($arrearsSummary['total'], 0, ',', '.') }}</strong>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle" id="arrearsTable">
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>Kelas</th>
                            <th>Tunggakan</th>
                            <th>Total Harus Dibayar</th>
                            <th>Invoice terakhir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($siswa as $item)
                            @php
                                $unpaidBills = $item->tagihan
                                    ->whereNotIn('status', ['lunas', 'gratis'])
                                    ->sortBy(fn ($bill) => sprintf('%04d%02d', $bill->tahun, array_search($bill->bulan, $months, true) ?: 1))
                                    ->values();
                                $paidBills = $item->tagihan
                                    ->whereIn('status', ['lunas', 'gratis'])
                                    ->sortByDesc(fn ($bill) => sprintf('%04d%02d', $bill->tahun, array_search($bill->bulan, $months, true) ?: 1))
                                    ->take(12)
                                    ->values();
                                $lastPayment = $item->pembayaran->where('status', 'success')->sortByDesc('paid_at')->first();
                                $arrearsMonths = $unpaidBills->map(fn ($bill) => "{$bill->bulan} {$bill->tahun}")->implode(', ');
                                $arrearsTotal = $unpaidBills->sum('nominal');
                                $arrearsMessage = "Assalamu'alaikum Bapak/Ibu {$item->nama_orang_tua}, kami dari TU mengingatkan bahwa ananda {$item->nama} memiliki tunggakan SPP {$unpaidBills->count()} bulan: {$arrearsMonths}. Total tunggakan Rp ".number_format($arrearsTotal, 0, ',', '.').'. Mohon segera melakukan pembayaran. Terima kasih.';
                            @endphp
                            <tr class="arrears-row {{ $unpaidBills->isNotEmpty() ? 'has-arrears' : 'clear-arrears' }}">
                                <td>
                                    <div class="arrears-student-cell">
                                        <span class="arrears-avatar">{{ strtoupper(mb_substr($item->nama, 0, 1)) }}</span>
                                        <div>
                                            <strong>{{ $item->nama }}</strong>
                                            <small>{{ $item->nis }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="arrears-class-chip">{{ $item->kelas->nama_kelas ?? '-' }}</span></td>
                                <td>
                                    @if($unpaidBills->isNotEmpty())
                                        <div class="arrears-bubbles">
                                            @foreach($unpaidBills as $bill)
                                                @php
                                                    $monthNumber = array_search($bill->bulan, $months, true) ?: $bill->bulan;
                                                    $code = $monthNumber.'-'.substr((string) $bill->tahun, -2);
                                                    $paidCount = $loop->iteration;
                                                    $paidTotal = $unpaidBills->take($paidCount)->sum('nominal');
                                                @endphp
                                                <button class="arrears-bubble" type="button" data-bs-toggle="modal" data-bs-target="#confirmBill{{ $bill->id }}">
                                                    {{ $code }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="arrears-clear-badge"><i class="bi bi-check2-circle"></i>Tidak ada tunggakan</span>
                                    @endif
                                    @if($paidBills->isNotEmpty())
                                        <div class="paid-bills mt-2">
                                            <div class="small text-muted mb-1">Sudah lunas, bisa dibuka ulang jika salah input:</div>
                                            <div class="arrears-bubbles">
                                                @foreach($paidBills as $paidBill)
                                                    @php
                                                        $paidMonthNumber = array_search($paidBill->bulan, $months, true) ?: $paidBill->bulan;
                                                        $paidCode = $paidMonthNumber.'-'.substr((string) $paidBill->tahun, -2);
                                                    @endphp
                                                    <form method="post" action="{{ route('admin.arrears.reopen-bill', [$item, $paidBill]) }}" class="d-inline" onsubmit="return confirm('Buka ulang tagihan {{ $paidBill->bulan }} {{ $paidBill->tahun }}? Transaksi manual terkait akan dibatalkan.')">
                                                        @csrf
                                                        <button class="arrears-bubble paid" type="submit">{{ $paidCode }}</button>
                                                    </form>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($unpaidBills->isNotEmpty())
                                        <div class="arrears-total-box">
                                            <span>{{ $unpaidBills->count() }} bulan</span>
                                            <strong>Rp {{ number_format($arrearsTotal, 0, ',', '.') }}</strong>
                                            <small>{{ $unpaidBills->first()->bulan }} {{ $unpaidBills->first()->tahun }} - {{ $unpaidBills->last()->bulan }} {{ $unpaidBills->last()->tahun }}</small>
                                        </div>
                                    @else
                                        <span class="text-muted small">Rp 0</span>
                                    @endif
                                </td>
                                <td>
                                    @if($lastPayment)
                                        <a href="{{ route('invoice.show', $lastPayment) }}" class="btn btn-sm btn-outline-primary arrears-invoice-link"><i class="bi bi-receipt me-1"></i>{{ $lastPayment->kode_invoice }}</a>
                                    @else
                                        <span class="text-muted small">Belum ada invoice</span>
                                    @endif
                                </td>
                                <td>
                                    @if($unpaidBills->isNotEmpty())
                                        <x-whatsapp-link :phone="$item->no_hp_orang_tua" :message="$arrearsMessage" label="WA Tunggakan" class="btn btn-sm btn-success" />
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr data-empty-row><td colspan="6" class="empty-state">Belum ada siswa.</td></tr>
                        @endforelse
                        <tr data-empty-row style="display:none"><td colspan="6" class="empty-state">Siswa tidak ditemukan.</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $siswa->links() }}</div>
        </div>
    </div>
</div>

@foreach($siswa as $modalStudent)
    @php
        $modalUnpaidBills = $modalStudent->tagihan
            ->whereNotIn('status', ['lunas', 'gratis'])
            ->sortBy(fn ($bill) => sprintf('%04d%02d', $bill->tahun, array_search($bill->bulan, $months, true) ?: 1))
            ->values();
    @endphp
    @foreach($modalUnpaidBills as $bill)
        @php
            $paidCount = $loop->iteration;
            $paidTotal = $modalUnpaidBills->take($paidCount)->sum('nominal');
        @endphp
        <div class="modal fade" id="confirmBill{{ $bill->id }}" tabindex="-1" aria-labelledby="confirmBillLabel{{ $bill->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header pb-0 border-bottom-0">
                        <h5 class="modal-title" id="confirmBillLabel{{ $bill->id }}">Metode Pembayaran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="px-3 pt-2">
                        <ul class="nav nav-tabs" id="paymentTab{{ $bill->id }}" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="cash-tab-{{ $bill->id }}" data-bs-toggle="tab" data-bs-target="#cash-pane-{{ $bill->id }}" type="button" role="tab" aria-controls="cash-pane-{{ $bill->id }}" aria-selected="true">
                                    <i class="bi bi-cash me-1"></i>Tunai / Manual
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="midtrans-tab-{{ $bill->id }}" data-bs-toggle="tab" data-bs-target="#midtrans-pane-{{ $bill->id }}" type="button" role="tab" aria-controls="midtrans-pane-{{ $bill->id }}" aria-selected="false">
                                    <i class="bi bi-credit-card me-1"></i>Midtrans Online
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content" id="paymentTabContent{{ $bill->id }}">
                        <!-- Tab 1: Manual / Cash -->
                        <div class="tab-pane fade show active" id="cash-pane-{{ $bill->id }}" role="tabpanel" aria-labelledby="cash-tab-{{ $bill->id }}">
                            <form method="post" action="{{ route('admin.arrears.confirm-through', [$modalStudent, $bill]) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="modal-body">
                                    <p class="mb-2">Konfirmasi bahwa pembayaran tunai dari <strong>{{ $modalStudent->nama }}</strong> sudah diterima sampai <strong>{{ $bill->bulan }} {{ $bill->tahun }}</strong>?</p>
                                    <div class="alert alert-warning small mb-3">
                                        Sistem akan melunasi <strong>{{ $paidCount }} bulan</strong> tunggakan senilai
                                        <strong>Rp {{ number_format($paidTotal, 0, ',', '.') }}</strong> secara manual.
                                    </div>
                                    <label class="form-label">Tanggal transaksi</label>
                                    <input name="paid_at" type="datetime-local" class="form-control mb-3" value="{{ now()->format('Y-m-d\TH:i') }}">
                                    <label class="form-label">Foto bukti pembayaran (opsional)</label>
                                    <input name="bukti" type="file" class="form-control" accept="image/*">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Oke, Konfirmasi Tunai</button>
                                </div>
                            </form>
                        </div>

                        <!-- Tab 2: Midtrans -->
                        <div class="tab-pane fade" id="midtrans-pane-{{ $bill->id }}" role="tabpanel" aria-labelledby="midtrans-tab-{{ $bill->id }}">
                            <form method="post" action="{{ route('admin.arrears.midtrans', [$modalStudent, $bill]) }}">
                                @csrf
                                <div class="modal-body">
                                    <p class="mb-2">Bayar tunggakan untuk <strong>{{ $modalStudent->nama }}</strong> sampai <strong>{{ $bill->bulan }} {{ $bill->tahun }}</strong> menggunakan Midtrans?</p>
                                    <div class="alert alert-info small mb-3">
                                        Total pembayaran <strong>{{ $paidCount }} bulan</strong> tunggakan: <strong class="fs-6 text-success">Rp {{ number_format($paidTotal, 0, ',', '.') }}</strong>.
                                    </div>
                                    <p class="text-muted small">Setelah menekan tombol di bawah, sistem akan membuat link pembayaran online Midtrans (QRIS, VA Bank, dll.) yang bisa langsung dibayar di tempat.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button class="btn btn-primary"><i class="bi bi-credit-card me-1"></i>Bayar di Tempat (Midtrans)</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endforeach

<style>
    .arrears-icon { width:42px; height:42px; display:grid; place-items:center; border-radius:8px; color:#fff; background:#162d78; box-shadow:0 12px 26px rgba(22,183,232,.32); flex:0 0 auto; }
    .arrears-form .form-label { font-size:.82rem; font-weight:720; color:#475467; }
    .arrears-search { width:min(280px,100%); }
    .arrears-bubbles { display:flex; flex-wrap:wrap; gap:.45rem; min-width:160px; }
    .arrears-bubble { border:1px solid #fecaca; color:#991b1b; background:#fee2e2; border-radius:999px; padding:.35rem .6rem; font-size:.82rem; font-weight:800; transition:transform .16s ease, box-shadow .16s ease; }
    .arrears-bubble:hover { transform:translateY(-2px); box-shadow:0 10px 22px rgba(153,27,27,.12); }
    .arrears-bubble.paid { border-color:#bbf7d0; color:#075f7f; background:#dcfce7; }
    .paid-bills { max-width:520px; }
    .arrears-total-box { display:grid; gap:.12rem; min-width:155px; padding:.55rem .65rem; border:1px solid #f2b8b5; border-radius:8px; background:#fff7f7; }
    .arrears-total-box span { color:#991b1b; font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; }
    .arrears-total-box strong { color:#7f1d1d; font-size:1rem; line-height:1.15; }
    .arrears-total-box small { color:#667085; line-height:1.3; }
    .arrears-summary-strip { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.65rem; }
    .arrears-summary-strip > div { display:grid; gap:.18rem; padding:.75rem .85rem; border:1px solid #d6edf6; border-radius:8px; background:#f8fbfc; }
    .arrears-summary-strip span { color:#667085; font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; }
    .arrears-summary-strip strong { color:#102027; font-size:1.05rem; line-height:1.2; overflow-wrap:anywhere; }
    .arrears-search-panel { display:grid; grid-template-columns:minmax(210px,1.5fr) minmax(140px,.8fr) minmax(160px,.8fr) auto; gap:.5rem; align-items:center; width:min(100%,720px); }
    .search-field { position:relative; }
    .search-field i { position:absolute; left:.7rem; top:50%; transform:translateY(-50%); color:#667085; pointer-events:none; }
    .search-field .form-control { padding-left:2rem; }
    @media (max-width: 992px) {
        .arrears-search-panel { grid-template-columns:1fr 1fr; width:100%; }
        .search-field { grid-column:1 / -1; }
        .arrears-summary-strip { grid-template-columns:1fr; }
    }
    @media (max-width: 576px) {
        .arrears-search-panel { grid-template-columns:1fr; }
    }

    .arrears-page-title {
        position:relative;
        overflow:hidden;
        align-items:center;
        padding:1.2rem;
        border-color:#d6edf6;
        background:
            linear-gradient(135deg,rgba(255,255,255,.97),rgba(238,253,255,.92)),
            radial-gradient(circle at top right,rgba(229,38,50,.12),transparent 34%);
        box-shadow:0 18px 44px rgba(16,24,40,.07);
    }
    .arrears-page-title::before {
        content:"";
        position:absolute;
        inset:0 auto 0 0;
        width:5px;
        background:linear-gradient(180deg,#e52632,#162d78);
    }
    .arrears-kicker {
        display:inline-flex;
        align-items:center;
        margin-bottom:.45rem;
        padding:.28rem .58rem;
        border:1px solid #f2b8b5;
        border-radius:999px;
        background:#fff7f7;
        color:#991b1b;
        font-size:.72rem;
        font-weight:820;
        text-transform:uppercase;
        letter-spacing:.04em;
    }
    .arrears-page-title h3 {
        font-size:1.45rem;
        font-weight:850;
        color:#102027;
    }
    .arrears-primary-action {
        min-width:180px;
        box-shadow:0 12px 28px rgba(22,45,120,.14);
    }
    .arrears-board {
        border-color:#d6edf6;
        background:rgba(255,255,255,.96);
        box-shadow:0 18px 44px rgba(16,24,40,.07);
    }
    .arrears-toolbar {
        display:flex;
        flex-wrap:wrap;
        justify-content:space-between;
        align-items:center;
        gap:1rem;
        padding-bottom:.85rem;
        border-bottom:1px solid #edf1f5;
    }
    .arrears-toolbar h6 {
        font-weight:840;
        color:#102027;
    }
    .arrears-search-panel {
        padding:.35rem;
        border:1px solid #d6edf6;
        border-radius:8px;
        background:#f8fbfc;
    }
    .arrears-search-panel .form-control,
    .arrears-search-panel .form-select {
        border-radius:999px;
        background-color:#fff;
    }
    .arrears-summary-strip {
        gap:1rem;
    }
    .arrears-summary-strip > div {
        position:relative;
        overflow:hidden;
        min-height:104px;
        padding:1rem;
        background:#fff;
        box-shadow:0 10px 28px rgba(16,24,40,.05);
    }
    .arrears-summary-strip > div::before {
        content:"";
        position:absolute;
        left:0;
        top:0;
        bottom:0;
        width:4px;
        background:#16b7e8;
    }
    .arrears-summary-strip i {
        width:38px;
        height:38px;
        display:grid;
        place-items:center;
        border-radius:8px;
        color:#fff;
        background:#162d78;
        margin-bottom:.55rem;
    }
    .arrears-summary-strip .summary-months i,
    .arrears-summary-strip .summary-months::before {
        background:#b45309;
    }
    .arrears-summary-strip .summary-total i,
    .arrears-summary-strip .summary-total::before {
        background:#e52632;
    }
    .arrears-summary-strip strong {
        font-size:clamp(1.12rem,2vw,1.45rem);
    }
    #arrearsTable thead th {
        background:#f8fafc;
        color:#475467;
        font-size:.72rem;
        letter-spacing:.04em;
    }
    .arrears-row {
        transition:background .16s ease, box-shadow .16s ease;
    }
    .arrears-row.has-arrears:hover {
        background:#fff7f7;
        box-shadow:inset 3px 0 0 #e52632;
    }
    .arrears-row.clear-arrears:hover {
        background:#f8fbfc;
        box-shadow:inset 3px 0 0 #16b7e8;
    }
    .arrears-student-cell {
        display:flex;
        align-items:center;
        gap:.7rem;
        min-width:220px;
    }
    .arrears-avatar {
        width:40px;
        height:40px;
        display:grid;
        place-items:center;
        flex:0 0 auto;
        border-radius:8px;
        color:#fff;
        background:linear-gradient(145deg,#e52632,#162d78);
        font-weight:850;
        box-shadow:0 8px 18px rgba(229,38,50,.14);
    }
    .arrears-student-cell strong {
        display:block;
        color:#102027;
        line-height:1.2;
    }
    .arrears-student-cell small {
        display:block;
        color:#667085;
        margin-top:.12rem;
    }
    .arrears-class-chip {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-width:58px;
        padding:.32rem .58rem;
        border:1px solid #c9edf8;
        border-radius:999px;
        color:#075f7f;
        background:#eef8fc;
        font-weight:780;
    }
    .arrears-bubble {
        border-radius:999px;
        box-shadow:none;
    }
    .arrears-bubble:not(.paid) {
        background:#fff1f2;
        border-color:#fecaca;
    }
    .arrears-bubble.paid {
        background:#eefdff;
        border-color:#bbf7d0;
    }
    .arrears-total-box {
        background:#fff7f7;
        box-shadow:0 8px 18px rgba(229,38,50,.05);
    }
    .arrears-clear-badge {
        display:inline-flex;
        align-items:center;
        gap:.35rem;
        padding:.38rem .62rem;
        border-radius:999px;
        color:#075f7f;
        background:#eefdff;
        border:1px solid #bbf7d0;
        font-size:.82rem;
        font-weight:780;
    }
    .arrears-invoice-link {
        max-width:190px;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }
    #setLastPaidModal .modal-content,
    [id^="confirmBill"] .modal-content {
        border:1px solid #d6edf6;
        box-shadow:0 24px 70px rgba(15,23,42,.18);
    }
    @media (max-width: 992px) {
        .arrears-page-title {
            display:grid;
        }
        .arrears-primary-action {
            width:100%;
        }
        .arrears-toolbar {
            display:grid;
        }
    }
</style>
@endsection
