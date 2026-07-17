@extends('layouts.app')

@section('content')
<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-credit-card"></i>Pembayaran Online</span>
            <h3>Bayar Arrears Midtrans</h3>
            <p>Selesaikan pembayaran online dengan Midtrans untuk tunggakan siswa.</p>
        </div>
        <div class="role-hero-actions">
            <span class="role-icon-tile"><i class="bi bi-wallet-fill"></i></span>
        </div>
    </section>

    <div class="content-card p-4 bg-white rounded-3 border shadow-sm" style="max-width: 600px; margin: 2rem auto 0;">
        <h5 class="fw-bold mb-3"><i class="bi bi-receipt me-2 text-primary"></i>Rincian Invoice</h5>
        <table class="table table-borderless table-sm mb-3">
            <tr>
                <td class="text-muted" style="width: 140px;">Siswa</td>
                <td>: <strong>{{ $pembayaran->siswa->nama }}</strong></td>
            </tr>
            <tr>
                <td class="text-muted">Kelas</td>
                <td>: {{ $pembayaran->siswa->kelas->nama_kelas ?? '-' }}</td>
            </tr>
            <tr>
                <td class="text-muted">Kode Invoice</td>
                <td>: <code>{{ $pembayaran->kode_invoice }}</code></td>
            </tr>
            <tr>
                <td class="text-muted">Bulan Tagihan</td>
                <td>: {{ $pembayaran->tagihan->bulan }} {{ $pembayaran->tagihan->tahun }} (dan bulan sebelumnya)</td>
            </tr>
            <tr>
                <td class="text-muted">Total Pembayaran</td>
                <td>: <strong class="fs-5 text-success">Rp {{ number_format($pembayaran->nominal, 0, ',', '.') }}</strong></td>
            </tr>
        </table>

        <div class="alert alert-warning small mb-4">
            <i class="bi bi-info-circle me-1"></i>
            Invoice Midtrans berlaku {{ config('services.midtrans.pending_timeout_minutes', 30) }} menit sejak dibuat. Jika melewati batas waktu dan belum dibayar, invoice pending akan otomatis dihapus.
        </div>

        <div class="d-grid gap-2">
            <button id="pay-button" class="btn btn-primary btn-lg"><i class="bi bi-qr-code-scan me-2"></i>Buka Midtrans Snap</button>
            <a href="{{ route('admin.arrears.students') }}" class="btn btn-outline-secondary">Kembali ke Tunggakan</a>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
<script>
async function finishPayment(result) {
    const response = await fetch('{{ route('admin.arrears.midtrans.finish', $pembayaran) }}', {
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
    location.href = data.redirect || '{{ route('admin.arrears.students') }}';
}

document.getElementById('pay-button').onclick = function () {
    snap.pay('{{ $snapToken }}', {
        onSuccess: finishPayment,
        onPending: function () { location.href = '{{ route('admin.arrears.students') }}'; },
        onError: function () { alert('Pembayaran gagal'); }
    });
};
</script>
@endpush
@endsection
