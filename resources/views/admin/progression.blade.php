@extends('layouts.app')

@section('content')
<style>
    .progression-mapping-table { min-width:760px; }
    .progression-students-table { min-width:1000px; }
</style>
<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-mortarboard" aria-hidden="true"></i>Administrasi Siswa</span>
            <h3>Kenaikan Kelas &amp; Kelulusan</h3>
            <p>Proses pergantian tahun ajaran, simpan riwayat kelas, dan tentukan SPP berikutnya.</p>
        </div>
        <div class="role-hero-actions"><a href="{{ route('admin.kelas') }}" class="btn btn-outline-primary">Atur Data Kelas</a><a href="#semester-rates" class="btn btn-primary">Tarif Semester</a></div>
    </section>
    <p class="alert alert-info mb-0">Angkatan masuk tetap sama saat naik kelas. Siswa lulus tetap tersimpan, dan tagihan serta pembayaran lama tetap berlaku.</p>
    @if($years->count() < 2 || $classes->isEmpty())
        <div class="role-filter p-4"><p>Siapkan kelas dan minimal dua tahun ajaran sebelum memproses kenaikan.</p><a href="{{ route('admin.settings') }}" class="btn btn-primary">Buka Pengaturan SPP</a></div>
    @else
    <form method="post" action="{{ route('admin.progression.preview') }}" id="progressionForm" class="d-grid gap-3">
        @csrf
        <section class="role-table-card">
            <div class="role-card-head"><div><h6>1. Pilih Tahun Ajaran</h6><span>Perubahan kelas berlaku saat konfirmasi. Tahun ajaran aktif dikelola terpisah di Pengaturan SPP.</span></div></div>
            <div class="row g-3 p-3">
                <div class="col-md-4"><label for="fromYear" class="form-label">Tahun ajaran asal</label><select name="from_year_id" id="fromYear" class="form-select" required>@foreach($years as $year)<option value="{{ $year->id }}" @selected((string) old('from_year_id', $years->firstWhere('is_active', true)?->id) === (string) $year->id)>{{ $year->nama }}</option>@endforeach</select></div>
                <div class="col-md-4"><label for="toYear" class="form-label">Tahun ajaran tujuan</label><select name="to_year_id" id="toYear" class="form-select" required><option value="">Pilih tahun tujuan</option>@foreach($years as $year)<option value="{{ $year->id }}" @selected((string) old('to_year_id') === (string) $year->id)>{{ $year->nama }}</option>@endforeach</select></div>
                <div class="col-md-4"><label for="effectiveMonth" class="form-label">Berlaku mulai bulan</label><input type="month" id="effectiveMonth" name="effective_month" class="form-control" max="{{ now()->format('Y-m') }}" value="{{ old('effective_month', now()->format('Y-m')) }}" required><small class="text-muted">Pilih bulan ini atau sebelumnya.</small></div>
            </div>
        </section>
        <section class="role-table-card">
            <div class="role-card-head"><div><h6>2. Tentukan Tujuan Setiap Kelas</h6><span>SPP kosong berarti pertahankan nominal sebelumnya per siswa. Nominal 0 berarti gratis.</span></div></div>
            <div class="table-responsive"><table class="table align-middle mb-0 progression-mapping-table"><thead><tr><th>Kelas asal</th><th>Tindakan</th><th>Kelas tujuan</th><th>SPP baru / bulan</th></tr></thead><tbody>
                @foreach($classes as $class)
                    @php
                        $candidates = $classes->filter(fn ($item) => $class->tingkat && $item->tingkat === $class->tingkat + 1 && $item->jenjang === $class->jenjang && $item->jurusan === $class->jurusan);
                        $suggested = $candidates->count() === 1 ? $candidates->first() : null;
                        $terminal = ['SD' => 6, 'SMP' => 9, 'SMA' => 12, 'SMK' => 12][$class->jenjang ?? ''] ?? null;
                        $defaultAction = $suggested ? 'naik' : ($terminal && $class->tingkat === $terminal ? 'lulus' : 'skip');
                    @endphp
                    <tr>
                        <td><strong>{{ $class->nama_kelas }}</strong><div class="small text-muted">{{ $class->jenjang ?? 'Jenjang belum diatur' }} @if($class->tingkat) · Tingkat {{ $class->tingkat }} @endif</div></td>
                        <td><select name="classes[{{ $class->id }}][action]" class="form-select" aria-label="Tindakan kelas {{ $class->nama_kelas }}">@foreach(['skip' => 'Lewati', 'naik' => 'Naik kelas', 'tetap' => 'Tetap / tinggal kelas', 'pindah' => 'Pindah kelas', 'lulus' => 'Lulus', 'keluar' => 'Keluar'] as $value => $label)<option value="{{ $value }}" @selected(old('classes.'.$class->id.'.action', $defaultAction) === $value)>{{ $label }}</option>@endforeach</select></td>
                        <td><select name="classes[{{ $class->id }}][target_class_id]" class="form-select" aria-label="Tujuan kelas {{ $class->nama_kelas }}"><option value="">Tidak perlu untuk tetap / lulus / keluar</option>@foreach($classes as $target)<option value="{{ $target->id }}" @selected((string) old('classes.'.$class->id.'.target_class_id', $suggested?->id) === (string) $target->id)>{{ $target->nama_kelas }}</option>@endforeach</select></td>
                        <td><input type="number" min="0" name="classes[{{ $class->id }}][nominal]" value="{{ old('classes.'.$class->id.'.nominal') }}" class="form-control" placeholder="Tetap sebelumnya" aria-label="SPP baru kelas {{ $class->nama_kelas }}"></td>
                    </tr>
                @endforeach
            </tbody></table></div>
        </section>
        <section class="role-table-card">
            <div class="role-card-head"><div><h6>3. Pilih Siswa &amp; Pengecualian</h6><span>Secara default mengikuti pemetaan kelas. Ubah tindakan siswa untuk tinggal kelas atau pindah jurusan.</span></div><label class="d-flex gap-2"><input type="checkbox" id="selectVisibleStudents" checked> Pilih semua yang sesuai tahun asal</label></div>
            <div class="table-responsive"><table class="table align-middle mb-0 progression-students-table"><thead><tr><th>Pilih</th><th>Siswa / angkatan</th><th>Kelas aktif</th><th>Pengecualian</th><th>Tujuan khusus</th><th>SPP khusus</th></tr></thead><tbody>
                @forelse($students as $student)
                <tr data-student-year="{{ $student->tahun_ajaran_id }}">
                    <td><input type="checkbox" class="student-choice" name="student_ids[]" value="{{ $student->id }}" @checked(in_array($student->id, old('student_ids', $students->pluck('id')->all()))) aria-label="Pilih {{ $student->nama }}"></td>
                    <td><strong>{{ $student->nama }}</strong><div class="small text-muted">{{ $student->nis }} · Angkatan {{ $student->angkatan ?? 'belum diisi' }}</div></td>
                    <td>{{ $student->kelas->nama_kelas }}<div class="small text-muted">{{ $student->tahunAjaran?->nama ?? 'Tahun asal belum diisi' }}</div></td>
                    <td><select name="exceptions[{{ $student->id }}][action]" class="form-select" aria-label="Pengecualian {{ $student->nama }}"><option value="">Ikuti kelas</option>@foreach(['skip' => 'Lewati siswa', 'naik' => 'Naik kelas', 'tetap' => 'Tinggal kelas', 'pindah' => 'Pindah kelas / jurusan', 'lulus' => 'Lulus', 'keluar' => 'Keluar'] as $value => $label)<option value="{{ $value }}" @selected(old('exceptions.'.$student->id.'.action') === $value)>{{ $label }}</option>@endforeach</select></td>
                    <td><select name="exceptions[{{ $student->id }}][target_class_id]" class="form-select" aria-label="Tujuan khusus {{ $student->nama }}"><option value="">Pilih jika naik / pindah</option>@foreach($classes as $target)<option value="{{ $target->id }}" @selected((string) old('exceptions.'.$student->id.'.target_class_id') === (string) $target->id)>{{ $target->nama_kelas }}</option>@endforeach</select></td>
                    <td><input type="number" min="0" name="exceptions[{{ $student->id }}][nominal]" value="{{ old('exceptions.'.$student->id.'.nominal') }}" class="form-control" placeholder="SPP sebelumnya" aria-label="SPP khusus {{ $student->nama }}"></td>
                </tr>
                @empty<tr><td colspan="6" class="empty-state">Belum ada siswa aktif.</td></tr>@endforelse
            </tbody></table></div>
            <div class="p-3 d-flex flex-wrap justify-content-between gap-3 align-items-center"><span class="text-muted small">Belum ada data yang berubah sebelum konfirmasi di halaman berikutnya.</span><button type="submit" class="btn btn-primary">Lihat Pratinjau Kenaikan</button></div>
        </section>
    </form>
    @endif
    @include('admin.partials.semester_rates')
    <section class="role-table-card">
        <div class="role-card-head"><div><h6>Riwayat Kenaikan &amp; Kelulusan</h6><span>Catatan kelas asal tidak berubah ketika siswa naik lagi.</span></div></div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Siswa</th><th>Tahun tujuan</th><th>Asal → tujuan</th><th>Tindakan</th><th>Efektif / pelaksana</th></tr></thead><tbody>
        @forelse($history as $entry)<tr><td>{{ $entry->siswa->nama }}</td><td>{{ $entry->targetYear->nama }}</td><td>{{ $entry->from_class_name }} → {{ $entry->to_class_name ?? ucfirst($entry->action) }}</td><td>{{ ucfirst($entry->action) }}</td><td>{{ $entry->effective_from->format('m/Y') }} · {{ $entry->processor?->name ?? '-' }}</td></tr>@empty<tr><td colspan="5" class="empty-state">Belum ada proses kenaikan kelas.</td></tr>@endforelse
        </tbody></table></div><div class="p-3">{{ $history->links() }}</div>
    </section>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const source = document.getElementById('fromYear');
    const all = document.getElementById('selectVisibleStudents');
    if (!source || !all) return;
    const rows = [...document.querySelectorAll('[data-student-year]')];
    const filter = () => {
        rows.forEach(row => {
            const included = !row.dataset.studentYear || row.dataset.studentYear === source.value;
            row.hidden = !included;
            const checkbox = row.querySelector('.student-choice');
            checkbox.disabled = !included;
        });
    };
    source.addEventListener('change', filter);
    all.addEventListener('change', () => rows.filter(row => !row.hidden).forEach(row => row.querySelector('.student-choice').checked = all.checked));
    filter();
    document.getElementById('progressionForm').addEventListener('submit', event => {
        const form = event.currentTarget;
        const payload = { classes: {}, exceptions: {}, student_ids: [] };
        for (const [name, value] of new FormData(form)) {
            if (name === 'student_ids[]') payload.student_ids.push(value);
            else {
                const match = name.match(/^(classes|exceptions)\[(\d+)\]\[(\w+)\]$/);
                if (match) {
                    payload[match[1]][match[2]] ??= {};
                    payload[match[1]][match[2]][match[3]] = value;
                } else if (name !== '_token') payload[name] = value;
            }
        }
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = 'batch_payload'; input.value = JSON.stringify(payload);
        for (const element of form.elements) if (element.name && element.name !== '_token') element.disabled = true;
        form.appendChild(input);
    });
})();
</script>
@endpush
