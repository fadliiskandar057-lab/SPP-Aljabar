@forelse($payments as $payment)
    @php
        $student = $payment->siswa;
        $bill = $payment->tagihan;
        $waMessage = $student && $bill
            ? "Assalamu'alaikum Bapak/Ibu {$student->nama_orang_tua}, kami dari TU MA Taruna Teknik Al Jabbar menginformasikan pembayaran SPP ananda {$student->nama} untuk {$bill->bulan} {$bill->tahun} dengan invoice {$payment->kode_invoice}. Terima kasih."
            : '';
    @endphp
    <tr>
        <td><span class="fw-bold">{{ $payment->kode_invoice }}</span></td>
        <td>
            <div>{{ $student->nama ?? '-' }}</div>
            @if($student)
                <div class="small text-muted">{{ $student->no_hp_orang_tua ?: 'No WA belum ada' }}</div>
            @endif
        </td>
        <td>{{ $payment->tagihan->bulan ?? '-' }}</td>
        <td>{{ $payment->paid_at?->format('d/m/Y H:i') ?? '-' }}</td>
        <td><span class="badge bg-light text-dark border">{{ $payment->metode }}</span></td>
        <td class="fw-bold text-success">Rp {{ number_format($payment->nominal,0,',','.') }}</td>
        <td><x-status :status="$payment->status"/></td>
        <td class="text-nowrap">
            @if($student)
                <x-whatsapp-link :phone="$student->no_hp_orang_tua" :message="$waMessage" label="WA" class="btn btn-sm btn-success" />
            @endif
            @if(in_array($payment->status, ['settlement', 'success'], true) || $payment->metode === 'tunai')
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('invoice.show',$payment) }}"><i class="bi bi-receipt"></i>Kwitansi</a>
            @else
                <span class="text-muted small">Belum tersedia</span>
            @endif
            @if(auth()->user()?->role === 'admin_tu' && $payment->metode === 'manual' && $payment->status === 'success')
                <form method="post" action="{{ route('admin.payments.cancel', $payment) }}" class="d-inline" onsubmit="return confirm('Batalkan transaksi manual ini? Tagihan akan dibuka ulang jika tidak ada pembayaran sukses lain.')">
                    @csrf
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i>Batalkan</button>
                </form>
            @endif
            @if(auth()->user()?->role === 'siswa' && $payment->status === 'pending')
                <form method="post" action="{{ route('siswa.payments.cancel', $payment) }}" class="d-inline" onsubmit="return confirm('Batalkan pembayaran pending ini?')">
                    @csrf
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i>Batalkan</button>
                </form>
            @endif
        </td>
    </tr>
@empty
    <tr><td colspan="8" class="empty-state">Tidak ada transaksi yang cocok dengan pencarian.</td></tr>
@endforelse
