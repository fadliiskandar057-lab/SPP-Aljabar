@extends('layouts.app')

@section('content')
@php
    $activeYear = $tahun->firstWhere('is_active', true);
    $defaultFee = $biaya->first(fn ($item) => is_null($item->kelas_id));
    $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
@endphp

<style>
    .spp-header { display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; margin-bottom:1rem; }
    .spp-header h3 { margin:0; font-weight:780; }
    .spp-header p { margin:.28rem 0 0; color:var(--muted); max-width:660px; }
    .spp-summary { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.85rem; margin-bottom:1rem; }
    .spp-stat { display:flex; align-items:center; gap:.8rem; padding:1rem; background:rgba(255,255,255,.92); border:1px solid rgba(230,234,240,.92); border-radius:8px; box-shadow:0 8px 22px rgba(16,24,40,.04); min-height:86px; }
    .spp-stat-icon { width:42px; height:42px; border-radius:8px; display:grid; place-items:center; color:#fff; flex:0 0 auto; background:#162d78; }
    .spp-stat:nth-child(2) .spp-stat-icon { background:#16b7e8; }
    .spp-stat:nth-child(3) .spp-stat-icon { background:#b45309; }
    .spp-stat:nth-child(4) .spp-stat-icon { background:#475467; }
    .spp-stat span { display:block; color:var(--muted); font-size:.78rem; font-weight:700; }
    .spp-stat strong { display:block; margin-top:.2rem; font-size:1.18rem; line-height:1.1; }
    .spp-tabs { border:1px solid rgba(230,234,240,.92); background:rgba(255,255,255,.82); border-radius:8px; padding:.35rem; gap:.35rem; margin-bottom:1rem; box-shadow:0 8px 22px rgba(16,24,40,.04); }
    .spp-tabs .nav-link { border:0; border-radius:6px; color:#475467; font-weight:740; display:flex; align-items:center; gap:.45rem; padding:.62rem .8rem; }
    .spp-tabs .nav-link.active { background:#162d78; color:#fff; box-shadow:0 10px 22px rgba(22,45,120,.18); }
    .spp-panel-grid { display:grid; grid-template-columns:minmax(260px,360px) minmax(0,1fr); gap:1rem; align-items:start; }
    .spp-form-card,.spp-list-card { padding:1rem; }
    .spp-card-title { display:flex; align-items:center; gap:.6rem; margin-bottom:.9rem; }
    .spp-card-title i { width:34px; height:34px; border-radius:8px; display:grid; place-items:center; background:#eefdff; color:#162d78; }
    .spp-card-title h6 { margin:0; font-weight:780; }
    .spp-card-title small { color:var(--muted); display:block; margin-top:.08rem; }
    .spp-form-card .form-label,.spp-list-card .form-label { color:#475467; font-size:.78rem; font-weight:720; }
    .spp-toolbar { display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:.75rem; }
    .spp-search { position:relative; width:min(100%,300px); }
    .spp-search i { position:absolute; left:.75rem; top:50%; transform:translateY(-50%); color:#98a2b3; }
    .spp-search input { padding-left:2.1rem; }
    .spp-badge { display:inline-flex; align-items:center; gap:.35rem; border-radius:999px; padding:.32rem .62rem; font-size:.78rem; font-weight:740; background:#f8fafc; border:1px solid #d6dde6; color:#344054; white-space:nowrap; }
    .spp-badge-success { background:#eefdff; border-color:#bbf7d0; color:#075f7f; }
    .spp-badge-muted { background:#f8fafc; color:#667085; }
    .spp-money { font-weight:800; color:#162d78; white-space:nowrap; }
    .spp-action-row { display:flex; flex-wrap:wrap; gap:.35rem; justify-content:flex-end; }
    .spp-action-row .btn { display:inline-flex; align-items:center; justify-content:center; gap:.35rem; }
    .spp-flow { display:grid; gap:.7rem; }
    .spp-flow-item { display:flex; gap:.75rem; align-items:flex-start; padding:.85rem; border:1px solid #e6eaf0; border-radius:8px; background:#fff; }
    .spp-flow-item span { width:30px; height:30px; border-radius:8px; display:grid; place-items:center; flex:0 0 auto; color:#fff; font-weight:800; background:#16b7e8; }
    .spp-flow-item:nth-child(2) span { background:#162d78; }
    .spp-flow-item:nth-child(3) span { background:#b45309; }
    .spp-flow-item strong { display:block; line-height:1.2; }
    .spp-flow-item small { color:var(--muted); display:block; margin-top:.15rem; }
    @media (max-width: 991px) {
        .spp-summary { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .spp-panel-grid { grid-template-columns:1fr; }
        .spp-header,.spp-toolbar { display:block; }
        .spp-search { width:100%; margin-top:.75rem; }
    }
    @media (max-width: 575px) {
        .spp-summary { grid-template-columns:1fr; }
        .spp-tabs .nav-link { width:100%; justify-content:flex-start; }
        .spp-action-row { justify-content:flex-start; }
    }

    .spp-header {
        position:relative;
        overflow:hidden;
        align-items:center;
        padding:1.2rem;
        border:1px solid #d6edf6;
        border-radius:8px;
        background:
            linear-gradient(135deg,rgba(255,255,255,.97),rgba(238,253,255,.92)),
            radial-gradient(circle at top right,rgba(22,183,232,.2),transparent 34%);
        box-shadow:0 18px 44px rgba(16,24,40,.07);
    }
    .spp-header::before {
        content:"";
        position:absolute;
        inset:0 auto 0 0;
        width:5px;
        background:linear-gradient(180deg,#16b7e8,#162d78);
    }
    .spp-kicker {
        display:inline-flex;
        align-items:center;
        padding:.28rem .58rem;
        margin-bottom:.45rem;
        border:1px solid #c9edf8;
        border-radius:999px;
        background:#fff;
        color:#075f7f;
        font-size:.72rem;
        font-weight:820;
        text-transform:uppercase;
        letter-spacing:.04em;
    }
    .spp-header h3 {
        font-size:1.45rem;
        font-weight:850;
        color:#102027;
    }
    .spp-header-actions {
        display:flex;
        flex-wrap:wrap;
        justify-content:flex-end;
        gap:.55rem;
        flex:0 0 auto;
    }
    .spp-summary {
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:1rem;
    }
    .spp-stat {
        position:relative;
        overflow:hidden;
        min-height:112px;
        padding:1rem;
        border-color:#e6eaf0;
        background:#fff;
        box-shadow:0 12px 32px rgba(16,24,40,.06);
    }
    .spp-stat::before {
        content:"";
        position:absolute;
        left:0;
        top:0;
        bottom:0;
        width:4px;
        background:linear-gradient(180deg,#16b7e8,#162d78);
    }
    .spp-stat-icon {
        width:46px;
        height:46px;
        box-shadow:0 10px 24px rgba(22,45,120,.16);
    }
    .spp-stat span {
        font-size:.74rem;
        font-weight:820;
        text-transform:uppercase;
        letter-spacing:.04em;
    }
    .spp-stat strong {
        color:#102027;
        font-size:clamp(1.15rem,1.8vw,1.45rem);
        overflow-wrap:anywhere;
    }
    .spp-flow-modern {
        grid-template-columns:repeat(3,minmax(0,1fr));
        margin-bottom:1rem;
    }
    .spp-flow-modern .spp-flow-item {
        border-color:#d6edf6;
        background:linear-gradient(180deg,#fff,#f8fbfc);
        box-shadow:0 10px 28px rgba(16,24,40,.05);
    }
    .spp-flow-modern .spp-flow-item span {
        width:36px;
        height:36px;
        box-shadow:0 8px 18px rgba(22,45,120,.12);
    }
    .spp-tabs {
        position:sticky;
        top:86px;
        z-index:10;
        border-color:#d6edf6;
        background:rgba(255,255,255,.94);
        backdrop-filter:blur(14px);
        box-shadow:0 12px 32px rgba(16,24,40,.06);
    }
    .spp-tabs .nav-link {
        min-height:42px;
        color:#475467;
    }
    .spp-tabs .nav-link i {
        width:1rem;
        text-align:center;
    }
    .spp-tabs .nav-link.active {
        background:linear-gradient(135deg,#162d78,#0794c9);
    }
    .spp-panel-grid {
        grid-template-columns:minmax(280px,380px) minmax(0,1fr);
    }
    .spp-form-card,
    .spp-list-card {
        border:1px solid #d6edf6;
        background:rgba(255,255,255,.96);
        box-shadow:0 18px 44px rgba(16,24,40,.07);
    }
    .spp-form-card {
        position:sticky;
        top:158px;
    }
    .spp-card-title {
        padding-bottom:.8rem;
        border-bottom:1px solid #edf1f5;
    }
    .spp-card-title i {
        background:#eef8fc;
        border:1px solid #c9edf8;
        color:#075f7f;
    }
    .spp-card-title h6 {
        color:#102027;
        font-weight:830;
    }
    .spp-toolbar {
        padding-bottom:.75rem;
        border-bottom:1px solid #edf1f5;
    }
    .spp-search input {
        min-height:38px;
        border-radius:999px;
        background:#fff;
    }
    .spp-badge {
        background:#eef8fc;
        border-color:#c9edf8;
        color:#0e205a;
    }
    .spp-money {
        color:#075f7f;
    }
    .spp-action-row {
        align-items:center;
    }
    .spp-action-row .btn {
        min-height:32px;
        border-radius:6px;
    }
    #tab-otomatis .spp-panel-grid {
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
    #tab-otomatis .spp-form-card {
        position:static;
    }
    .table-responsive {
        border-color:#d6edf6;
    }
    .tab-pane {
        animation:sppFade .18s ease-out;
    }
    @keyframes sppFade {
        from { opacity:.35; transform:translateY(4px); }
        to { opacity:1; transform:translateY(0); }
    }
    @media (max-width: 1199px) {
        .spp-summary,
        .spp-flow-modern,
        #tab-otomatis .spp-panel-grid {
            grid-template-columns:repeat(2,minmax(0,1fr));
        }
        .spp-form-card {
            position:static;
        }
    }
    @media (max-width: 991px) {
        .spp-header {
            display:grid;
        }
        .spp-header-actions {
            justify-content:flex-start;
            margin-top:1rem;
        }
        .spp-tabs {
            position:static;
        }
    }
    @media (max-width: 575px) {
        .spp-summary,
        .spp-flow-modern,
        #tab-otomatis .spp-panel-grid {
            grid-template-columns:1fr;
        }
        .spp-header-actions .btn {
            width:100%;
        }
    }
</style>

<div class="spp-header">
    <div>
        <span class="spp-kicker">Control Center SPP</span>
        <h3>Pengaturan SPP</h3>
        <p>Kelola data dasar pembayaran SPP dari satu halaman: kelas, tahun ajaran, nominal biaya, dan pembuatan tagihan bulanan.</p>
    </div>
    <div class="spp-header-actions">
        <button class="btn btn-primary" data-bs-toggle="tab" data-bs-target="#tab-biaya" type="button"><i class="bi bi-cash-stack"></i>Atur Biaya</button>
        <button class="btn btn-outline-primary" data-bs-toggle="tab" data-bs-target="#tab-otomatis" type="button"><i class="bi bi-calendar2-week"></i>Jadwal Otomatis</button>
    </div>
</div>

<div class="spp-summary">
    <div class="spp-stat">
        <div class="spp-stat-icon"><i class="bi bi-building"></i></div>
        <div><span>Total Kelas</span><strong>{{ $kelas->count() }}</strong></div>
    </div>
    <div class="spp-stat">
        <div class="spp-stat-icon"><i class="bi bi-calendar2-week"></i></div>
        <div><span>Tahun Aktif</span><strong>{{ $activeYear->nama ?? 'Belum diatur' }}</strong></div>
    </div>
    <div class="spp-stat">
        <div class="spp-stat-icon"><i class="bi bi-cash-coin"></i></div>
        <div><span>Aturan Biaya</span><strong>{{ $biaya->count() }}</strong></div>
    </div>
    <div class="spp-stat">
        <div class="spp-stat-icon"><i class="bi bi-wallet2"></i></div>
        <div><span>Biaya Umum</span><strong>{{ $defaultFee ? 'Rp '.number_format($defaultFee->nominal, 0, ',', '.') : '-' }}</strong></div>
    </div>
</div>

<div class="spp-flow spp-flow-modern">
    <div class="spp-flow-item">
        <span>1</span>
        <div><strong>Siapkan kelas dan tahun aktif</strong><small>Pastikan rombel dan tahun ajaran aktif sudah benar sebelum biaya dibuat.</small></div>
    </div>
    <div class="spp-flow-item">
        <span>2</span>
        <div><strong>Atur nominal biaya</strong><small>Buat biaya umum untuk semua kelas atau biaya spesifik per kelas.</small></div>
    </div>
    <div class="spp-flow-item">
        <span>3</span>
        <div><strong>Aktifkan jadwal tagihan</strong><small>Generate tagihan otomatis mengikuti tanggal keluar dan jatuh tempo.</small></div>
    </div>
</div>

<ul class="nav spp-tabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-kelas" type="button" role="tab"><i class="bi bi-building"></i>Kelas</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tahun" type="button" role="tab"><i class="bi bi-calendar-range"></i>Tahun Ajaran</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-biaya" type="button" role="tab"><i class="bi bi-cash-stack"></i>Biaya SPP</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-otomatis" type="button" role="tab"><i class="bi bi-calendar2-week"></i>Otomatis & Diskon</button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-kelas" role="tabpanel">
        <div class="spp-panel-grid">
            <div class="content-card spp-form-card">
                <div class="spp-card-title">
                    <i class="bi bi-plus-lg"></i>
                    <div><h6>Tambah Kelas</h6><small>Master rombel siswa</small></div>
                </div>
                <form method="post" action="{{ route('admin.kelas.store') }}">
                    @csrf
                    <label class="form-label">Nama Kelas</label>
                    <input name="nama_kelas" class="form-control mb-3" placeholder="Contoh: X-A" required>
                    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-save me-1"></i>Simpan Kelas</button>
                </form>
            </div>

            <div class="content-card spp-list-card">
                <div class="spp-toolbar">
                    <div class="spp-card-title mb-0">
                        <i class="bi bi-list-ul"></i>
                        <div><h6>Daftar Kelas</h6><small id="settingsClassCount">{{ $kelas->count() }} data tampil</small></div>
                    </div>
                    <div class="spp-search">
                        <i class="bi bi-search"></i>
                        <input class="form-control form-control-sm" placeholder="Cari kelas..." data-live-search="#settingsClassTable" data-live-count="#settingsClassCount">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="settingsClassTable">
                        <thead><tr><th>#</th><th>Nama Kelas</th></tr></thead>
                        <tbody>
                            @forelse($kelas as $item)
                                <tr><td>{{ $loop->iteration }}</td><td><span class="spp-badge"><i class="bi bi-building"></i>{{ $item->nama_kelas }}</span></td></tr>
                            @empty
                                <tr data-empty-row><td colspan="2" class="empty-state">Belum ada kelas.</td></tr>
                            @endforelse
                            <tr data-empty-row style="display:none"><td colspan="2" class="empty-state">Kelas tidak ditemukan.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-tahun" role="tabpanel">
        <div class="spp-panel-grid">
            <div class="content-card spp-form-card">
                <div class="spp-card-title">
                    <i class="bi bi-calendar-plus"></i>
                    <div><h6>Tambah Tahun Ajaran</h6><small>Periode akademik SPP</small></div>
                </div>
                <form method="post" action="{{ route('admin.tahun.store') }}">
                    @csrf
                    <label class="form-label">Nama Tahun Ajaran</label>
                    <input name="nama" class="form-control mb-3" placeholder="2025/2026" required>
                    <div class="form-check form-switch mb-3">
                        <input name="is_active" type="checkbox" class="form-check-input" id="settingsActive">
                        <label for="settingsActive" class="form-check-label">Jadikan tahun aktif</label>
                    </div>
                    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-save me-1"></i>Simpan Tahun</button>
                </form>
            </div>

            <div class="content-card spp-list-card">
                <div class="spp-card-title">
                    <i class="bi bi-calendar-check"></i>
                    <div><h6>Daftar Tahun Ajaran</h6><small>{{ $tahun->count() }} periode tersedia</small></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Nama</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                            @forelse($tahun as $item)
                                <tr>
                                    <td>
                                        <form id="tahun-update-{{ $item->id }}" method="post" action="{{ route('admin.tahun.update', $item) }}">
                                            @csrf
                                            @method('put')
                                            <input name="nama" class="form-control form-control-sm" value="{{ $item->nama }}" required>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input form="tahun-update-{{ $item->id }}" name="is_active" type="checkbox" class="form-check-input" @checked($item->is_active)>
                                            <label class="form-check-label">
                                                <span class="spp-badge {{ $item->is_active ? 'spp-badge-success' : 'spp-badge-muted' }}">{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="spp-action-row">
                                            <button form="tahun-update-{{ $item->id }}" class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-check2"></i>Update</button>
                                            <form method="post" action="{{ route('admin.tahun.destroy', $item) }}">
                                                @csrf
                                                @method('delete')
                                                <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Hapus tahun ajaran ini?')"><i class="bi bi-trash"></i>Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="empty-state">Belum ada tahun ajaran.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-biaya" role="tabpanel">
        <div class="spp-panel-grid">
            <div class="content-card spp-form-card">
                <div class="spp-card-title">
                    <i class="bi bi-cash-coin"></i>
                    <div><h6>Tambah Biaya SPP</h6><small>Umum atau per kelas</small></div>
                </div>
                <form method="post" action="{{ route('admin.biaya.store') }}">
                    @csrf
                    <label class="form-label">Tahun Ajaran</label>
                    <select name="tahun_ajaran_id" class="form-select mb-3" required>
                        @foreach($tahun as $t)
                            <option value="{{ $t->id }}" @selected($t->is_active)>{{ $t->nama }}{{ $t->is_active ? ' - Aktif' : '' }}</option>
                        @endforeach
                    </select>
                    <label class="form-label">Kelas</label>
                    <select name="kelas_id" class="form-select mb-3">
                        <option value="">Semua Kelas</option>
                        @foreach($kelas as $k)
                            <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                    <label class="form-label">Nominal</label>
                    <input name="nominal" type="number" min="1" class="form-control mb-3" placeholder="Contoh: 250000" required>
                    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-save me-1"></i>Simpan Biaya</button>
                </form>
            </div>

            <div class="content-card spp-list-card">
                <div class="spp-toolbar">
                    <div class="spp-card-title mb-0">
                        <i class="bi bi-cash-stack"></i>
                        <div><h6>Daftar Biaya SPP</h6><small id="settingsFeeCount">{{ $biaya->count() }} data tampil</small></div>
                    </div>
                    <div class="spp-search">
                        <i class="bi bi-search"></i>
                        <input class="form-control form-control-sm" placeholder="Cari tahun, kelas, nominal..." data-live-search="#settingsFeeTable" data-live-count="#settingsFeeCount">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="settingsFeeTable">
                        <thead><tr><th>Tahun</th><th>Kelas</th><th>Nominal</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                            @forelse($biaya as $item)
                                <tr>
                                    <td>
                                        <form id="biaya-update-{{ $item->id }}" method="post" action="{{ route('admin.biaya.update', $item) }}">
                                            @csrf
                                            @method('put')
                                            <select name="tahun_ajaran_id" class="form-select form-select-sm">
                                                @foreach($tahun as $t)
                                                    <option value="{{ $t->id }}" @selected($item->tahun_ajaran_id === $t->id)>{{ $t->nama }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <select form="biaya-update-{{ $item->id }}" name="kelas_id" class="form-select form-select-sm">
                                            <option value="">Semua Kelas</option>
                                            @foreach($kelas as $k)
                                                <option value="{{ $k->id }}" @selected($item->kelas_id === $k->id)>{{ $k->nama_kelas }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input form="biaya-update-{{ $item->id }}" name="nominal" type="number" min="1" class="form-control form-control-sm spp-money-input" value="{{ $item->nominal }}" required>
                                        <div class="spp-money small mt-1">Rp {{ number_format($item->nominal, 0, ',', '.') }}</div>
                                    </td>
                                    <td>
                                        <div class="spp-action-row">
                                            <button form="biaya-update-{{ $item->id }}" class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-check2"></i>Update</button>
                                            <form method="post" action="{{ route('admin.biaya.destroy', $item) }}">
                                                @csrf
                                                @method('delete')
                                                <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Hapus biaya SPP ini?')"><i class="bi bi-trash"></i>Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr data-empty-row><td colspan="4" class="empty-state">Belum ada biaya SPP.</td></tr>
                            @endforelse
                            <tr data-empty-row style="display:none"><td colspan="4" class="empty-state">Biaya tidak ditemukan.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-otomatis" role="tabpanel">
        <div class="spp-panel-grid mb-3">
            <div class="content-card spp-form-card">
                <div class="spp-card-title">
                    <i class="bi bi-calendar2-check"></i>
                    <div><h6>Jadwal Tagihan Otomatis</h6><small>Generate bulanan mengikuti tahun ajaran aktif</small></div>
                </div>
                <form method="post" action="{{ route('admin.auto-bill.update') }}" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input name="is_enabled" type="checkbox" class="form-check-input" id="autoBillEnabled" @checked($autoBillSetting->is_enabled)>
                            <label class="form-check-label" for="autoBillEnabled">Aktifkan generate otomatis</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Tagihan Keluar</label>
                        <input name="generate_day" type="number" min="1" max="28" class="form-control" value="{{ $autoBillSetting->generate_day }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Jatuh Tempo</label>
                        <input name="due_day" type="number" min="1" max="28" class="form-control" value="{{ $autoBillSetting->due_day }}" required>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-save me-1"></i>Simpan Jadwal</button>
                    </div>
                </form>
            </div>

            <div class="content-card spp-form-card">
                <div class="spp-card-title">
                    <i class="bi bi-ticket-perforated"></i>
                    <div><h6>Tambah Gratis/Diskon</h6><small>Berlaku saat tagihan dibuat</small></div>
                </div>
                <form method="post" action="{{ route('admin.exemptions.store') }}" class="row g-3" id="exemptionForm">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label">Tahun Ajaran</label>
                        <select name="tahun_ajaran_id" class="form-select" required>
                            @foreach($tahun as $t)
                                <option value="{{ $t->id }}" @selected($t->is_active)>{{ $t->nama }}{{ $t->is_active ? ' - Aktif' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Bulan</label>
                        <select name="bulan" class="form-select" required>
                            @foreach($months as $month)
                                <option value="{{ $month }}" @selected($month === now()->translatedFormat('F'))>{{ $month }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tahun</label>
                        <input name="tahun" type="number" min="2020" max="2100" class="form-control" value="{{ date('Y') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cakupan</label>
                        <select name="scope_type" class="form-select" id="exemptionScopeSelect" required>
                            <option value="all" @selected(old('scope_type', 'all') === 'all')>Semua Siswa</option>
                            <option value="kelas" @selected(old('scope_type') === 'kelas')>Kelas Tertentu</option>
                            <option value="siswa" @selected(old('scope_type') === 'siswa')>Siswa Tertentu</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kelas</label>
                        <select name="kelas_id" class="form-select" id="exemptionClassSelect">
                            <option value="">Pilih jika cakupan kelas</option>
                            @foreach($kelas as $k)
                                <option value="{{ $k->id }}" @selected((int) old('kelas_id') === $k->id)>{{ $k->nama_kelas }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Siswa</label>
                        <select name="siswa_id" class="form-select" id="exemptionStudentSelect">
                            <option value="">Pilih jika cakupan siswa</option>
                            @foreach($siswaList as $siswa)
                                <option value="{{ $siswa->id }}" data-kelas-id="{{ $siswa->kelas_id }}" @selected((int) old('siswa_id') === $siswa->id)>{{ $siswa->nama }} - {{ $siswa->kelas->nama_kelas ?? '-' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jenis</label>
                        <select name="benefit_type" class="form-select" required>
                            <option value="free">Gratis Penuh</option>
                            <option value="nominal">Potongan Nominal</option>
                            <option value="percent">Potongan Persen</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nilai Potongan</label>
                        <input name="amount" type="number" min="1" max="100000000" class="form-control" placeholder="Kosongkan untuk gratis">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Alasan</label>
                        <input name="alasan" class="form-control" placeholder="Subsidi, beasiswa, dll">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-plus-lg me-1"></i>Simpan Aturan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="content-card spp-list-card">
            <div class="spp-toolbar">
                <div class="spp-card-title mb-0">
                    <i class="bi bi-tags"></i>
                    <div><h6>Daftar Gratis/Diskon</h6><small id="settingsExemptionCount">{{ $exemptions->count() }} aturan tampil</small></div>
                </div>
                <div class="spp-search">
                    <i class="bi bi-search"></i>
                    <input class="form-control form-control-sm" placeholder="Cari aturan..." data-live-search="#settingsExemptionTable" data-live-count="#settingsExemptionCount">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="settingsExemptionTable">
                    <thead><tr><th>Periode</th><th>Cakupan</th><th>Benefit</th><th>Alasan</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        @forelse($exemptions as $item)
                            @php
                                $scopeText = match ($item->scope_type) {
                                    'kelas' => 'Kelas '.($item->kelas->nama_kelas ?? '-'),
                                    'siswa' => ($item->siswa->nama ?? '-').' | Kelas '.($item->kelas->nama_kelas ?? $item->siswa?->kelas?->nama_kelas ?? '-'),
                                    default => 'Semua Siswa',
                                };
                                $benefitText = match ($item->benefit_type) {
                                    'nominal' => 'Potongan Rp '.number_format($item->amount, 0, ',', '.'),
                                    'percent' => 'Potongan '.$item->amount.'%',
                                    default => 'Gratis Penuh',
                                };
                            @endphp
                            <tr>
                                <td>{{ $item->bulan }} {{ $item->tahun }}<div class="small text-muted">{{ $item->tahunAjaran->nama ?? '-' }}</div></td>
                                <td>{{ $scopeText }}</td>
                                <td><span class="spp-badge spp-badge-success">{{ $benefitText }}</span></td>
                                <td>{{ $item->alasan ?: '-' }}</td>
                                <td>
                                    <div class="spp-action-row">
                                        <form method="post" action="{{ route('admin.exemptions.destroy', $item) }}">
                                            @csrf
                                            @method('delete')
                                            <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Hapus aturan gratis/diskon ini?')"><i class="bi bi-trash"></i>Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr data-empty-row><td colspan="5" class="empty-state">Belum ada aturan gratis/diskon.</td></tr>
                        @endforelse
                        <tr data-empty-row style="display:none"><td colspan="5" class="empty-state">Aturan tidak ditemukan.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const scopeSelect = document.getElementById('exemptionScopeSelect');
    const classSelect = document.getElementById('exemptionClassSelect');
    const studentSelect = document.getElementById('exemptionStudentSelect');

    if (!scopeSelect || !classSelect || !studentSelect) return;

    function syncFields(source) {
        if (source === 'student' && studentSelect.value !== '') {
            scopeSelect.value = 'siswa';
            classSelect.value = studentSelect.selectedOptions[0]?.dataset.kelasId || '';
        }

        if (source === 'class' && classSelect.value !== '') {
            scopeSelect.value = 'kelas';
            studentSelect.value = '';
        }

        if (scopeSelect.value === 'all') {
            classSelect.value = '';
            studentSelect.value = '';
            classSelect.disabled = true;
            studentSelect.disabled = true;
            return;
        }

        if (scopeSelect.value === 'kelas') {
            studentSelect.value = '';
            classSelect.disabled = false;
            studentSelect.disabled = true;
            return;
        }

        classSelect.disabled = true;
        studentSelect.disabled = false;

        if (studentSelect.value !== '') {
            classSelect.value = studentSelect.selectedOptions[0]?.dataset.kelasId || '';
        }
    }

    scopeSelect.addEventListener('change', function () { syncFields('scope'); });
    classSelect.addEventListener('change', function () { syncFields('class'); });
    studentSelect.addEventListener('change', function () { syncFields('student'); });
    syncFields('scope');
});
</script>
@endpush
@endsection
