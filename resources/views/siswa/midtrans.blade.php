@extends('layouts.app')
@section('content')
<h3>Pembayaran Online</h3>
<div class="content-card p-3">
    <p>Invoice {{ $payment->kode_invoice }} sebesar <strong>Rp {{ number_format($payment->nominal,0,',','.') }}</strong>.</p>
    <div class="alert alert-warning small">
        Invoice Midtrans berlaku {{ config('services.midtrans.pending_timeout_minutes', 30) }} menit sejak dibuat. Jika melewati batas waktu dan belum dibayar, invoice pending akan otomatis dihapus.
    </div>
    <button id="pay-button" class="btn btn-primary">Buka Midtrans Snap</button>
    @if($payment->future_plan)
        <p class="small text-muted mt-3">Tagihan masa depan baru dicatat setelah pembayaran berhasil.</p>
        <form method="post" action="{{ route('siswa.payments.cancel', $payment) }}">@csrf<button class="btn btn-outline-danger" type="submit">Batal Bayar</button></form>
    @endif
</div>
@push('scripts')
<script src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
@include('partials.midtrans_finish_script', ['finishUrl' => route('siswa.midtrans.finish', $payment), 'snapToken' => $snapToken])
@endpush
@endsection
