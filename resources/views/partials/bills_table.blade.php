<div class="table-responsive">
<table class="table table-hover align-middle mb-0" id="{{ $tableId ?? 'billsTable' }}">
    <thead><tr><th>Bulan</th><th>Tahun</th><th>Nominal</th><th>Jatuh Tempo</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    @forelse($tagihan as $bill)
        <tr>
            <td><strong>{{ $bill->bulan }}</strong></td><td>{{ $bill->tahun }}</td><td class="fw-bold text-success">Rp {{ number_format($bill->nominal,0,',','.') }}</td><td>{{ $bill->jatuh_tempo?->format('d/m/Y') }}</td><td><x-status :status="$bill->status"/></td>
            <td>
                <div class="d-flex flex-wrap gap-1">
                @if(auth()->user()->role === 'siswa' && in_array($bill->status, ['belum_lunas', 'gagal'], true))
                    <form method="post" action="{{ route('siswa.midtrans',$bill) }}">@csrf<button class="btn btn-sm btn-primary"><i class="bi bi-credit-card"></i>Online</button></form>
                    <form method="post" action="{{ route('siswa.cash',$bill) }}">@csrf<button class="btn btn-sm btn-warning"><i class="bi bi-wallet2"></i>Tunai</button></form>
                @elseif(auth()->user()->role === 'siswa' && $bill->status === 'menunggu_konfirmasi')
                    @php
                        $pendingPayment = $bill->pembayaran->firstWhere('status', 'pending');
                    @endphp
                    <span class="text-muted small">Menunggu konfirmasi</span>
                    @if($pendingPayment)
                        <form method="post" action="{{ route('siswa.payments.cancel', $pendingPayment) }}" onsubmit="return confirm('Batalkan pembayaran pending ini?')">
                            @csrf
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i>Batalkan</button>
                        </form>
                    @endif
                @elseif(auth()->user()->role === 'siswa' && $bill->status === 'lunas')
                    <span class="text-muted small">Selesai</span>
                @endif
                </div>
            </td>
        </tr>
    @empty
        <tr data-empty-row><td colspan="6" class="empty-state">Belum ada tagihan.</td></tr>
    @endforelse
        <tr data-empty-row style="display:none"><td colspan="6" class="empty-state">Tidak ada tagihan yang cocok dengan pencarian.</td></tr>
    </tbody>
</table>
</div>
