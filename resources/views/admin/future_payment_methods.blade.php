@extends('layouts.app')

@section('content')
<div class="card mx-auto" style="max-width:640px">
    <div class="card-body">
        <h3>Metode Pembayaran</h3>
        <p>SPP <strong>{{ $siswa->nama }}</strong> sampai {{ $tagihan->bulan }} {{ $tagihan->tahun }}.</p>
        <div class="alert alert-info">
            {{ $bills->count() }} bulan, total <strong>Rp {{ number_format($bills->sum('nominal'), 0, ',', '.') }}</strong>.
            Termasuk tunggakan sebelumnya yang belum lunas.
        </div>
        <ul>
            @foreach($bills as $bill)
                <li>{{ $bill->bulan }} {{ $bill->tahun }} — Rp {{ number_format($bill->nominal, 0, ',', '.') }}</li>
            @endforeach
        </ul>
        <form method="post" action="{{ route('treasury.arrears.confirm-through', [$siswa, $tagihan]) }}" enctype="multipart/form-data">
            @csrf
            <label for="futurePaidAt" class="form-label">Tanggal transaksi tunai</label>
            <input id="futurePaidAt" name="paid_at" type="datetime-local" class="form-control mb-3" value="{{ now()->format('Y-m-d\TH:i') }}">
            <label for="futureProof" class="form-label">Foto bukti pembayaran (opsional)</label>
            <input id="futureProof" name="bukti" type="file" class="form-control mb-3" accept="image/*">
            <p class="small text-muted">Konfirmasi tunai setelah uang pembayaran diterima.</p>
            <button class="btn btn-success w-100">Konfirmasi Bayar Tunai</button>
        </form>
        <form method="post" action="{{ route('treasury.arrears.midtrans', [$siswa, $tagihan]) }}" class="mt-3">
            @csrf
            <button class="btn btn-primary w-100">Bayar melalui Midtrans</button>
        </form>
        <a href="{{ route('treasury.arrears.students') }}" class="btn btn-outline-secondary mt-3">Kembali</a>
    </div>
</div>
@endsection
