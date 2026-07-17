@extends('layouts.app')

@section('content')
<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-whatsapp"></i>Kelas {{ $kelas->nama_kelas ?? '-' }}</span>
            <h3>Kontak Orang Tua</h3>
            <p>Kontak wali siswa kelas {{ $kelas->nama_kelas ?? '-' }} untuk follow-up pembayaran dan tunggakan.</p>
        </div>
        <div class="role-hero-actions"><span class="role-icon-tile"><i class="bi bi-whatsapp"></i></span></div>
    </section>

<div class="role-table-card">
    <div class="role-card-head"><div><h6>Daftar Kontak</h6><span>{{ $students->count() }} kontak siswa.</span></div></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Siswa</th><th>Orang Tua</th><th>No HP</th><th>Tunggakan</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
                @forelse($students as $siswa)
                    @php
                        $arrears = $siswa->tagihan->sum('nominal');
                        $arrearsMonths = $siswa->tagihan->map(fn ($bill) => "{$bill->bulan} {$bill->tahun}")->implode(', ');
                        $message = "Assalamu'alaikum Bapak/Ibu {$siswa->nama_orang_tua}, kami wali kelas {$kelas->nama_kelas} mengingatkan bahwa ananda {$siswa->nama} memiliki tunggakan SPP {$siswa->tagihan->count()} bulan: {$arrearsMonths}. Total tunggakan Rp ".number_format($arrears, 0, ',', '.').'. Mohon segera melakukan pembayaran. Terima kasih.';
                    @endphp
                    <tr>
                        <td><div class="role-student"><span class="role-avatar">{{ strtoupper(substr($siswa->nama,0,1)) }}</span><div><strong>{{ $siswa->nama }}</strong><span>{{ $siswa->nis }}</span></div></div></td>
                        <td>{{ $siswa->nama_orang_tua }}</td>
                        <td><span class="role-chip"><i class="bi bi-telephone"></i>{{ $siswa->no_hp_orang_tua }}</span></td>
                        <td><span class="{{ $arrears > 0 ? 'role-money-danger' : 'role-money' }}">Rp {{ number_format($arrears, 0, ',', '.') }}</span></td>
                        <td class="text-end">
                            @if($siswa->tagihan->isNotEmpty())
                                <x-whatsapp-link :phone="$siswa->no_hp_orang_tua" :message="$message" label="WA Tunggakan" />
                            @else
                                <span class="text-muted small">Tidak ada tunggakan</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-state">Belum ada siswa.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection
