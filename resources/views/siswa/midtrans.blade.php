@extends('layouts.app')
@section('content')
<h3>Pembayaran Online</h3>
<div class="content-card p-3">
    <p>Invoice {{ $payment->kode_invoice }} sebesar <strong>Rp {{ number_format($payment->nominal,0,',','.') }}</strong>.</p>
    <div class="alert alert-warning small">
        Invoice Midtrans berlaku {{ config('services.midtrans.pending_timeout_minutes', 30) }} menit sejak dibuat. Jika melewati batas waktu dan belum dibayar, invoice pending akan otomatis dihapus.
    </div>
    <button id="pay-button" class="btn btn-primary">Buka Midtrans Snap</button>
</div>
@push('scripts')
<script src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
<script>
async function finishPayment(result) {
    const response = await fetch('{{ route('siswa.midtrans.finish', $payment) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            transaction_status: result.transaction_status || 'settlement',
            transaction_id: result.transaction_id || null
        })
    });
    const data = await response.json();
    location.href = data.redirect || '{{ route('siswa.riwayat') }}';
}

document.getElementById('pay-button').onclick = function () {
    snap.pay('{{ $snapToken }}', {
        onSuccess: finishPayment,
        onPending: function () { location.href = '{{ route('siswa.riwayat') }}'; },
        onError: function () { alert('Pembayaran gagal'); }
    });
};
</script>
@endpush
@endsection
