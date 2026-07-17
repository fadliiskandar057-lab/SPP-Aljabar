@extends('layouts.app')

@section('content')
<style>
    :root {
        --tta-sky:#16b7e8;
        --tta-sky-dark:#0794c9;
        --tta-navy:#162d78;
        --tta-red:#e52632;
        --tta-gold:#f4df45;
        --tta-ink:#102235;
        --tta-line:#d9e8ef;
    }
    .tta-login-shell {
        min-height:100vh;
        display:grid;
        grid-template-columns:minmax(360px,.92fr) minmax(360px,.78fr);
        background:
            radial-gradient(circle at 12% 12%, rgba(22,183,232,.28), transparent 24rem),
            radial-gradient(circle at 86% 18%, rgba(244,223,69,.28), transparent 22rem),
            linear-gradient(135deg,#f5fcff 0%,#ffffff 50%,#eaf8fb 100%);
        color:var(--tta-ink);
    }
    .tta-login-visual {
        position:relative;
        display:flex;
        flex-direction:column;
        justify-content:space-between;
        min-height:100vh;
        padding:clamp(1.5rem,4vw,3rem);
        color:#082f45;
        background:
            radial-gradient(circle at 74% 20%, rgba(244,223,69,.45), transparent 15rem),
            linear-gradient(120deg,rgba(255,255,255,.96),rgba(238,253,255,.82)),
            url("https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1500&q=80") center/cover;
        overflow:hidden;
    }
    .tta-login-brand {
        display:inline-flex;
        align-items:center;
        gap:.85rem;
        width:max-content;
        max-width:100%;
        padding:.55rem .75rem;
        position:relative;
        z-index:1;
        border:1px solid #c9edf8;
        border-radius:999px;
        background:#fff;
        box-shadow:0 12px 30px rgba(16,34,53,.08);
    }
    .tta-login-brand img { width:44px; height:44px; object-fit:contain; filter:drop-shadow(0 10px 14px rgba(22,45,120,.16)); }
    .tta-login-brand strong { display:block; line-height:1.15; }
    .tta-login-brand span { display:block; color:#607485; font-size:.82rem; }
    .tta-login-copy { position:relative; z-index:1; max-width:720px; }
    .tta-login-copy h1 {
        margin:0 0 .85rem;
        font-size:clamp(2rem,4.6vw,4.3rem);
        line-height:1.03;
        font-weight:900;
        letter-spacing:0;
    }
    .tta-login-copy h1 span { color:var(--tta-red); }
    .tta-login-copy p { margin:0; max-width:620px; color:#41586a; line-height:1.78; font-size:1.05rem; }
    .tta-login-points {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:.75rem;
        margin-top:1.5rem;
    }
    .tta-login-point {
        padding:.9rem;
        border:1px solid #d9eaf0;
        border-radius:8px;
        background:rgba(255,255,255,.86);
        box-shadow:0 10px 26px rgba(16,34,53,.06);
    }
    .tta-login-point i { color:var(--tta-sky-dark); font-size:1.25rem; }
    .tta-login-point strong { display:block; margin-top:.45rem; }
    .tta-login-point span { display:block; margin-top:.15rem; color:#607485; font-size:.8rem; line-height:1.35; }
    .tta-login-form-zone {
        min-height:100vh;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:clamp(1.2rem,4vw,3rem);
    }
    .tta-login-card {
        width:min(460px,100%);
        border:1px solid var(--tta-line);
        border-radius:8px;
        background:#fff;
        box-shadow:0 24px 60px rgba(16,34,53,.12);
        overflow:hidden;
    }
    .tta-login-card-head {
        padding:1.25rem 1.25rem 1rem;
        border-bottom:1px solid var(--tta-line);
        background:linear-gradient(135deg,#ffffff,#f0fbff);
    }
    .tta-login-back {
        display:inline-flex;
        align-items:center;
        gap:.35rem;
        color:var(--tta-navy);
        font-weight:850;
        text-decoration:none;
        margin-bottom:1.1rem;
    }
    .tta-login-back:hover { color:var(--tta-red); }
    .tta-login-badge {
        display:inline-flex;
        align-items:center;
        gap:.45rem;
        padding:.42rem .7rem;
        border-radius:999px;
        background:#eaf8fc;
        color:var(--tta-sky-dark);
        font-size:.76rem;
        font-weight:900;
        text-transform:uppercase;
        letter-spacing:.06em;
    }
    .tta-login-card h2 { margin:.85rem 0 .35rem; color:#082f45; font-weight:900; letter-spacing:0; }
    .tta-login-card p { margin:0; color:#667085; line-height:1.65; }
    .tta-login-body { padding:1.25rem; }
    .tta-login-field { position:relative; margin-bottom:1rem; }
    .tta-login-field .form-label { color:#344b5c; font-weight:800; }
    .tta-login-field i {
        position:absolute;
        left:.9rem;
        top:2.42rem;
        color:#78909c;
        pointer-events:none;
    }
    .tta-login-field .form-control {
        min-height:48px;
        padding-left:2.55rem;
        border-color:#d6e5ea;
        border-radius:8px;
    }
    .tta-login-field .form-control:focus {
        border-color:var(--tta-sky-dark);
        box-shadow:0 0 0 .2rem rgba(22,183,232,.14);
    }
    .tta-login-submit {
        min-height:48px;
        border-radius:8px;
        background:var(--tta-navy);
        border-color:var(--tta-navy);
        font-weight:850;
        box-shadow:0 14px 28px rgba(22,45,120,.18);
    }
    .tta-login-submit:hover { background:#0e205a; border-color:#0e205a; }
    .tta-login-note {
        margin-top:1rem;
        padding:1rem;
        border:1px solid var(--tta-line);
        border-radius:8px;
        background:#f8fcfd;
        color:#607485;
        line-height:1.62;
    }
    @media (max-width: 991px) {
        .tta-login-shell { grid-template-columns:1fr; }
        .tta-login-visual,.tta-login-form-zone { min-height:auto; }
        .tta-login-visual { gap:4rem; }
    }
    @media (max-width: 575px) {
        .tta-login-visual,.tta-login-form-zone { padding:1rem; }
        .tta-login-points { grid-template-columns:1fr; }
        .tta-login-card-head,.tta-login-body { padding:1rem; }
    }
</style>

<div class="tta-login-shell">
    <section class="tta-login-visual">
        <div class="tta-login-brand">
            <img src="{{ asset('images/logo-sekolah.svg') }}" alt="Logo MA Taruna Teknik Al Jabbar">
            <div>
                <strong>MA Taruna Teknik Al Jabbar</strong>
                <span>Portal SPP Digital</span>
            </div>
        </div>

        <div class="tta-login-copy">
            <h1>Masuk ke portal <span>SPP Al Jabbar</span>.</h1>
            <p>Kelola tagihan, pembayaran tunai, riwayat transaksi, tunggakan, dan laporan bulanan melalui akses sesuai peran masing-masing.</p>
            <div class="tta-login-points">
                <div class="tta-login-point">
                    <i class="bi bi-person-badge"></i>
                    <strong>Siswa</strong>
                    <span>Tagihan dan riwayat pembayaran</span>
                </div>
                <div class="tta-login-point">
                    <i class="bi bi-cash-coin"></i>
                    <strong>Admin TU</strong>
                    <span>Data siswa dan transaksi</span>
                </div>
                <div class="tta-login-point">
                    <i class="bi bi-graph-up-arrow"></i>
                    <strong>Kepala</strong>
                    <span>Monitoring laporan sekolah</span>
                </div>
            </div>
        </div>
    </section>

    <section class="tta-login-form-zone">
        <div class="tta-login-card">
            <div class="tta-login-card-head">
                <a href="{{ route('landing') }}" class="tta-login-back"><i class="bi bi-arrow-left"></i>Kembali ke beranda</a>
                <div>
                    <span class="tta-login-badge"><i class="bi bi-lock"></i>Akses Portal</span>
                    <h2>Masuk Sistem</h2>
                    <p>Gunakan NIS untuk siswa/orang tua atau username untuk Admin TU dan Kepala Sekolah.</p>
                </div>
            </div>

            <div class="tta-login-body">
                <form method="post" action="{{ route('login.post') }}">
                    @csrf
                    <div class="tta-login-field">
                        <label class="form-label">Username / NIS</label>
                        <i class="bi bi-person"></i>
                        <input name="username" class="form-control" value="{{ old('username') }}" autocomplete="username" required>
                    </div>
                    <div class="tta-login-field">
                        <label class="form-label">Password</label>
                        <i class="bi bi-key"></i>
                        <input name="password" type="password" class="form-control" autocomplete="current-password" required>
                    </div>
                    <button class="btn btn-primary w-100 tta-login-submit" type="submit"><i class="bi bi-box-arrow-in-right"></i>Masuk Portal</button>
                </form>

                <div class="tta-login-note small">
                    Hak akses akan mengikuti akun yang digunakan saat login, sehingga tampilan siswa, Admin TU, dan Kepala Sekolah tetap terpisah.
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
