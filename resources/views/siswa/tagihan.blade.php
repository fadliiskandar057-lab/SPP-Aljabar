@extends('layouts.app')

@section('content')
<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-receipt"></i>Portal Siswa</span>
            <h3>Tagihan Saya</h3>
            <p>Daftar tagihan per bulan. Pembayaran bisa online melalui Midtrans atau tunai ke TU.</p>
        </div>
        <div class="role-hero-actions"><span class="role-icon-tile"><i class="bi bi-receipt-cutoff"></i></span></div>
    </section>

    @include('siswa.current_bill')
    @include('partials.spp_coverage')
    @if(auth()->user()->siswa?->status === 'aktif')
    <section class="role-table-card" id="bayar-masa-depan" aria-labelledby="futurePaymentTitle">
        <div class="role-card-head">
            <div>
                <span class="role-kicker"><i class="bi bi-calendar-plus" aria-hidden="true"></i>Rencanakan Pembayaran</span>
                <h6 id="futurePaymentTitle">Bayar Masa Depan</h6>
                <span>Siapkan SPP untuk beberapa bulan atau beberapa tahun sekaligus.</span>
            </div>
            <span class="role-icon-tile" aria-hidden="true"><i class="bi bi-calendar-check"></i></span>
        </div>
        <div class="p-3 p-md-4">
            <div class="row g-4 align-items-center">
                <div class="col-md-6">
                    <p class="mb-2 fw-semibold">Pilih sampai bulan apa ingin membayar</p>
                    <p class="text-muted small mb-0" id="futurePaymentHelp">Lihat total SPP sampai bulan pilihan, termasuk tunggakan sebelumnya. Berikutnya, pilih pembayaran Tunai atau Online untuk seluruh periode. Tagihan masa depan baru dicatat setelah pembayaran berhasil atau dikonfirmasi TU.</p>
                </div>
                <div class="col-md-6">
                    <form method="post" action="{{ route('siswa.future-payment') }}">
                        @csrf
                        <label for="futurePaymentUntil" class="form-label fw-semibold">Periode akhir pembayaran</label>
                        <div class="d-flex flex-wrap gap-2">
                            <input type="month" id="futurePaymentUntil" name="until" class="form-control w-auto flex-grow-1 @error('until') is-invalid @enderror" min="{{ now()->format('Y-m') }}" max="{{ now()->startOfMonth()->addYears(100)->format('Y-m') }}" value="{{ old('until', now()->addMonthNoOverflow()->format('Y-m')) }}" aria-describedby="futurePaymentHelp @error('until') futurePaymentError @enderror" required>
                            <button class="btn btn-primary" type="submit"><i class="bi bi-arrow-right-circle" aria-hidden="true"></i>Lihat Pilihan Pembayaran</button>
                        </div>
                        @error('until')<p class="text-danger small mt-2 mb-0" id="futurePaymentError" role="alert">{{ $message }}</p>@enderror
                        <p class="text-muted small mt-2 mb-0"><i class="bi bi-info-circle" aria-hidden="true"></i>Melihat pilihan pembayaran tidak membuat tagihan baru.</p>
                    </form>
                </div>
            </div>
        </div>
    </section>
    @else
        <p class="alert alert-info">Status siswa {{ auth()->user()->siswa?->status }}. Tidak ada tagihan baru; kamu tetap dapat menyelesaikan tagihan lama dan melihat riwayat.</p>
    @endif
    <div class="role-table-card">
        <div class="role-card-head">
        <div>
            <h6>Daftar Tagihan</h6>
            <span id="studentBillsCount" class="live-search-count"></span>
        </div>
        <div class="role-search">
            <i class="bi bi-search"></i>
            <input class="form-control form-control-sm" placeholder="Cari bulan, tahun, nominal, status..." data-live-search="#studentBillsTable" data-live-count="#studentBillsCount">
        </div>
        </div>
        <div class="p-0">
            @include('partials.bills_table', ['tagihan' => $tagihan, 'tableId' => 'studentBillsTable'])
        </div>
        <div class="p-3">{{ $tagihan->links() }}</div>
    </div>
</div>
@endsection
