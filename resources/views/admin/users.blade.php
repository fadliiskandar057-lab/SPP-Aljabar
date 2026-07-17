@extends('layouts.app')

@section('content')
@php
    $roleLabels = [
        'admin_tu' => 'Admin TU',
        'kepala_sekolah' => 'Kepala Sekolah',
        'wali_kelas' => 'Wali Kelas',
        'siswa' => 'Siswa / Orang Tua',
    ];
    $roleIcons = [
        'admin_tu' => 'bi-shield-check',
        'kepala_sekolah' => 'bi-mortarboard',
        'wali_kelas' => 'bi-person-video3',
        'siswa' => 'bi-people',
    ];
@endphp

<style>
    .user-hero { display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; margin-bottom:1rem; }
    .user-hero h3 { margin:0; font-weight:780; }
    .user-hero p { color:var(--muted); margin:.3rem 0 0; max-width:620px; }
    .user-stat-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.85rem; margin-bottom:1rem; }
    .user-stat { background:rgba(255,255,255,.9); border:1px solid rgba(230,234,240,.92); border-radius:8px; padding:1rem; display:flex; gap:.8rem; align-items:center; box-shadow:0 8px 22px rgba(16,24,40,.04); }
    .user-stat-icon { width:42px; height:42px; border-radius:8px; display:grid; place-items:center; color:#fff; background:#162d78; flex:0 0 auto; }
    .user-stat:nth-child(2) .user-stat-icon { background:#16b7e8; }
    .user-stat:nth-child(3) .user-stat-icon { background:#b45309; }
    .user-stat:nth-child(4) .user-stat-icon { background:#475467; }
    .user-stat span { display:block; color:var(--muted); font-size:.78rem; font-weight:650; }
    .user-stat strong { display:block; margin-top:.15rem; font-size:1.3rem; line-height:1; }
    .user-form-card { padding:1rem; margin-bottom:1rem; }
    .user-form-card .form-label { color:#475467; font-size:.78rem; font-weight:700; }
    .user-table-toolbar { display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:.75rem; }
    .user-search { max-width:320px; position:relative; }
    .user-search i { position:absolute; left:.75rem; top:50%; transform:translateY(-50%); color:#98a2b3; }
    .user-search input { padding-left:2.1rem; }
    .user-name-cell { display:flex; align-items:center; gap:.7rem; min-width:210px; }
    .user-avatar { width:38px; height:38px; border-radius:8px; display:grid; place-items:center; background:#eefdff; color:#162d78; font-weight:800; flex:0 0 auto; }
    .user-name-cell small { color:var(--muted); display:block; margin-top:.1rem; }
    .role-badge { display:inline-flex; align-items:center; gap:.4rem; border:1px solid #d6dde6; border-radius:999px; padding:.32rem .62rem; background:#fff; color:#344054; font-weight:700; font-size:.78rem; white-space:nowrap; }
    .login-pill { display:inline-flex; align-items:center; gap:.42rem; border-radius:999px; background:#f6fbfb; color:#162d78; padding:.34rem .65rem; font-weight:760; }
    .action-group { display:flex; flex-wrap:wrap; gap:.35rem; justify-content:flex-end; }
    .action-group .btn { display:inline-flex; align-items:center; justify-content:center; gap:.35rem; }
    .password-note { background:#fffbeb; border:1px solid #fde68a; color:#92400e; border-radius:8px; padding:.75rem .85rem; font-size:.86rem; }
    @media (max-width: 991px) {
        .user-stat-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .user-table-toolbar,.user-hero { display:block; }
        .user-search { max-width:none; margin-top:.75rem; }
    }
    @media (max-width: 575px) {
        .user-stat-grid { grid-template-columns:1fr; }
        .action-group { justify-content:flex-start; }
    }
    .user-hero { position:relative; overflow:hidden; align-items:center; padding:1.2rem; border:1px solid #d6edf6; border-radius:8px; background:linear-gradient(135deg,#fff,#eef8fc); box-shadow:0 18px 44px rgba(16,24,40,.07); }
    .user-hero::before { content:""; position:absolute; inset:0 auto 0 0; width:5px; background:linear-gradient(180deg,#16b7e8,#162d78); }
    .user-kicker { display:inline-flex; margin-bottom:.45rem; padding:.28rem .58rem; border:1px solid #c9edf8; border-radius:999px; background:#fff; color:#075f7f; font-size:.72rem; font-weight:820; text-transform:uppercase; letter-spacing:.04em; }
    .user-hero h3 { font-weight:850; color:#102027; }
    .user-stat { min-height:112px; border-color:#e6eaf0; background:#fff; box-shadow:0 12px 32px rgba(16,24,40,.06); position:relative; overflow:hidden; }
    .user-stat::before { content:""; position:absolute; inset:0 auto 0 0; width:4px; background:#16b7e8; }
    .user-stat span { font-size:.74rem; font-weight:820; text-transform:uppercase; letter-spacing:.04em; }
    .user-stat strong { font-size:1.55rem; color:#102027; }
    .user-form-card { border-color:#d6edf6; background:linear-gradient(180deg,#fff,#f8fbfc); box-shadow:0 18px 44px rgba(16,24,40,.07); }
    .user-form-card .form-control,.user-form-card .form-select { border-radius:999px; }
    .user-table-toolbar { padding-bottom:.85rem; border-bottom:1px solid #edf1f5; }
    .user-search input { border-radius:999px; background:#fff; }
    #userTable tbody tr:hover { background:#f8fbfc; box-shadow:inset 3px 0 0 #16b7e8; }
    .user-avatar { background:linear-gradient(145deg,#16b7e8,#162d78); color:#fff; box-shadow:0 8px 18px rgba(22,45,120,.14); }
    .login-pill,.role-badge { border-color:#c9edf8; background:#eef8fc; color:#0e205a; }
    .action-group { padding:.25rem; border:1px solid #edf1f5; border-radius:8px; background:#fff; }
</style>

<div class="user-hero">
    <div>
        <span class="user-kicker">Akses Sistem</span>
        <h3>User Management</h3>
        <p>Kelola akun login untuk admin, kepala sekolah, wali kelas, dan siswa/orang tua. Username di halaman ini dipakai saat masuk ke sistem.</p>
    </div>
</div>

<div class="user-stat-grid">
    <div class="user-stat">
        <div class="user-stat-icon"><i class="bi bi-person-lines-fill"></i></div>
        <div><span>Total User</span><strong>{{ $users->count() }}</strong></div>
    </div>
    <div class="user-stat">
        <div class="user-stat-icon"><i class="bi bi-shield-check"></i></div>
        <div><span>Admin TU</span><strong>{{ $users->where('role', 'admin_tu')->count() }}</strong></div>
    </div>
    <div class="user-stat">
        <div class="user-stat-icon"><i class="bi bi-mortarboard"></i></div>
        <div><span>Kepala Sekolah</span><strong>{{ $users->where('role', 'kepala_sekolah')->count() }}</strong></div>
    </div>
    <div class="user-stat">
        <div class="user-stat-icon"><i class="bi bi-person-video3"></i></div>
        <div><span>Wali Kelas</span><strong>{{ $users->where('role', 'wali_kelas')->count() }}</strong></div>
    </div>
</div>

<div class="content-card user-form-card">
    <form method="post" action="{{ route('admin.users.store') }}" class="row g-3 align-items-end">
        @csrf
        <div class="col-lg-2 col-md-6">
            <label class="form-label">Nama</label>
            <input name="name" class="form-control" placeholder="Nama user" required>
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="form-label">Username Login</label>
            <input name="username" class="form-control" placeholder="contoh: admin" required>
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="form-label">Password Awal</label>
            <input name="password" type="password" class="form-control" placeholder="Minimal 6 karakter" required>
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="form-label">Role</label>
            <select name="role" class="form-select">
                <option value="admin_tu">Admin TU</option>
                <option value="kepala_sekolah">Kepala Sekolah</option>
                <option value="wali_kelas">Wali Kelas</option>
                <option value="siswa">Siswa / Orang Tua</option>
            </select>
        </div>
        <div class="col-lg-3 col-md-8">
            <label class="form-label">Hubungkan Siswa</label>
            <select name="siswa_id" class="form-select">
                <option value="">Tanpa siswa</option>
                @foreach($siswa as $s)
                    <option value="{{ $s->id }}">{{ $s->nis }} - {{ $s->nama }}{{ $s->kelas ? ' | '.$s->kelas->nama_kelas : '' }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="form-label">Kelas Wali</label>
            <select name="kelas_id" class="form-select">
                <option value="">Pilih kelas</option>
                @foreach($kelas as $k)
                    <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-1 col-md-4">
            <button class="btn btn-primary w-100" type="submit" title="Simpan user baru">
                <i class="bi bi-plus-lg"></i>
            </button>
        </div>
    </form>
</div>

<div class="content-card p-3">
    <div class="user-table-toolbar">
        <div>
            <h6 class="mb-1 fw-bold">Daftar Akun Login</h6>
            <div class="live-search-count" id="userCount">{{ $users->count() }} data tampil</div>
        </div>
        <div class="user-search">
            <i class="bi bi-search"></i>
            <input class="form-control form-control-sm" placeholder="Cari nama, username, role..." data-live-search="#userTable" data-live-count="#userCount">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="userTable">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Username Login</th>
                    <th>Role</th>
                    <th>Terhubung Siswa</th>
                    <th>Kelas Wali</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                    <tr>
                        <td>
                            <div class="user-name-cell">
                                <div class="user-avatar">{{ strtoupper(substr($u->name, 0, 1)) }}</div>
                                <div>
                                    <strong>{{ $u->name }}</strong>
                                    <small>Dibuat {{ $u->created_at?->format('d M Y') ?? '-' }}</small>
                                </div>
                            </div>
                        </td>
                        <td><span class="login-pill"><i class="bi bi-person-badge"></i>{{ $u->username }}</span></td>
                        <td><span class="role-badge"><i class="bi {{ $roleIcons[$u->role] ?? 'bi-person' }}"></i>{{ $roleLabels[$u->role] ?? $u->role }}</span></td>
                        <td>
                            @if($u->siswa)
                                <strong>{{ $u->siswa->nama }}</strong>
                                <div class="text-muted small">{{ $u->siswa->nis }}{{ $u->siswa->kelas ? ' | '.$u->siswa->kelas->nama_kelas : '' }}</div>
                            @else
                                <span class="text-muted">Tidak terhubung</span>
                            @endif
                        </td>
                        <td>{{ $u->kelas->nama_kelas ?? '-' }}</td>
                        <td>
                            <div class="action-group">
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#editUser{{ $u->id }}" title="Edit username dan data login">
                                    <i class="bi bi-pencil-square"></i><span>Edit</span>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#passwordUser{{ $u->id }}" title="Ganti password custom">
                                    <i class="bi bi-key"></i><span>Ganti</span>
                                </button>
                                <form method="post" action="{{ route('admin.users.reset-password', $u) }}" onsubmit="return confirm('Reset password {{ $u->name }} ke password default?')">
                                    @csrf
                                    @method('patch')
                                    <button class="btn btn-sm btn-outline-warning" type="submit" title="Reset ke password default">
                                        <i class="bi bi-arrow-clockwise"></i><span>Reset</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                @empty
                    <tr data-empty-row><td colspan="6" class="empty-state">Belum ada user.</td></tr>
                @endforelse
                <tr data-empty-row style="display:none"><td colspan="6" class="empty-state">User tidak ditemukan.</td></tr>
            </tbody>
        </table>
    </div>
</div>

@foreach($users as $u)
    <div class="modal fade" id="editUser{{ $u->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="post" action="{{ route('admin.users.update', $u) }}">
                    @csrf
                    @method('put')
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">Edit Akun Login</h5>
                            <small class="text-muted">Username yang diisi akan dipakai user saat login.</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama</label>
                                <input name="name" class="form-control" value="{{ $u->name }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username Login</label>
                                <input name="username" class="form-control" value="{{ $u->username }}" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-select">
                                    @foreach($roleLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($u->role === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Hubungkan Siswa</label>
                                <select name="siswa_id" class="form-select">
                                    <option value="">Tanpa siswa</option>
                                    @foreach($siswa as $s)
                                        <option value="{{ $s->id }}" @selected($u->siswa_id === $s->id)>{{ $s->nis }} - {{ $s->nama }}{{ $s->kelas ? ' | '.$s->kelas->nama_kelas : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Kelas Wali</label>
                                <select name="kelas_id" class="form-select">
                                    <option value="">Tanpa kelas</option>
                                    @foreach($kelas as $k)
                                        <option value="{{ $k->id }}" @selected($u->kelas_id === $k->id)>{{ $k->nama_kelas }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i>Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="passwordUser{{ $u->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="{{ route('admin.users.password', $u) }}">
                    @csrf
                    @method('patch')
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">Ganti Password</h5>
                            <small class="text-muted">{{ $u->name }} | {{ $u->username }}</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="password-note mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Gunakan tombol reset bila ingin mengembalikan password ke default <strong>password</strong>.
                        </div>
                        <label class="form-label">Password Baru</label>
                        <input name="password" type="password" class="form-control mb-3" minlength="6" required>
                        <label class="form-label">Konfirmasi Password</label>
                        <input name="password_confirmation" type="password" class="form-control" minlength="6" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-primary" type="submit"><i class="bi bi-key me-1"></i>Ganti Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection
