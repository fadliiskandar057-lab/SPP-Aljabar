<section class="role-table-card" id="semester-rates">
    <div class="role-card-head"><div><h6>Tarif SPP per Semester</h6><span>Ubah tarif tanpa menaikkan kelas. Tagihan lama dan pembayaran di muka tetap memakai nominal yang tercatat.</span></div></div>
    <form method="post" action="{{ route('admin.fee-rates.store') }}" class="row g-3 p-3" id="semesterRatesForm">
        @csrf
        <div class="col-md-6"><label for="rateClass" class="form-label">Pilih satu kelas sekaligus (opsional)</label><select id="rateClass" class="form-select mb-2"><option value="">Pilih siswa secara manual</option><option value="all">Semua siswa aktif</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->nama_kelas }}</option>@endforeach</select><label for="rateStudents" class="form-label">Siswa yang mendapat tarif baru</label><select name="student_ids[]" id="rateStudents" class="form-select" multiple size="6" required>@foreach($students as $student)<option value="{{ $student->id }}" data-class="{{ $student->kelas_id }}">{{ $student->nama }} — {{ $student->kelas->nama_kelas }} ({{ $student->nis }})</option>@endforeach</select><small class="text-muted">Gunakan Ctrl / Command untuk memilih beberapa siswa.</small></div>
        <div class="col-md-6"><div class="row g-3">
            <div class="col-sm-6"><label for="rateYear" class="form-label">Tahun ajaran</label><select name="tahun_ajaran_id" id="rateYear" class="form-select" required>@foreach($years as $year)<option value="{{ $year->id }}">{{ $year->nama }}</option>@endforeach</select></div>
            <div class="col-sm-6"><label for="rateSemester" class="form-label">Semester</label><select name="semester" id="rateSemester" class="form-select"><option value="ganjil">Ganjil / semester 1</option><option value="genap">Genap / semester 2</option></select></div>
            <div class="col-sm-6"><label for="rateMonth" class="form-label">Berlaku mulai bulan</label><input name="effective_month" id="rateMonth" type="month" class="form-control" value="{{ now()->format('Y-m') }}" required></div>
            <div class="col-sm-6"><label for="rateAmount" class="form-label">SPP per bulan (Rp)</label><input name="nominal" id="rateAmount" type="number" min="0" class="form-control" required><small class="text-muted">Isi 0 untuk gratis.</small></div>
            <div class="col-12"><button type="submit" class="btn btn-primary">Simpan Tarif Semester</button></div>
        </div></div>
    </form>
    <details class="p-3"><summary>Riwayat 30 pengaturan tarif terakhir</summary><div class="table-responsive mt-2"><table class="table mb-0"><thead><tr><th>Siswa</th><th>Tahun / semester</th><th>Mulai</th><th>SPP per bulan</th></tr></thead><tbody>@forelse($rates as $rate)<tr><td>{{ $rate->siswa->nama }}</td><td>{{ $rate->tahunAjaran->nama }} / {{ $rate->semester }}</td><td>{{ $rate->effective_from->format('m/Y') }}</td><td>Rp {{ number_format($rate->nominal, 0, ',', '.') }}</td></tr>@empty<tr><td colspan="4">Belum ada pengaturan tarif per siswa.</td></tr>@endforelse</tbody></table></div></details>
</section>
@push('scripts')
<script>
document.getElementById('rateClass').addEventListener('change', event => {
    for (const option of document.getElementById('rateStudents').options) {
        option.selected = event.target.value === 'all' || option.dataset.class === event.target.value;
    }
});
document.getElementById('semesterRatesForm').addEventListener('submit', event => {
    const form = event.currentTarget;
    const payload = { student_ids: [] };
    for (const [name, value] of new FormData(form)) {
        if (name === 'student_ids[]') payload.student_ids.push(value);
        else if (name !== '_token') payload[name] = value;
    }
    const input = document.createElement('input');
    input.type = 'hidden'; input.name = 'batch_payload'; input.value = JSON.stringify(payload);
    for (const element of form.elements) if (element.name && element.name !== '_token') element.disabled = true;
    form.appendChild(input);
});
</script>
@endpush
