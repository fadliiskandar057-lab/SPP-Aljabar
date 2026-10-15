@extends('layouts.app')

@section('content')
<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-building"></i>Master Data</span>
            <h3>Data Kelas</h3>
            <p>Master kelas untuk pengelompokan siswa dan biaya SPP.</p>
        </div>
        <div class="role-hero-actions"><a href="{{ route('admin.progression.index') }}" class="btn btn-primary">Kenaikan &amp; Kelulusan</a></div>
    </section>

    <section class="role-filter p-3">
        <form class="row g-3 align-items-end" method="post" action="{{ route('admin.kelas.store') }}">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Nama Kelas</label>
                <input name="nama_kelas" class="form-control" placeholder="Contoh: X-A" required>
            </div>
            <div class="col-md-2"><label for="newClassStage" class="form-label">Jenjang</label><select id="newClassStage" name="jenjang" class="form-select"><option value="">Belum diatur</option>@foreach(['SD', 'SMP', 'SMA', 'SMK'] as $stage)<option>{{ $stage }}</option>@endforeach</select></div>
            <div class="col-md-2"><label for="newClassLevel" class="form-label">Tingkat (1–12)</label><input id="newClassLevel" type="number" name="tingkat" min="1" max="12" class="form-control" placeholder="10"></div>
            <div class="col-md-2"><label for="newClassMajor" class="form-label">Jurusan</label><input id="newClassMajor" name="jurusan" maxlength="100" class="form-control" placeholder="Perhotelan"></div>
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
                <thead><tr><th>#</th><th>Nama Kelas / Pengaturan</th></tr></thead>
                <tbody>
                    @forelse($kelas as $item)
                        <tr><td>{{ $loop->iteration }}</td><td>
                            <form method="post" action="{{ route('admin.kelas.update', $item) }}" class="row g-2 align-items-end">
                                @csrf @method('put')
                                <div class="col-md-3"><label class="form-label small">Nama kelas</label><input name="nama_kelas" value="{{ $item->nama_kelas }}" class="form-control" required aria-label="Nama kelas {{ $item->nama_kelas }}"></div>
                                <div class="col-md-2"><label class="form-label small">Jenjang</label><select name="jenjang" class="form-select" aria-label="Jenjang {{ $item->nama_kelas }}"><option value="">Belum diatur</option>@foreach(['SD', 'SMP', 'SMA', 'SMK'] as $stage)<option @selected($item->jenjang === $stage)>{{ $stage }}</option>@endforeach</select></div>
                                <div class="col-md-2"><label class="form-label small">Tingkat</label><input name="tingkat" type="number" min="1" max="12" value="{{ $item->tingkat }}" class="form-control" aria-label="Tingkat {{ $item->nama_kelas }}"></div>
                                <div class="col-md-3"><label class="form-label small">Jurusan</label><input name="jurusan" value="{{ $item->jurusan }}" maxlength="100" class="form-control" aria-label="Jurusan {{ $item->nama_kelas }}"></div>
                                <div class="col-md-2"><button class="btn btn-outline-primary w-100" type="submit">Simpan</button></div>
                            </form>
                        </td></tr>
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
