@extends('layouts.app')

@section('content')
<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-building"></i>Monitoring Kelas</span>
            <h3>Laporan Per Kelas</h3>
            <p>Rekap pemasukan, tunggakan, dan status tagihan setiap kelas untuk pemantauan kepala sekolah.</p>
        </div>
        <div class="role-hero-actions"><span class="role-icon-tile"><i class="bi bi-building"></i></span></div>
    </section>

    <div class="role-table-card">
        <div class="role-card-head">
            <div>
                <h6>Ringkasan Kelas</h6>
                <span>{{ $kelas->count() }} kelas terdata</span>
            </div>
            <div class="role-search">
                <i class="bi bi-search"></i>
                <input class="form-control form-control-sm" placeholder="Cari kelas..." data-live-search="#principalClassTable">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="principalClassTable">
                <thead><tr><th>Kelas</th><th>Jumlah Siswa</th><th>Tagihan Lunas</th><th>Belum Lunas</th><th>Pemasukan</th><th>Tunggakan</th></tr></thead>
                <tbody>
                @forelse($kelas as $row)
                    <tr>
                        <td><span class="role-chip"><i class="bi bi-building"></i>{{ $row['nama_kelas'] }}</span></td>
                        <td>{{ $row['jumlah_siswa'] }}</td>
                        <td>{{ $row['lunas'] }}</td>
                        <td>{{ $row['belum_lunas'] }}</td>
                        <td><span class="role-money">Rp {{ number_format($row['pemasukan'],0,',','.') }}</span></td>
                        <td><span class="role-money-danger">Rp {{ number_format($row['tunggakan'],0,',','.') }}</span></td>
                    </tr>
                @empty
                    <tr data-empty-row><td colspan="6" class="empty-state">Data kelas tidak tersedia.</td></tr>
                @endforelse
                <tr data-empty-row style="display:none"><td colspan="6" class="empty-state">Kelas tidak ditemukan.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
