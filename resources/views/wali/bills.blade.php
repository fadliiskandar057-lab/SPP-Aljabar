@extends('layouts.app')

@section('content')
<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-receipt"></i>Kelas {{ $kelas->nama_kelas ?? '-' }}</span>
            <h3>Tagihan Kelas</h3>
            <p>Semua tagihan siswa kelas {{ $kelas->nama_kelas ?? '-' }} dengan status, jatuh tempo, dan aksi WhatsApp tunggakan.</p>
        </div>
        <div class="role-hero-actions"><span class="role-icon-tile"><i class="bi bi-receipt-cutoff"></i></span></div>
    </section>

<div class="role-filter p-3">
    <form class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label">Bulan</label><input name="bulan" value="{{ request('bulan') }}" class="form-control" placeholder="Contoh: Juni"></div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">Semua</option>
                @foreach(['belum_lunas' => 'Belum Lunas', 'menunggu_konfirmasi' => 'Menunggu', 'lunas' => 'Lunas', 'gratis' => 'Gratis', 'gagal' => 'Gagal'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4"><label class="form-label">Nama / NIS</label><input name="keyword" value="{{ request('keyword') }}" class="form-control" placeholder="Kata kunci"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-funnel"></i>Filter</button></div>
    </form>
</div>

<div class="role-table-card">
    <div class="role-card-head"><div><h6>Daftar Tagihan</h6><span>Hasil filter tagihan kelas.</span></div></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Siswa</th><th>Bulan</th><th>Nominal</th><th>Jatuh Tempo</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
                @forelse($bills as $bill)
                    @php
                        $studentArrears = $bill->siswa->tagihan;
                        $arrearsMonths = $studentArrears->map(fn ($item) => "{$item->bulan} {$item->tahun}")->implode(', ');
                        $arrearsTotal = $studentArrears->sum('nominal');
                        $message = "Assalamu'alaikum Bapak/Ibu {$bill->siswa->nama_orang_tua}, kami wali kelas {$kelas->nama_kelas} mengingatkan bahwa ananda {$bill->siswa->nama} memiliki tunggakan SPP {$studentArrears->count()} bulan: {$arrearsMonths}. Total tunggakan Rp ".number_format($arrearsTotal, 0, ',', '.').'. Mohon segera melakukan pembayaran. Terima kasih.';
                    @endphp
                    <tr>
                        <td><div class="role-student"><span class="role-avatar">{{ strtoupper(substr($bill->siswa->nama,0,1)) }}</span><div><strong>{{ $bill->siswa->nama }}</strong><span>{{ $bill->siswa->nis }}</span></div></div></td>
                        <td>{{ $bill->bulan }} {{ $bill->tahun }}</td>
                        <td><span class="role-money">Rp {{ number_format($bill->nominal, 0, ',', '.') }}</span></td>
                        <td>{{ $bill->jatuh_tempo?->format('d/m/Y') }}</td>
                        <td><x-status :status="$bill->status"/></td>
                        <td class="text-end">
                            @if($studentArrears->isNotEmpty())
                                <x-whatsapp-link :phone="$bill->siswa->no_hp_orang_tua" :message="$message" label="WA Tunggakan" />
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state">Tagihan tidak ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection
