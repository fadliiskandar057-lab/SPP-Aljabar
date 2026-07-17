@extends('layouts.app')

@section('content')
<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-building"></i>Master Data</span>
            <h3>Data Kelas</h3>
            <p>Master kelas untuk pengelompokan siswa dan biaya SPP.</p>
        </div>
        <div class="role-hero-actions"><span class="role-icon-tile"><i class="bi bi-building"></i></span></div>
    </section>

    <section class="role-filter p-3">
        <form class="row g-3 align-items-end" method="post" action="{{ route('admin.kelas.store') }}">
            @csrf
            <div class="col-md-10">
                <label class="form-label">Nama Kelas</label>
                <input name="nama_kelas" class="form-control" placeholder="Contoh: X-A" required>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-save"></i>Simpan</button></div>
        </form>
    </section>

    <section class="role-table-card">
        <div class="role-card-head">
            <div><h6>Daftar Kelas</h6><span>{{ $kelas->count() }} kelas terdata</span></div>
            <div class="role-search"><i class="bi bi-search"></i><input class="form-control form-control-sm" placeholder="Cari kelas..." data-live-search="#classTable"></div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="classTable">
                <thead><tr><th>#</th><th>Nama Kelas</th></tr></thead>
                <tbody>
                    @forelse($kelas as $item)
                        <tr><td>{{ $loop->iteration }}</td><td><span class="role-chip"><i class="bi bi-building"></i>{{ $item->nama_kelas }}</span></td></tr>
                    @empty
                        <tr data-empty-row><td colspan="2" class="empty-state">Belum ada kelas.</td></tr>
                    @endforelse
                    <tr data-empty-row style="display:none"><td colspan="2" class="empty-state">Kelas tidak ditemukan.</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
