@extends('layouts.app')

@section('content')
<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-people"></i>Wali Kelas {{ $kelas->nama_kelas ?? '-' }}</span>
            <h3>Data Siswa Kelas</h3>
            <p>Daftar siswa kelas {{ $kelas->nama_kelas ?? '-' }}. Halaman ini hanya baca data.</p>
        </div>
        <div class="role-hero-actions"><span class="role-icon-tile"><i class="bi bi-people"></i></span></div>
    </section>

<div class="role-filter p-3">
    <form class="row g-2 align-items-end">
        <div class="col-md-10">
            <label class="form-label">Cari Siswa</label>
            <input name="keyword" value="{{ request('keyword') }}" class="form-control" placeholder="Nama, NIS, orang tua, atau nomor HP">
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-search"></i>Cari</button></div>
    </form>
</div>

<div class="role-table-card">
    <div class="role-card-head">
        <div><h6>Daftar Siswa</h6><span>{{ $students->count() }} data tampil</span></div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="waliStudentsTable">
            <thead><tr><th>NIS</th><th>Nama</th><th>Orang Tua</th><th>No HP</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($students as $siswa)
                    <tr>
                        <td>{{ $siswa->nis }}</td>
                        <td><div class="role-student"><span class="role-avatar">{{ strtoupper(substr($siswa->nama,0,1)) }}</span><div><strong>{{ $siswa->nama }}</strong><span>{{ $siswa->kelas->nama_kelas ?? '-' }}</span></div></div></td>
                        <td>{{ $siswa->nama_orang_tua }}</td>
                        <td>{{ $siswa->no_hp_orang_tua }}</td>
                        <td><x-status :status="$siswa->status"/></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-state">Data siswa tidak ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection
