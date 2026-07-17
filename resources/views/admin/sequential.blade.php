@extends('layouts.app')

@section('content')
<div class="seq-hero page-title">
    <div>
        <span class="seq-kicker">Sequential Lab</span>
        <h3>Pencarian Sequential</h3>
        <p>Halaman khusus untuk melihat proses pencarian berurutan pada data siswa dan pembayaran.</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="content-card p-3 h-100 seq-explain">
            <div class="d-flex align-items-start gap-3 mb-3">
                <div class="sequential-icon"><i class="bi bi-list-ol"></i></div>
                <div>
                    <h6 class="mb-1">Cara Kerja</h6>
                    <p class="text-muted mb-0 small">Sequential Search memeriksa data satu per satu dari awal sampai menemukan data yang cocok. Angka "Data diperiksa" menunjukkan jumlah data yang dilewati proses pencarian.</p>
                </div>
            </div>
            <div class="sequential-flow">
                <span>Mulai</span>
                <i class="bi bi-arrow-right"></i>
                <span>Cek data 1</span>
                <i class="bi bi-arrow-right"></i>
                <span>Cek data berikutnya</span>
                <i class="bi bi-arrow-right"></i>
                <span>Hasil</span>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="content-card p-3 h-100 seq-card">
            <h6 class="mb-2">Pencarian Siswa</h6>
            <div class="seq-search mb-2"><i class="bi bi-search"></i><input class="form-control" placeholder="Cari NIS, nama, email, atau kelas..." data-sequential-url="{{ route('admin.sequential.siswa.search') }}" data-results-target="#sequentialStudentRows" data-meta-target="#sequentialStudentMeta"></div>
            <div id="sequentialStudentMeta" class="small text-muted mb-2">Data diperiksa: {{ $studentMeta['checked'] }} | Hasil: {{ $students->count() }} | Waktu: {{ $studentMeta['duration_ms'] }} ms</div>
            <div class="table-responsive sequential-table">
                <table class="table table-sm align-middle">
                    <thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Status</th></tr></thead>
                    <tbody id="sequentialStudentRows">
                        @include('admin.partials.sequential_student_rows', ['students' => $students])
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="content-card p-3 seq-card">
            <h6 class="mb-2">Pencarian Pembayaran</h6>
            <div class="row g-2 align-items-center mb-2">
                <div class="col-md-8"><div class="seq-search"><i class="bi bi-search"></i><input class="form-control" placeholder="Cari invoice, NIS, nama, bulan, metode, atau status..." data-sequential-url="{{ route('admin.payments.search') }}" data-results-target="#sequentialPaymentRows" data-meta-target="#sequentialPaymentMeta" data-include-cancelled-target="#sequentialShowCancelled"></div></div>
                <div class="col-md-4 form-check d-flex align-items-center gap-2 ps-4 seq-check"><input id="sequentialShowCancelled" class="form-check-input" type="checkbox"><label class="form-check-label">Tampilkan cancelled</label></div>
            </div>
            <div id="sequentialPaymentMeta" class="small text-muted mb-2">Data diperiksa: {{ $paymentMeta['checked'] }} | Hasil: {{ $payments->count() }} | Waktu: {{ $paymentMeta['duration_ms'] }} ms</div>
            @include('partials.payments_table', ['payments' => $payments, 'tableId' => 'sequentialPaymentsTable', 'tbodyId' => 'sequentialPaymentRows'])
        </div>
    </div>
</div>

<style>
    .sequential-icon { width:42px; height:42px; border-radius:8px; display:grid; place-items:center; color:#fff; background:#162d78; box-shadow:0 12px 26px rgba(22,183,232,.32); flex:0 0 auto; }
    .sequential-flow { display:flex; flex-wrap:wrap; align-items:center; gap:.55rem; padding:.75rem; border:1px solid rgba(214,221,230,.88); border-radius:8px; background:#f8fafc; }
    .sequential-flow span { display:inline-flex; padding:.38rem .58rem; border-radius:999px; background:#fff; border:1px solid #e6eaf0; color:#344054; font-size:.82rem; font-weight:700; }
    .sequential-flow i { color:#162d78; }
    .sequential-table { max-height:340px; overflow:auto; }
    .seq-hero { position:relative; overflow:hidden; padding:1.2rem; border-color:#d6edf6; background:linear-gradient(135deg,#fff,#eef8fc); box-shadow:0 18px 44px rgba(16,24,40,.07); }
    .seq-hero::before { content:""; position:absolute; inset:0 auto 0 0; width:5px; background:linear-gradient(180deg,#16b7e8,#162d78); }
    .seq-kicker { display:inline-flex; margin-bottom:.45rem; padding:.28rem .58rem; border:1px solid #c9edf8; border-radius:999px; background:#fff; color:#075f7f; font-size:.72rem; font-weight:820; text-transform:uppercase; letter-spacing:.04em; }
    .seq-hero h3 { font-weight:850; }
    .seq-explain,.seq-card { border-color:#d6edf6; box-shadow:0 18px 44px rgba(16,24,40,.07); }
    .seq-explain { background:linear-gradient(180deg,#fff,#f8fbfc); }
    .seq-search { position:relative; }
    .seq-search i { position:absolute; left:.82rem; top:50%; transform:translateY(-50%); color:#667085; z-index:1; }
    .seq-search .form-control { padding-left:2.35rem; border-radius:999px; background:#fff; }
    .seq-check { min-height:42px; border:1px solid #e6eaf0; border-radius:999px; background:#f8fafc; }
    .sequential-flow { border-color:#d6edf6; background:#eef8fc; }
    .sequential-flow span { border-color:#c9edf8; color:#0e205a; }
</style>
@endsection
