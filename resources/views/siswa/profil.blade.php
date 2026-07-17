@extends('layouts.app')

@section('content')
<div class="role-page student-profile-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-person-circle"></i>Profil Siswa</span>
            <h3>{{ $siswa->nama }}</h3>
            <p>Data siswa, kontak orang tua/wali, ringkasan tagihan, dan pembayaran terakhir yang tercatat di sistem.</p>
        </div>
        <div class="role-hero-actions">
            <a href="{{ route('siswa.tagihan') }}" class="btn btn-primary"><i class="bi bi-receipt"></i>Lihat Tagihan</a>
            <a href="{{ route('siswa.riwayat') }}" class="btn btn-outline-primary"><i class="bi bi-clock-history"></i>Riwayat</a>
        </div>
    </section>

    <section class="role-stat-grid">
        <div class="role-stat-card">
            <span class="role-stat-icon"><i class="bi bi-journal-text"></i></span>
            <div><p class="role-stat-label">Total Tagihan</p><strong class="role-stat-value">{{ $summary['total_tagihan'] }}</strong></div>
        </div>
        <div class="role-stat-card is-success">
            <span class="role-stat-icon"><i class="bi bi-patch-check"></i></span>
            <div><p class="role-stat-label">Lunas</p><strong class="role-stat-value">{{ $summary['lunas'] }}</strong></div>
        </div>
        <div class="role-stat-card is-danger">
            <span class="role-stat-icon"><i class="bi bi-exclamation-circle"></i></span>
            <div><p class="role-stat-label">Belum Lunas</p><strong class="role-stat-value">{{ $summary['belum_lunas'] }}</strong></div>
        </div>
        <div class="role-stat-card is-warning">
            <span class="role-stat-icon"><i class="bi bi-cash-stack"></i></span>
            <div><p class="role-stat-label">Tunggakan</p><strong class="role-stat-value">Rp {{ number_format($summary['tunggakan'],0,',','.') }}</strong></div>
        </div>
    </section>

    <section class="student-profile-grid">
        <aside class="role-profile-card p-4 text-center student-profile-card">
            <div class="mx-auto mb-3 role-profile-avatar">{{ strtoupper(substr($siswa->nama,0,1)) }}</div>
            <h5 class="mb-1 fw-bold">{{ $siswa->nama }}</h5>
            <div class="text-muted">{{ $siswa->nis }} - {{ $siswa->kelas->nama_kelas ?? '-' }}</div>
            <div class="mt-3"><x-status :status="$siswa->status"/></div>
            <div class="profile-mini-list mt-4">
                <div><span>Orang Tua</span><strong>{{ $siswa->nama_orang_tua ?: '-' }}</strong></div>
                <div><span>WhatsApp</span><strong>{{ $siswa->no_hp_orang_tua ?: '-' }}</strong></div>
                <div><span>Alamat</span><strong>{{ $siswa->alamat ?: '-' }}</strong></div>
            </div>
        </aside>

        <div class="role-table-card student-profile-payments">
            <div class="role-card-head">
                <div>
                    <h6>Pembayaran Terakhir</h6>
                    <span>Riwayat terbaru yang terkait dengan akun siswa.</span>
                </div>
            </div>
            @include('partials.payments_table', ['payments' => $payments, 'tableId' => 'profilePaymentsTable'])
        </div>
    </section>
</div>

<style>
    .student-profile-grid { display:grid; grid-template-columns:minmax(260px,340px) minmax(0,1fr); gap:1rem; align-items:start; min-width:0; }
    .student-profile-card,.student-profile-payments { min-width:0; }
    .role-profile-avatar { width:104px; height:104px; display:grid; place-items:center; border-radius:8px; background:linear-gradient(145deg,#eefdff,#ffffff); border:1px solid #c9edf8; color:#162d78; font-size:2.2rem; font-weight:900; box-shadow:0 18px 36px rgba(22,45,120,.1); }
    .profile-mini-list { display:grid; gap:.65rem; text-align:left; }
    .profile-mini-list div { padding:.85rem; border:1px solid #edf1f5; border-radius:8px; background:#f8fcfd; }
    .profile-mini-list span { display:block; color:#667085; font-size:.78rem; font-weight:760; }
    .profile-mini-list strong { display:block; margin-top:.16rem; color:#102027; overflow-wrap:anywhere; }
    @media (max-width: 991px) {
        .student-profile-grid { grid-template-columns:1fr; }
    }
</style>
@endsection
