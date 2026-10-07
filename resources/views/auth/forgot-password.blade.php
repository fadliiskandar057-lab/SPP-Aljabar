@extends('layouts.app')
@section('content')
<style>
    .password-help-shell { min-height:100vh; display:grid; grid-template-columns:minmax(0,1fr) minmax(360px,.72fr); background:linear-gradient(135deg,#f5fcff 0%,#fff 52%,#eaf8fb 100%); }
    .password-help-aside { display:flex; flex-direction:column; justify-content:space-between; padding:clamp(1.5rem,5vw,4rem); color:#082f45; background:linear-gradient(135deg,rgba(255,255,255,.94),rgba(238,253,255,.8)),url("https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1500&q=80") center/cover; }
    .password-help-brand { display:inline-flex; align-items:center; gap:.7rem; width:max-content; max-width:100%; padding:.55rem .75rem; border:1px solid #c9edf8; border-radius:999px; background:#fff; box-shadow:0 12px 30px rgba(16,34,53,.08); }
    .password-help-brand img { width:40px; height:40px; object-fit:contain; }
    .password-help-brand strong,.password-help-brand span { display:block; line-height:1.25; }
    .password-help-brand span { color:#607485; font-size:.8rem; }
    .password-help-copy { max-width:640px; }
    .password-help-copy h1 { margin:0 0 .85rem; font-size:clamp(2.15rem,4.5vw,4rem); line-height:1.05; font-weight:900; }
    .password-help-copy h1 span { color:#e52632; }
    .password-help-copy p { margin:0; max-width:560px; color:#41586a; font-size:1.04rem; line-height:1.75; }
    .password-help-steps { display:grid; gap:.7rem; margin-top:1.6rem; }
    .password-help-step { display:flex; gap:.75rem; align-items:flex-start; padding:.85rem; border:1px solid #d9eaf0; border-radius:8px; background:rgba(255,255,255,.84); }
    .password-help-step i { color:#0794c9; font-size:1.2rem; }
    .password-help-step strong,.password-help-step span { display:block; }
    .password-help-step span { color:#607485; font-size:.84rem; line-height:1.4; }
    .password-help-form-zone { display:flex; align-items:center; justify-content:center; padding:clamp(1rem,4vw,3rem); }
    .password-help-card { width:min(460px,100%); overflow:hidden; border:1px solid #d9e8ef; border-radius:8px; background:#fff; box-shadow:0 24px 60px rgba(16,34,53,.12); }
    .password-help-head { padding:1.25rem; border-bottom:1px solid #d9e8ef; background:linear-gradient(135deg,#fff,#f0fbff); }
    .password-help-back { display:inline-flex; align-items:center; gap:.35rem; margin-bottom:1rem; color:#162d78; font-weight:800; text-decoration:none; }
    .password-help-back:hover { color:#e52632; }
    .password-help-badge { display:inline-flex; align-items:center; gap:.4rem; padding:.4rem .68rem; border-radius:999px; background:#eaf8fc; color:#0794c9; font-size:.75rem; font-weight:850; letter-spacing:.05em; text-transform:uppercase; }
    .password-help-head h2 { margin:.8rem 0 .35rem; color:#082f45; font-weight:900; }
    .password-help-head p { margin:0; color:#667085; line-height:1.6; }
    .password-help-body { padding:1.25rem; }
    .password-help-field { position:relative; }
    .password-help-field .form-label { color:#344b5c; font-weight:800; }
    .password-help-field i { position:absolute; top:2.42rem; left:.9rem; color:#78909c; pointer-events:none; }
    .password-help-field .form-control { min-height:48px; padding-left:2.55rem; border-color:#d6e5ea; border-radius:8px; }
    .password-help-field .form-control:focus { border-color:#0794c9; box-shadow:0 0 0 .2rem rgba(22,183,232,.14); }
    .password-help-submit { min-height:48px; border-color:#162d78; border-radius:8px; background:#162d78; font-weight:850; box-shadow:0 14px 28px rgba(22,45,120,.18); }
    .password-help-submit:hover { border-color:#0e205a; background:#0e205a; }
    .password-help-note { margin-top:1rem; padding:.9rem; border:1px solid #d9e8ef; border-radius:8px; background:#f8fcfd; color:#607485; line-height:1.55; }
    @media (max-width:991px) { .password-help-shell { grid-template-columns:1fr; } .password-help-aside { gap:3rem; } }
    @media (max-width:575px) { .password-help-aside,.password-help-form-zone { padding:1rem; } .password-help-head,.password-help-body { padding:1rem; } }
</style>
<div class="password-help-shell">
    <section class="password-help-aside">
        <div class="password-help-brand"><img src="{{ asset('images/logo-sekolah.svg') }}" alt="Logo MA Taruna Teknik Al Jabbar"><div><strong>MA Taruna Teknik Al Jabbar</strong><span>Portal SPP Digital</span></div></div>
        <div class="password-help-copy"><h1>Pulihkan akses <span>akun Anda.</span></h1><p>Masukkan username, NIS, atau email. Kami akan mengirim tautan pengaturan ulang ke email yang terdaftar pada akun tersebut.</p><div class="password-help-steps"><div class="password-help-step"><i class="bi bi-person-check"></i><div><strong>Cari akun</strong><span>Gunakan identitas yang biasa dipakai untuk masuk.</span></div></div><div class="password-help-step"><i class="bi bi-envelope-check"></i><div><strong>Cek email</strong><span>Buka tautan terbaru yang kami kirimkan ke alamat email akun.</span></div></div></div></div>
    </section>
    <section class="password-help-form-zone"><div class="password-help-card"><div class="password-help-head"><a class="password-help-back" href="{{ route('login') }}"><i class="bi bi-arrow-left"></i>Kembali ke masuk</a><div><span class="password-help-badge"><i class="bi bi-shield-lock"></i>Pemulihan akun</span><h2>Lupa kata sandi?</h2><p>Masukkan identitas akun untuk menerima tautan pengaturan ulang.</p></div></div><div class="password-help-body"><form method="post" action="{{ route('password.email') }}">@csrf<div class="password-help-field"><label class="form-label" for="login">Username, NIS, atau email</label><i class="bi bi-person"></i><input id="login" class="form-control @error('login') is-invalid @enderror" name="login" value="{{ old('login') }}" autocomplete="username" required autofocus aria-describedby="login-help @error('login') login-error @enderror"><div id="login-help" class="form-text">Jika email belum terdaftar pada akun, hubungi admin.</div>@error('login')<div id="login-error" class="invalid-feedback">{{ $message }}</div>@enderror</div><button class="btn btn-primary w-100 mt-3 password-help-submit" type="submit"><i class="bi bi-send me-1"></i>Kirim tautan reset</button></form><div class="password-help-note small"><i class="bi bi-info-circle me-1"></i>Gunakan tautan dari email yang paling baru dikirim. Tautan sebelumnya tidak berlaku lagi.</div></div></div></section>
</div>
@endsection
