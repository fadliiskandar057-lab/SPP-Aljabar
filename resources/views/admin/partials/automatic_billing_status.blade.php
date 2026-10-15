@php
    $calendar = now();
    $nextRelease = $calendar->copy()->startOfMonth()->day($autoBillSetting->generate_day);
    if ($calendar->day >= $autoBillSetting->generate_day) $nextRelease->addMonthNoOverflow();
    if ($autoBillSetting->starts_on && $autoBillSetting->starts_on->gt($nextRelease)) $nextRelease = $autoBillSetting->starts_on->copy()->day($autoBillSetting->generate_day);
@endphp
<section class="content-card p-3 p-md-4 mb-3" aria-labelledby="automaticBillingStatus">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div><span class="role-kicker"><i class="bi bi-calendar2-check" aria-hidden="true"></i>Kalender Tagihan</span><h5 id="automaticBillingStatus" class="fw-bold mb-1">SPP mengikuti bulan berjalan</h5><p class="text-muted mb-0">{{ $calendar->locale('id')->translatedFormat('l, d F Y') }} · {{ config('app.timezone') }}</p></div>
        <span class="badge {{ $autoBillSetting->is_enabled ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-dark' }} p-2">{{ $autoBillSetting->is_enabled ? 'Otomatis aktif' : 'Otomatis nonaktif' }}</span>
    </div>
    <div class="row g-3">
        <div class="col-md-4"><span class="text-muted small d-block">Periode kalender saat ini</span><strong>{{ $calendar->translatedFormat('F Y') }}</strong><div class="small text-muted">Tahun ajaran {{ $activeYear?->nama ?? 'belum dipilih' }}</div></div>
        <div class="col-md-4"><span class="text-muted small d-block">Terbit berikutnya</span><strong>{{ $autoBillSetting->is_enabled ? $nextRelease->locale('id')->translatedFormat('d F Y') : 'Aktifkan jadwal dahulu' }}</strong><div class="small text-muted">Terbit tanggal {{ $autoBillSetting->generate_day }} · jatuh tempo tanggal {{ $autoBillSetting->due_day }}{{ $autoBillSetting->due_day < $autoBillSetting->generate_day ? ' bulan berikutnya' : '' }}</div></div>
        <div class="col-md-4"><span class="text-muted small d-block">Penerbitan terakhir</span><strong>{{ $autoBillSetting->last_generated_at?->format('d/m/Y H:i') ?? 'Belum ada penerbitan otomatis' }}</strong><div class="small text-muted">@if($autoBillSetting->last_generated_at){{ $autoBillSetting->last_created_count }} tagihan diterbitkan.@endif Pemeriksaan terakhir: {{ $autoBillSetting->last_checked_at?->format('d/m/Y H:i') ?? 'belum diperiksa' }}</div></div>
    </div>
    @if(!$autoBillSetting->is_enabled)<p class="alert alert-warning mt-3 mb-0">Jadwal sedang nonaktif. Aktifkan dan simpan jadwal di bawah agar tagihan bulan berjalan dapat muncul otomatis.</p>@endif
    @if(!$activeYear)<p class="alert alert-warning mt-3 mb-0">Pilih tahun ajaran aktif pada tab Tahun Ajaran terlebih dahulu.</p>@endif
    @if($autoBillSetting->last_missing_fee_count)<p class="alert alert-warning mt-3 mb-0">{{ $autoBillSetting->last_missing_fee_count }} tagihan belum dapat dibuat karena biaya SPP belum tersedia. Lengkapi tarif kelas / siswa; sistem akan memeriksa kembali.</p>@endif
    <p class="small text-muted mt-3 mb-0">Saat web dibuka, sistem mengejar periode terlewat sejak bulan mulai yang dipilih. Tagihan lama serta bulan yang sudah dibayar tidak dibuat ulang.</p>
</section>
