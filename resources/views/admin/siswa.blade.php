@extends('layouts.app')

@section('content')
<div class="student-page">
    <div class="student-header">
        <div>
            <span class="student-kicker">Administrasi siswa</span>
            <h3>Data Siswa</h3>
            <p>Kelola data siswa aktif, import dari Excel, dan cari data dengan Sequential Search.</p>
        </div>
        <div class="student-header-actions">
            <button class="btn btn-primary student-action-button" type="button" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                <i class="bi bi-person-plus"></i> Tambah Siswa
            </button>
            <button class="btn btn-outline-primary student-action-button" type="button" data-bs-toggle="modal" data-bs-target="#importStudentModal">
                <i class="bi bi-upload"></i> Import
            </button>
            <button class="btn btn-outline-success student-action-button" type="button" data-bs-toggle="modal" data-bs-target="#exportStudentModal">
                <i class="bi bi-download"></i> Export
            </button>
            <div class="student-stat">
                <span>Total data</span>
                <strong>{{ $siswa->count() }}</strong>
            </div>
            <div class="student-stat">
                <span>Aktif</span>
                <strong>{{ $siswa->where('status', 'aktif')->count() }}</strong>
            </div>
        </div>
    </div>

    @if(session('import_warnings'))
        <div class="alert alert-warning">
            <strong>Catatan import:</strong>
            <ul class="mb-0 mt-2">
                @foreach(session('import_warnings') as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="student-table-panel">
        <div class="table-toolbar">
            <div>
                <h6>Daftar Siswa</h6>
                <div id="studentsMeta" class="student-meta">Data diperiksa: {{ $meta['checked'] }} | Hasil: {{ $siswa->count() }} | Waktu: {{ $meta['duration_ms'] }} ms</div>
            </div>
            <div class="student-search">
                <i class="bi bi-search"></i>
                <input class="form-control" placeholder="Cari NIS, nama, email, atau kelas..." data-sequential-url="{{ route('admin.siswa.search') }}" data-results-target="#studentsRows" data-meta-target="#studentsMeta" data-extra-filter-targets="#studentClassFilter">
            </div>
        </div>

        <div class="student-filters">
            <label for="studentClassFilter">Filter kelas</label>
            <select id="studentClassFilter" class="form-select form-select-sm" name="kelas_id" data-sequential-filter>
                <option value="">Semua kelas</option>
                @foreach($kelas as $k)
                    <option value="{{ $k->id }}" @selected((string) request('kelas_id') === (string) $k->id)>{{ $k->nama_kelas }}</option>
                @endforeach
            </select>
        </div>

        <div class="bulk-actions">
            <div class="bulk-count"><i class="bi bi-check2-square"></i><span id="selectedStudentsCount">0 siswa dipilih</span></div>
            <form id="bulkUpdateStudentsForm" class="bulk-form" method="post" action="{{ route('admin.siswa.bulk-update') }}" data-bulk-form>
                @csrf
                @method('patch')
                <select name="kelas_id" class="form-select form-select-sm">
                    <option value="">Kelas tetap</option>
                    @foreach($kelas as $k)
                        <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Status tetap</option>
                    <option value="aktif">Aktif</option>
                    <option value="lulus">Lulus</option>
                    <option value="keluar">Keluar</option>
                </select>
                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Update Terpilih</button>
            </form>
            <form id="bulkDeleteStudentsForm" method="post" action="{{ route('admin.siswa.bulk-delete') }}" data-bulk-form data-confirm="Hapus semua siswa yang dipilih?">
                @csrf
                @method('delete')
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Hapus Terpilih</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table align-middle student-table" id="studentsTable">
                <thead>
                    <tr>
                        <th style="width:42px"><input id="selectAllStudents" class="form-check-input" type="checkbox" aria-label="Pilih semua siswa"></th>
                        <th>NIS</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>Email</th>
                        <th>HP Orang Tua</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody id="studentsRows">@include('admin.partials.student_rows', ['siswa' => $siswa, 'kelas' => $kelas])</tbody>
            </table>
        </div>
    </section>
</div>

<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="post" action="{{ route('admin.siswa.store') }}">
                @csrf
                <div class="modal-header">
                    <div class="panel-heading mb-0">
                        <span class="panel-icon panel-icon-soft"><i class="bi bi-person-plus"></i></span>
                        <div>
                            <h5 class="modal-title" id="addStudentModalLabel">Tambah Siswa</h5>
                            <p>Simpan satu siswa dan buat akun portal otomatis. Username memakai NIS, password awal <code>password</code>.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    @include('admin.siswa_form', ['item' => null])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Siswa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="importStudentModal" tabindex="-1" aria-labelledby="importStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="{{ route('admin.siswa.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <div class="panel-heading mb-0">
                        <span class="panel-icon"><i class="bi bi-file-earmark-spreadsheet"></i></span>
                        <div>
                            <h5 class="modal-title" id="importStudentModalLabel">Import Data Siswa</h5>
                            <p>Kolom: nis, nama, email, kelas, nama_orang_tua, no_hp_orang_tua, alamat, status.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <label for="studentExcel" class="upload-drop mb-3">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <span>Pilih file .xlsx, .xls, atau .csv</span>
                        <small>Data dengan NIS sama akan diperbarui.</small>
                    </label>
                    <input id="studentExcel" name="file" type="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary"><i class="bi bi-upload me-1"></i>Import Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="exportStudentModal" tabindex="-1" aria-labelledby="exportStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="panel-heading mb-0">
                    <span class="panel-icon panel-icon-export"><i class="bi bi-download"></i></span>
                    <div>
                        <h5 class="modal-title" id="exportStudentModalLabel">Export Data Siswa</h5>
                        <p>Unduh data siswa dalam format Excel untuk arsip atau pengolahan lanjutan.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="export-summary">
                    <div><span>Total data tampil</span><strong>{{ $siswa->count() }}</strong></div>
                    <div><span>Siswa aktif</span><strong>{{ $siswa->where('status', 'aktif')->count() }}</strong></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <a class="btn btn-success" href="{{ route('admin.siswa.export') }}"><i class="bi bi-download me-1"></i>Download Excel</a>
            </div>
        </div>
    </div>
</div>

<style>
    .student-page { position:relative; isolation:isolate; min-height:calc(100vh - 3rem); }
    .student-header { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1rem; }
    .student-kicker { display:inline-flex; align-items:center; margin-bottom:.35rem; color:#9a3412; font-size:.74rem; font-weight:760; letter-spacing:.08em; text-transform:uppercase; }
    .student-header h3 { margin:0; font-weight:800; color:#102027; }
    .student-header p { margin:.25rem 0 0; color:#5b6675; max-width:620px; }
    .student-header-actions { display:flex; gap:.75rem; flex-wrap:wrap; justify-content:flex-end; }
    .student-header-actions > .btn { white-space:nowrap; }
    .student-stat { min-width:118px; padding:.85rem 1rem; border:1px solid rgba(22,183,232,.22); background:rgba(255,255,255,.84); border-radius:8px; box-shadow:0 14px 36px rgba(16,24,40,.06); }
    .student-stat span { display:block; color:#667085; font-size:.78rem; }
    .student-stat strong { display:block; margin-top:.15rem; font-size:1.35rem; color:#162d78; line-height:1; }
    .student-grid { display:grid; grid-template-columns:minmax(260px,.9fr) minmax(320px,1.1fr); gap:1rem; margin-bottom:1rem; }
    .student-panel,.student-table-panel { background:rgba(255,255,255,.9); border:1px solid rgba(214,221,230,.86); border-radius:8px; box-shadow:0 18px 44px rgba(16,24,40,.07); backdrop-filter:blur(10px); }
    .student-panel { padding:1rem; }
    .student-panel-accent { border-color:rgba(22,183,232,.32); background:linear-gradient(180deg, rgba(255,255,255,.94), rgba(238,253,255,.92)); }
    .panel-heading { display:flex; gap:.75rem; align-items:flex-start; margin-bottom:.9rem; }
    .panel-heading h6,.table-toolbar h6 { margin:0; font-weight:780; color:#16202a; }
    .panel-heading p { margin:.2rem 0 0; color:#667085; font-size:.86rem; }
    .panel-icon { width:38px; height:38px; display:grid; place-items:center; flex:0 0 auto; border-radius:8px; background:#162d78; color:#fff; box-shadow:0 10px 24px rgba(22,183,232,.32); }
    .panel-icon-soft { background:#fff7ed; color:#b45309; box-shadow:none; border:1px solid #fed7aa; }
    .panel-icon-export { background:#dcfce7; color:#075f7f; box-shadow:none; border:1px solid #bbf7d0; }
    .upload-box { display:grid; gap:.75rem; }
    .upload-drop { border:1px dashed rgba(22,183,232,.5); background:rgba(238,253,255,.82); border-radius:8px; min-height:118px; padding:1rem; display:grid; place-items:center; text-align:center; color:#162d78; cursor:pointer; }
    .upload-drop i { font-size:1.8rem; }
    .upload-drop span { font-weight:720; color:#0e205a; }
    .upload-drop small { color:#667085; }
    .export-summary { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.75rem; }
    .export-summary div { padding:.85rem; border:1px solid rgba(214,221,230,.9); border-radius:8px; background:#f8fafc; }
    .export-summary span { display:block; color:#667085; font-size:.78rem; font-weight:720; }
    .export-summary strong { display:block; margin-top:.2rem; color:#162d78; font-size:1.35rem; line-height:1; }
    .student-table-panel { padding:1rem; overflow:visible; }
    .student-table-panel .table-responsive { overflow-x:auto; }
    .table-toolbar { display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:.85rem; }
    .bulk-actions { display:flex; flex-wrap:wrap; align-items:center; gap:.6rem; padding:.75rem; margin-bottom:.85rem; border:1px solid rgba(214,221,230,.88); border-radius:8px; background:rgba(248,250,252,.82); }
    .bulk-count { display:flex; align-items:center; gap:.45rem; color:#475467; font-size:.86rem; font-weight:650; margin-right:auto; }
    .bulk-count i { color:#162d78; }
    .bulk-form { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; }
    .bulk-form .form-select { width:auto; min-width:138px; }
    .student-meta { color:#667085; font-size:.82rem; margin-top:.15rem; }
    .student-search { position:relative; width:min(420px,100%); }
    .student-search i { position:absolute; left:.82rem; top:50%; transform:translateY(-50%); color:#667085; }
    .student-search .form-control { padding-left:2.35rem; background:#f8fafc; border-color:#dbe3ea; }
    .student-filters { display:flex; align-items:center; gap:.65rem; padding:.7rem .75rem; margin-bottom:.85rem; border:1px solid rgba(214,221,230,.88); border-radius:8px; background:rgba(255,255,255,.74); }
    .student-filters label { color:#475467; font-size:.84rem; font-weight:720; white-space:nowrap; }
    .student-filters .form-select { width:min(260px,100%); }
    .student-table { margin-bottom:0; }
    .student-table tbody tr { transition:background .18s ease; }
    .student-table tbody tr:hover { background:#f7fbfa; }
    .student-table td,.student-table th { padding:.8rem .75rem; }
    .student-table td:last-child,.student-table th:last-child { text-align:right; }
    .student-detail-modal { border:0; border-radius:8px; overflow:hidden; box-shadow:0 24px 70px rgba(15,23,42,.22); }
    .student-detail-hero { display:flex; align-items:center; gap:1rem; padding:1.15rem; color:#fff; background:linear-gradient(135deg,#162d78,#162d78 58%,#16b7e8); }
    .student-detail-hero p { margin:0; font-size:.74rem; font-weight:760; letter-spacing:.08em; text-transform:uppercase; opacity:.78; }
    .student-detail-hero h5 { margin:.15rem 0; font-weight:800; }
    .student-detail-hero span { color:rgba(255,255,255,.78); }
    .student-avatar { width:54px; height:54px; display:grid; place-items:center; border-radius:8px; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.24); font-size:1.45rem; font-weight:850; box-shadow:inset 0 1px 0 rgba(255,255,255,.22); }
    .detail-metrics { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.75rem; margin-bottom:1rem; }
    .detail-metrics div,.detail-card { border:1px solid rgba(214,221,230,.9); border-radius:8px; background:linear-gradient(180deg,rgba(255,255,255,.96),rgba(248,250,252,.92)); box-shadow:0 10px 28px rgba(16,24,40,.05); }
    .detail-metrics div { padding:.85rem; }
    .detail-metrics span { display:block; color:#667085; font-size:.78rem; }
    .detail-metrics strong { display:block; margin-top:.2rem; color:#102027; font-size:1.1rem; }
    .detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
    .detail-card { padding:1rem; }
    .detail-card h6 { margin:0 0 .75rem; font-weight:800; color:#102027; }
    .detail-card dl { display:grid; grid-template-columns:110px 1fr; gap:.45rem .75rem; margin:0; }
    .detail-card dt { color:#667085; font-size:.82rem; font-weight:650; }
    .detail-card dd { margin:0; color:#16202a; }
    .overdue-list { display:flex; flex-wrap:wrap; gap:.45rem; }
    .overdue-list span { display:inline-flex; align-items:center; padding:.38rem .58rem; border-radius:999px; color:#991b1b; background:#fee2e2; border:1px solid #fecaca; font-size:.82rem; font-weight:720; }
    .empty-mini { padding:.85rem; color:#667085; text-align:center; border:1px dashed #d0d5dd; border-radius:8px; background:#f8fafc; }
    @media (max-width: 991px) {
        .student-header,.table-toolbar { display:block; }
        .student-header-actions { justify-content:flex-start; margin-top:1rem; }
        .student-header-actions > .btn { flex:1 1 auto; }
        .student-grid { grid-template-columns:1fr; }
        .student-search { margin-top:.75rem; width:100%; }
        .student-filters { display:grid; grid-template-columns:1fr; }
        .student-filters .form-select { width:100%; }
        .bulk-actions,.bulk-form { display:grid; grid-template-columns:1fr; }
        .bulk-form .form-select,.bulk-actions .btn { width:100%; }
    }
    @media (max-width: 575px) {
        .student-stat { flex:1 1 0; min-width:0; }
        .export-summary { grid-template-columns:1fr; }
        .student-table td,.student-table th { white-space:nowrap; }
        .detail-metrics,.detail-grid { grid-template-columns:1fr; }
        .detail-card dl { grid-template-columns:1fr; }
    }

    .student-page {
        display:grid;
        gap:1rem;
    }
    .student-header {
        position:relative;
        overflow:hidden;
        align-items:center;
        padding:1.15rem;
        border:1px solid rgba(214,221,230,.9);
        border-radius:8px;
        background:
            linear-gradient(135deg, rgba(255,255,255,.96), rgba(238,253,255,.9)),
            radial-gradient(circle at top right, rgba(22,183,232,.18), transparent 34%);
        box-shadow:0 18px 44px rgba(16,24,40,.07);
    }
    .student-header::before {
        content:"";
        position:absolute;
        inset:0 auto 0 0;
        width:5px;
        background:linear-gradient(180deg,#16b7e8,#162d78);
    }
    .student-kicker {
        color:#075f7f;
        background:#eef8fc;
        border:1px solid #c9edf8;
        border-radius:999px;
        padding:.28rem .55rem;
        letter-spacing:.04em;
    }
    .student-header h3 {
        font-size:1.45rem;
        letter-spacing:0;
    }
    .student-header-actions {
        align-items:center;
    }
    .student-action-button {
        min-width:126px;
        box-shadow:0 10px 24px rgba(16,24,40,.06);
    }
    .student-stat {
        display:grid;
        place-content:center;
        min-height:72px;
        background:#fff;
    }
    .student-stat strong {
        font-size:1.45rem;
    }
    .student-table-panel {
        border-color:#d6edf6;
        background:rgba(255,255,255,.96);
    }
    .table-toolbar {
        padding:.15rem .1rem .85rem;
        border-bottom:1px solid #edf1f5;
    }
    .table-toolbar h6 {
        font-size:1rem;
    }
    .student-search .form-control {
        min-height:44px;
        border-radius:999px;
        background:#fff;
    }
    .student-filters {
        justify-content:space-between;
        background:#f8fbfc;
        border-color:#d6edf6;
    }
    .student-filters .form-select {
        border-radius:999px;
        background-color:#fff;
    }
    .bulk-actions {
        background:linear-gradient(180deg,#ffffff,#f8fbfc);
        border-color:#d6edf6;
    }
    .bulk-count {
        padding:.35rem .55rem;
        border-radius:999px;
        background:#eef8fc;
        color:#0e205a;
    }
    .student-table thead th {
        background:#f8fafc;
        color:#475467;
        font-size:.72rem;
        letter-spacing:.04em;
    }
    .student-table tbody tr.student-data-row {
        transition:transform .16s ease, box-shadow .16s ease, background .16s ease;
    }
    .student-table tbody tr.student-data-row:hover {
        background:#f8fbfc;
        box-shadow:inset 3px 0 0 #16b7e8;
    }
    .student-identity {
        display:flex;
        align-items:center;
        gap:.7rem;
        min-width:210px;
    }
    .student-mini-avatar {
        width:38px;
        height:38px;
        display:grid;
        place-items:center;
        flex:0 0 auto;
        border-radius:8px;
        color:#fff;
        background:linear-gradient(145deg,#16b7e8,#162d78);
        font-weight:850;
        box-shadow:0 8px 18px rgba(22,45,120,.16);
    }
    .student-identity strong {
        display:block;
        color:#102027;
        line-height:1.2;
    }
    .student-identity small {
        display:block;
        color:#667085;
        margin-top:.12rem;
    }
    .student-nis-pill,
    .student-class-chip {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        border-radius:999px;
        font-weight:760;
        white-space:nowrap;
    }
    .student-nis-pill {
        padding:.32rem .55rem;
        color:#0e205a;
        background:#eef8fc;
        border:1px solid #c9edf8;
    }
    .student-class-chip {
        min-width:58px;
        padding:.32rem .58rem;
        color:#075f7f;
        background:#eefdff;
        border:1px solid #c9edf8;
    }
    .student-muted-text {
        color:#667085;
    }
    .student-parent-contact {
        display:flex;
        align-items:center;
        gap:.45rem;
        flex-wrap:wrap;
    }
    .student-parent-contact span {
        color:#475467;
        font-weight:650;
    }
    .student-row-actions {
        display:inline-flex;
        align-items:center;
        gap:.35rem;
        padding:.25rem;
        border:1px solid #edf1f5;
        border-radius:8px;
        background:#fff;
    }
    .student-row-actions .btn {
        width:32px;
        min-height:32px;
        padding:0;
    }
    .student-row-actions form {
        display:inline-flex;
        margin:0;
    }
    .modal .panel-heading p {
        max-width:560px;
    }
    @media (max-width: 991px) {
        .student-header {
            align-items:flex-start;
        }
        .student-action-button {
            min-width:0;
        }
    }
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rows = document.getElementById('studentsRows');
    const selectAll = document.getElementById('selectAllStudents');
    const counter = document.getElementById('selectedStudentsCount');
    const forms = document.querySelectorAll('[data-bulk-form]');

    function selectedChecks() {
        return Array.from(document.querySelectorAll('.student-row-check:checked'));
    }

    function allChecks() {
        return Array.from(document.querySelectorAll('.student-row-check'));
    }

    function syncBulkState() {
        const checks = allChecks();
        const selected = selectedChecks();
        if (counter) counter.textContent = selected.length + ' siswa dipilih';
        if (selectAll) {
            selectAll.checked = checks.length > 0 && selected.length === checks.length;
            selectAll.indeterminate = selected.length > 0 && selected.length < checks.length;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            allChecks().forEach(function (checkbox) {
                checkbox.checked = selectAll.checked;
            });
            syncBulkState();
        });
    }

    document.addEventListener('change', function (event) {
        if (event.target.classList.contains('student-row-check')) {
            syncBulkState();
        }
    });

    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            form.querySelectorAll('[data-generated-bulk-id]').forEach(function (input) {
                input.remove();
            });

            const selected = selectedChecks();
            if (selected.length === 0) {
                event.preventDefault();
                alert('Pilih minimal satu siswa terlebih dahulu.');
                return;
            }

            const confirmation = form.dataset.confirm;
            if (confirmation && !confirm(confirmation)) {
                event.preventDefault();
                return;
            }

            selected.forEach(function (checkbox) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'siswa_ids[]';
                input.value = checkbox.value;
                input.dataset.generatedBulkId = 'true';
                form.appendChild(input);
            });
        });
    });

    if (rows) {
        new MutationObserver(function () {
            if (selectAll) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            }
            syncBulkState();
        }).observe(rows, { childList: true });
    }

    syncBulkState();
});
</script>
@endpush
