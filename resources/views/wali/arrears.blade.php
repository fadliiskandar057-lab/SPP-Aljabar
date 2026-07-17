@extends('layouts.app')

@section('content')
<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-exclamation-circle"></i>Kelas {{ $kelas->nama_kelas ?? '-' }}</span>
            <h3>Tunggakan Kelas</h3>
            <p>Daftar siswa kelas {{ $kelas->nama_kelas ?? '-' }} yang masih memiliki tagihan aktif beserta total nominalnya.</p>
        </div>
        <div class="role-hero-actions"><span class="role-icon-tile"><i class="bi bi-exclamation-triangle"></i></span></div>
    </section>

<div class="role-table-card">
    <div class="role-card-head"><div><h6>Daftar Tunggakan</h6><span>{{ $students->count() }} siswa memiliki tunggakan.</span></div></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Siswa</th><th>Jumlah Bulan</th><th>Total Tunggakan</th><th>Rincian Bulan</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
                @forelse($students as $siswa)
                    @php
                        $arrearsMonths = $siswa->tagihan->map(fn ($bill) => "{$bill->bulan} {$bill->tahun}")->implode(', ');
                        $message = "Assalamu'alaikum Bapak/Ibu {$siswa->nama_orang_tua}, kami wali kelas {$kelas->nama_kelas} mengingatkan bahwa ananda {$siswa->nama} memiliki tunggakan SPP {$siswa->tagihan->count()} bulan: {$arrearsMonths}. Total tunggakan Rp ".number_format($siswa->arrears_total, 0, ',', '.').'. Mohon segera melakukan pembayaran. Terima kasih.';
                    @endphp
                    <tr>
                        <td><div class="role-student"><span class="role-avatar">{{ strtoupper(substr($siswa->nama,0,1)) }}</span><div><strong>{{ $siswa->nama }}</strong><span>{{ $siswa->nis }} - {{ $siswa->nama_orang_tua }}</span></div></div></td>
                        <td><span class="role-chip">{{ $siswa->tagihan->count() }} bulan</span></td>
                        <td><span class="role-money-danger">Rp {{ number_format($siswa->arrears_total, 0, ',', '.') }}</span></td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($siswa->tagihan as $bill)
                                    <span class="badge bg-light text-dark border">{{ $bill->bulan }} {{ $bill->tahun }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="text-end">
                            <x-whatsapp-link :phone="$siswa->no_hp_orang_tua" :message="$message" label="WA Tunggakan" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-state">Tidak ada tunggakan kelas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection
