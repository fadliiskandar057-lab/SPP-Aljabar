<section class="role-filter p-3 p-md-4" aria-labelledby="currentBillTitle">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div><span class="role-kicker"><i class="bi bi-calendar-event" aria-hidden="true"></i>Bulan Berjalan</span><h5 id="currentBillTitle" class="fw-bold mb-1">SPP {{ now()->locale('id')->translatedFormat('F Y') }}</h5>
            @if($currentBill)
                <p class="mb-1">Rp {{ number_format($currentBill->nominal, 0, ',', '.') }} · Jatuh tempo {{ $currentBill->jatuh_tempo->format('d/m/Y') }}</p><x-status :status="$currentBill->status"/>
            @elseif(auth()->user()->siswa?->status !== 'aktif')
                <p class="text-muted mb-0">Siswa nonaktif tidak mendapat tagihan baru.</p>
            @elseif($pendingCurrentPayment)
                <p class="text-muted mb-0">Bulan ini sudah termasuk dalam invoice pembayaran masa depan yang sedang menunggu konfirmasi.</p>
            @elseif(!$billingSchedule?->is_enabled)
                <p class="text-muted mb-0">Jadwal tagihan otomatis belum aktif. Hubungi TU jika tagihan bulan ini belum tersedia.</p>
            @elseif(now()->day < $billingSchedule->generate_day)
                <p class="text-muted mb-0">Tagihan dijadwalkan terbit tanggal {{ $billingSchedule->generate_day }} bulan ini.</p>
            @else
                <p class="text-muted mb-0">Tagihan bulan ini belum tersedia. Hubungi TU untuk memeriksa periode mulai dan biaya SPP.</p>
            @endif
        </div>
        @if($currentBill && in_array($currentBill->status, ['belum_lunas', 'gagal'], true))
            <div class="d-flex flex-wrap gap-2"><form method="post" action="{{ route('siswa.midtrans', $currentBill) }}">@csrf<button class="btn btn-primary" type="submit">Bayar Online</button></form><form method="post" action="{{ route('siswa.cash', $currentBill) }}">@csrf<button class="btn btn-outline-primary" type="submit">Bayar Tunai</button></form></div>
        @elseif($currentBill?->status === 'menunggu_konfirmasi')
            <a href="{{ route('siswa.riwayat') }}" class="btn btn-outline-primary">Lihat Pembayaran Pending</a>
        @elseif($pendingCurrentPayment)
            <a href="{{ route('siswa.riwayat') }}" class="btn btn-outline-primary">Lihat Pembayaran Pending</a>
        @endif
    </div>
    <p class="text-muted small mt-3 mb-0">Kalender sekolah: {{ now()->locale('id')->translatedFormat('d F Y') }}. Tagihan tahun sebelumnya tetap ditampilkan sebagai riwayat / tunggakan.</p>
</section>
