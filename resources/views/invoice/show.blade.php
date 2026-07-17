@extends('layouts.app')
@section('content')
<div class="page-title"><div><h3>Invoice / Kwitansi</h3><p>Dokumen pembayaran digital untuk arsip siswa dan TU.</p></div></div>
<div class="content-card p-4">
    <div class="d-flex justify-content-between border-bottom pb-3 mb-3">
        <div><h5>MA Plus Taruna Teknik Al Jabbar</h5><div class="text-muted">Kwitansi Pembayaran SPP</div></div>
        <div class="text-end"><strong>{{ $payment->kode_invoice }}</strong><br><x-status :status="$payment->tagihan->status"/></div>
    </div>
    <dl class="row">
        <dt class="col-sm-3">Nama Siswa</dt><dd class="col-sm-9">{{ $payment->siswa->nama }}</dd>
        <dt class="col-sm-3">NIS / Kelas</dt><dd class="col-sm-9">{{ $payment->siswa->nis }} / {{ $payment->siswa->kelas->nama_kelas }}</dd>
        <dt class="col-sm-3">Bulan SPP</dt><dd class="col-sm-9">{{ $payment->tagihan->bulan }} {{ $payment->tagihan->tahun }}</dd>
        <dt class="col-sm-3">Nominal</dt><dd class="col-sm-9">Rp {{ number_format($payment->nominal,0,',','.') }}</dd>
        <dt class="col-sm-3">Metode</dt><dd class="col-sm-9">{{ strtoupper($payment->metode) }}</dd>
        <dt class="col-sm-3">Tanggal Lunas</dt><dd class="col-sm-9">{{ $payment->paid_at?->format('d/m/Y H:i') ?? 'Menunggu konfirmasi' }}</dd>
        @if($payment->bukti_path)
            <dt class="col-sm-3">Bukti Foto</dt><dd class="col-sm-9"><a href="{{ asset($payment->bukti_path) }}" target="_blank">Lihat bukti pembayaran</a></dd>
        @endif
    </dl>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-danger" href="{{ route('invoice.download',$payment) }}">Download PDF</a>
        @if(auth()->user()?->role === 'siswa' && $payment->status === 'pending')
            <form method="post" action="{{ route('siswa.payments.cancel', $payment) }}" onsubmit="return confirm('Batalkan pembayaran pending ini?')">
                @csrf
                <button class="btn btn-outline-danger"><i class="bi bi-x-circle"></i>Batalkan Pembayaran</button>
            </form>
        @endif
    </div>
</div>
@endsection
