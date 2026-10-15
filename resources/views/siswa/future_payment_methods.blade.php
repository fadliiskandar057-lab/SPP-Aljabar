@extends('layouts.app')

@section('content')
<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-calendar-check" aria-hidden="true"></i>Bayar Masa Depan</span>
            <h3>Pilih Metode Pembayaran</h3>
            <p>SPP sampai {{ \Carbon\Carbon::createFromFormat('!Y-m', $plan['until'])->locale('id')->translatedFormat('F Y') }}. Termasuk periode sebelumnya yang belum lunas.</p>
        </div>
        <a href="{{ route('siswa.tagihan') }}#bayar-masa-depan" class="btn btn-outline-primary">Ubah Periode</a>
    </section>
    <section class="role-table-card">
        <div class="role-card-head">
            <div><h6>Ringkasan Pembayaran</h6><span>{{ count($plan['items']) }} bulan dalam satu pembayaran</span></div>
            <strong class="fs-4">Rp {{ number_format($plan['total'], 0, ',', '.') }}</strong>
        </div>
        <div class="p-3 p-md-4">
            <p class="text-muted">Belum ada tagihan baru yang disimpan. Jika tidak jadi membayar, kamu bisa kembali tanpa menambahkan tagihan masa depan.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <form method="post" action="{{ route('siswa.future-payment.start') }}" class="border rounded p-3 h-100">
                        @csrf
                        <input type="hidden" name="until" value="{{ $plan['until'] }}">
                        <input type="hidden" name="method" value="tunai">
                        <p class="fw-bold mb-1"><i class="bi bi-wallet2" aria-hidden="true"></i> Tunai ke TU</p>
                        <p class="small text-muted">Buat satu invoice untuk seluruh periode. Tagihan dicatat setelah bendahara menerima uang dan mengonfirmasi.</p>
                        <button class="btn btn-warning w-100" type="submit">Bayar Tunai</button>
                    </form>
                </div>
                <div class="col-md-6">
                    <form method="post" action="{{ route('siswa.future-payment.start') }}" class="border rounded p-3 h-100">
                        @csrf
                        <input type="hidden" name="until" value="{{ $plan['until'] }}">
                        <input type="hidden" name="method" value="midtrans">
                        <p class="fw-bold mb-1"><i class="bi bi-credit-card" aria-hidden="true"></i> Online melalui Midtrans</p>
                        <p class="small text-muted">Bayar total periode pilihan melalui Midtrans. Tagihan dicatat setelah pembayaran berhasil diverifikasi.</p>
                        <button class="btn btn-primary w-100" type="submit">Bayar Online</button>
                    </form>
                </div>
            </div>
            <details class="mt-4">
                <summary class="fw-semibold">Lihat rincian {{ count($plan['items']) }} bulan</summary>
                <div class="table-responsive mt-2">
                    <table class="table align-middle mb-0">
                        <thead><tr><th scope="col">Periode</th><th scope="col" class="text-end">Nominal</th></tr></thead>
                        <tbody>
                            @foreach($plan['items'] as $item)
                                <tr><td>{{ $item['bulan'] }} {{ $item['tahun'] }}</td><td class="text-end">Rp {{ number_format($item['nominal'], 0, ',', '.') }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
            <a href="{{ route('siswa.tagihan') }}" class="btn btn-outline-secondary mt-3">Batal / Kembali ke Tagihan</a>
        </div>
    </section>
</div>
@endsection
