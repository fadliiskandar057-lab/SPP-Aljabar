@extends('layouts.app')

@section('content')
<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-cash-coin"></i>Master Biaya</span>
            <h3>Biaya SPP</h3>
            <p>Nominal bisa dibuat umum untuk semua kelas atau spesifik per kelas pada tahun ajaran tertentu.</p>
        </div>
        <div class="role-hero-actions"><span class="role-icon-tile"><i class="bi bi-cash-stack"></i></span></div>
    </section>

    <section class="role-filter p-3">
        <form class="row g-3 align-items-end" method="post" action="{{ route('admin.biaya.store') }}">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Tahun Ajaran</label>
                <select name="tahun_ajaran_id" class="form-select">
                    @foreach($tahun as $t)<option value="{{ $t->id }}">{{ $t->nama }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Kelas</label>
                <select name="kelas_id" class="form-select">
                    <option value="">Semua Kelas</option>
                    @foreach($kelas as $k)<option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Nominal</label>
                <input name="nominal" type="number" class="form-control" placeholder="Contoh: 270000" required>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-save"></i>Simpan</button></div>
        </form>
    </section>

    <section class="role-table-card">
        <div class="role-card-head">
            <div><h6>Daftar Biaya</h6><span>{{ $biaya->count() }} nominal terdata</span></div>
            <div class="role-search"><i class="bi bi-search"></i><input class="form-control form-control-sm" placeholder="Cari biaya..." data-live-search="#feeTable"></div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="feeTable">
                <thead><tr><th>Tahun</th><th>Kelas</th><th>Nominal</th></tr></thead>
                <tbody>
                    @forelse($biaya as $item)
                        <tr>
                            <td>{{ $item->tahunAjaran->nama }}</td>
                            <td><span class="role-chip">{{ $item->kelas->nama_kelas ?? 'Semua Kelas' }}</span></td>
                            <td><span class="role-money">Rp {{ number_format($item->nominal,0,',','.') }}</span></td>
                        </tr>
                    @empty
                        <tr data-empty-row><td colspan="3" class="empty-state">Belum ada biaya SPP.</td></tr>
                    @endforelse
                    <tr data-empty-row style="display:none"><td colspan="3" class="empty-state">Biaya tidak ditemukan.</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
