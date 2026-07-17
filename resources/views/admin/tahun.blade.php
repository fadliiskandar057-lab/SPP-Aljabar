@extends('layouts.app')

@section('content')
<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-calendar3"></i>Master Periode</span>
            <h3>Tahun Ajaran</h3>
            <p>Atur periode akademik aktif untuk generate tagihan dan laporan pembayaran.</p>
        </div>
        <div class="role-hero-actions"><span class="role-icon-tile"><i class="bi bi-calendar-range"></i></span></div>
    </section>

    <section class="role-filter p-3">
        <form class="row g-3 align-items-end" method="post" action="{{ route('admin.tahun.store') }}">
            @csrf
            <div class="col-md-8">
                <label class="form-label">Nama Tahun Ajaran</label>
                <input name="nama" class="form-control" placeholder="Contoh: 2025/2026" required>
            </div>
            <div class="col-md-2">
                <div class="form-check role-check-box">
                    <input name="is_active" type="checkbox" class="form-check-input" id="active">
                    <label class="form-check-label" for="active">Jadikan aktif</label>
                </div>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-save"></i>Simpan</button></div>
        </form>
    </section>

    <section class="role-table-card">
        <div class="role-card-head">
            <div><h6>Daftar Tahun Ajaran</h6><span>{{ $tahun->count() }} periode terdata</span></div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="yearTable">
                <thead><tr><th>Nama</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($tahun as $item)
                        <tr>
                            <td><span class="role-chip"><i class="bi bi-calendar3"></i>{{ $item->nama }}</span></td>
                            <td><span class="badge {{ $item->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        </tr>
                    @empty
                        <tr data-empty-row><td colspan="2" class="empty-state">Belum ada tahun ajaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<style>
    .role-check-box { min-height:42px; display:flex; align-items:center; gap:.45rem; padding:.5rem .75rem; border:1px solid #d6dde6; border-radius:8px; background:#fff; }
</style>
@endsection
