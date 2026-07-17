<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'SPP Al Jabbar' }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-sekolah.svg') }}">
    <link rel="shortcut icon" type="image/svg+xml" href="{{ asset('images/logo-sekolah.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-sekolah.svg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --ink:#102235; --muted:#667085; --line:#dbe7ee; --brand:#162d78; --brand-dark:#0e205a; --brand-sky:#16b7e8; --brand-sky-dark:#0794c9; --accent:#e52632; --gold:#f4df45; --soft:#f6fbfd; --nav:#162d78; --nav-2:#0794c9; --bs-primary:#162d78; --bs-success:#0794c9; --bs-warning:#f4df45; --bs-danger:#e52632; }
        html,body { max-width:100%; overflow-x:hidden; }
        body { background:linear-gradient(180deg,#f5fcff 0%,#eef8fc 52%,#f8fbff 100%); color:var(--ink); font-family:Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; overflow-x:hidden; }
        .global-three-wrap { position:fixed; right:0; bottom:0; width:min(58vw,760px); height:min(62vh,560px); z-index:0; pointer-events:none; opacity:.26; mask-image:linear-gradient(90deg,transparent 0%,#000 22%,#000 100%); }
        .global-three-wrap::before { content:""; position:absolute; inset:0; background:linear-gradient(135deg,rgba(22,183,232,.16),rgba(229,38,50,.08)); filter:blur(2px); }
        #globalThreeBg { position:absolute; inset:0; width:100%; height:100%; }
        .app-shell { min-height:100vh; position:relative; max-width:100%; overflow-x:hidden; }
        .sidebar { min-height:100vh; background:#162d78; position:sticky; top:0; box-shadow:18px 0 44px rgba(15,23,42,.12); z-index:20; overflow:hidden auto; }
        .sidebar::before { content:""; position:absolute; inset:0; background:linear-gradient(180deg,#162d78 0%,#0e205a 58%,#08394f 100%); pointer-events:none; }
        .brand-lockup { display:flex; gap:.75rem; align-items:center; color:#fff; position:relative; }
        .brand-mark,.school-mark { width:44px; height:44px; display:grid; place-items:center; border-radius:8px; background:#fff; color:var(--brand); font-weight:850; box-shadow:0 10px 28px rgba(0,0,0,.16); }
        .brand-mark img,.school-mark img { width:82%; height:82%; object-fit:contain; }
        .brand-copy strong { display:block; line-height:1.1; }
        .brand-copy span { color:rgba(255,255,255,.68); font-size:.78rem; }
        .nav-user { position:relative; color:#e8fffb; background:rgba(255,255,255,.09); border:1px solid rgba(255,255,255,.12); border-radius:8px; padding:.75rem; margin-bottom:.9rem; }
        .nav-user strong { display:block; font-size:.9rem; }
        .nav-user span { color:rgba(255,255,255,.68); font-size:.78rem; text-transform:capitalize; }
        .nav-section-label { position:relative; color:rgba(255,255,255,.5); font-size:.68rem; font-weight:760; letter-spacing:.08em; text-transform:uppercase; margin:.85rem .55rem .4rem; }
        .sidebar-nav { position:relative; display:grid; gap:.22rem; }
        .sidebar a { position:relative; color:#d7f4ef; text-decoration:none; display:flex; gap:.65rem; align-items:center; padding:.72rem .82rem; border-radius:8px; font-size:.92rem; font-weight:620; border:1px solid transparent; transition:background .18s ease, color .18s ease, transform .18s ease, border-color .18s ease; }
        .sidebar a i { width:1.1rem; text-align:center; font-size:1rem; }
        .sidebar a:hover { background:rgba(255,255,255,.11); color:#fff; transform:translateX(2px); }
        .sidebar a.active { background:rgba(255,255,255,.15); border-color:rgba(255,255,255,.24); color:#fff; box-shadow:inset 3px 0 0 var(--gold), 0 10px 24px rgba(0,0,0,.1); }
        .sidebar a.active i { color:var(--gold); }
        .app-main { min-height:100vh; padding:0 !important; min-width:0; max-width:100%; }
        .topbar { position:sticky; top:0; z-index:15; display:flex; align-items:center; justify-content:space-between; gap:1rem; min-height:74px; padding:1rem 1.5rem; background:rgba(255,255,255,.88); border-bottom:1px solid rgba(214,221,230,.9); backdrop-filter:blur(14px); box-shadow:0 8px 26px rgba(16,24,40,.05); }
        .topbar-title { min-width:0; display:flex; align-items:center; gap:.85rem; }
        .topbar-title > div:last-child { min-width:0; }
        .date-orbit { position:relative; width:46px; height:46px; flex:0 0 auto; border-radius:8px; background:linear-gradient(145deg,var(--brand-sky),var(--brand)); box-shadow:0 16px 30px rgba(22,45,120,.22); overflow:hidden; transform-style:preserve-3d; animation:dateFloat 4.2s ease-in-out infinite; }
        .date-orbit::before,.date-orbit::after { content:""; position:absolute; border-radius:999px; background:rgba(255,255,255,.55); filter:blur(.1px); }
        .date-orbit::before { width:16px; height:16px; left:8px; top:9px; animation:bubbleMove 3.8s ease-in-out infinite; }
        .date-orbit::after { width:9px; height:9px; right:9px; top:12px; animation:bubbleMove 3.8s ease-in-out infinite reverse; }
        .date-bars { position:absolute; left:10px; right:10px; bottom:9px; height:22px; display:flex; align-items:end; justify-content:space-between; }
        .date-bars i { display:block; width:5px; border-radius:999px 999px 2px 2px; background:rgba(255,255,255,.88); box-shadow:0 3px 8px rgba(0,0,0,.12); animation:barPulse 1.8s ease-in-out infinite; }
        .date-bars i:nth-child(1) { height:10px; animation-delay:.1s; }
        .date-bars i:nth-child(2) { height:17px; animation-delay:.25s; }
        .date-bars i:nth-child(3) { height:13px; animation-delay:.4s; }
        .date-bars i:nth-child(4) { height:21px; animation-delay:.55s; }
        .topbar-title span { display:block; color:var(--muted); font-size:.78rem; font-weight:650; }
        .topbar-title strong { display:block; color:#102027; font-size:1.05rem; line-height:1.2; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .topbar-actions { display:flex; align-items:center; gap:.6rem; flex:0 0 auto; }
        .avatar-menu { width:42px; height:42px; border:1px solid rgba(22,45,120,.24); border-radius:999px; display:grid; place-items:center; padding:0; color:var(--brand); background:linear-gradient(145deg,#ffffff,#eefdff); box-shadow:0 10px 24px rgba(16,24,40,.08); }
        .avatar-menu:hover,.avatar-menu.show { color:#fff; background:linear-gradient(145deg,var(--brand-sky),var(--brand)); border-color:var(--brand); }
        .avatar-menu i { font-size:1.35rem; }
        .notification-menu-btn { position:relative; }
        .notification-dot { position:absolute; right:-2px; top:-2px; min-width:19px; height:19px; padding:0 .3rem; border-radius:999px; display:grid; place-items:center; background:var(--accent); color:#fff; font-size:.68rem; font-weight:800; border:2px solid #fff; }
        .notification-dropdown { border:1px solid rgba(214,221,230,.95); border-radius:8px; box-shadow:0 18px 44px rgba(16,24,40,.13); overflow:hidden; min-width:min(360px,92vw); padding:0; }
        .notification-head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.8rem .95rem; background:#f8fafc; border-bottom:1px solid var(--line); }
        .notification-head strong { display:block; line-height:1.2; }
        .notification-head span { color:var(--muted); font-size:.76rem; }
        .notification-mini-list { max-height:360px; overflow:auto; }
        .notification-mini-item,.notification-row button { width:100%; display:flex; gap:.7rem; align-items:flex-start; text-align:left; color:inherit; background:#fff; border:0; border-bottom:1px solid #edf1f5; padding:.78rem .9rem; text-decoration:none; }
        .notification-mini-item:hover,.notification-row button:hover { background:#f6fbfb; }
        .notification-mini-item.is-unread,.notification-row.is-unread button { background:#eefdff; }
        .notification-icon { width:34px; height:34px; border-radius:8px; display:grid; place-items:center; flex:0 0 auto; color:#fff; background:var(--brand); }
        .notification-success { background:var(--brand-sky-dark); }
        .notification-warning { background:#b45309; }
        .notification-danger { background:var(--accent); }
        .notification-copy { min-width:0; display:grid; gap:.12rem; }
        .notification-copy strong { font-size:.86rem; line-height:1.2; }
        .notification-copy small { color:var(--muted); line-height:1.35; }
        .notification-copy em { color:#98a2b3; font-size:.72rem; font-style:normal; }
        .notification-footer { padding:.65rem .9rem; background:#fff; }
        .notification-list { display:grid; border:1px solid #edf1f5; border-radius:8px; overflow:hidden; }
        .notification-row { margin:0; }
        .app-toast-stack { position:fixed; right:1rem; top:5rem; z-index:2000; display:grid; gap:.65rem; width:min(380px,calc(100vw - 2rem)); pointer-events:none; }
        .app-toast { pointer-events:auto; display:flex; gap:.75rem; align-items:flex-start; padding:.85rem .95rem; border-radius:8px; background:#fff; border:1px solid rgba(214,221,230,.95); box-shadow:0 18px 44px rgba(16,24,40,.16); animation:toastIn .22s ease-out both; }
        .app-toast i { width:34px; height:34px; border-radius:8px; display:grid; place-items:center; flex:0 0 auto; color:#fff; background:var(--brand-sky-dark); }
        .app-toast-danger i { background:var(--accent); }
        .app-toast strong { display:block; font-size:.9rem; line-height:1.2; }
        .app-toast span { display:block; color:var(--muted); margin-top:.12rem; line-height:1.35; }
        .app-toast button { margin-left:auto; border:0; background:transparent; color:#98a2b3; padding:0; line-height:1; }
        .app-toast button i { width:auto; height:auto; border-radius:0; display:inline; color:inherit; background:transparent; }
        @keyframes toastIn {
            from { opacity:0; transform:translateY(-8px) scale(.98); }
            to { opacity:1; transform:translateY(0) scale(1); }
        }
        .profile-dropdown { border:1px solid rgba(214,221,230,.95); border-radius:8px; box-shadow:0 18px 44px rgba(16,24,40,.13); overflow:hidden; min-width:230px; }
        .profile-summary { padding:.85rem 1rem; background:#f8fafc; border-bottom:1px solid var(--line); }
        .profile-summary strong { display:block; color:#102027; }
        .profile-summary span { display:block; color:var(--muted); font-size:.78rem; text-transform:capitalize; }
        .modal { z-index:1085; }
        .modal-backdrop { z-index:1080; }
        @keyframes dateFloat {
            0%,100% { transform:translateY(0) rotateX(0deg) rotateY(0deg); }
            50% { transform:translateY(-3px) rotateX(8deg) rotateY(-10deg); }
        }
        @keyframes bubbleMove {
            0%,100% { transform:translate3d(0,0,0) scale(1); opacity:.56; }
            50% { transform:translate3d(7px,5px,8px) scale(1.18); opacity:.82; }
        }
        @keyframes barPulse {
            0%,100% { transform:scaleY(.72); opacity:.72; }
            50% { transform:scaleY(1); opacity:1; }
        }
        .mobile-menu-btn { display:none !important; width:40px; height:40px; padding:0; align-items:center; justify-content:center; }
        .content-area { padding:1.5rem; min-width:0; max-width:100%; }
        .public-navbar { position:sticky; top:0; z-index:30; display:flex; align-items:center; justify-content:space-between; gap:1rem; min-height:72px; padding:.85rem min(8vw,6rem); background:rgba(255,255,255,.92); border-bottom:1px solid rgba(214,221,230,.9); backdrop-filter:blur(16px); box-shadow:0 10px 30px rgba(16,24,40,.06); }
        .public-brand { min-width:0; display:flex; gap:.7rem; align-items:center; color:#102027; text-decoration:none; font-weight:820; letter-spacing:0; }
        .public-brand:hover { color:var(--brand); }
        .public-brand .brand-mark { width:42px; height:42px; flex:0 0 auto; background:var(--brand); color:#fff; box-shadow:0 10px 24px rgba(22,45,120,.18); }
        .brand-logo { width:42px; height:42px; flex:0 0 auto; object-fit:contain; filter:drop-shadow(0 8px 14px rgba(6,107,146,.18)); }
        .public-brand span:last-child { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .public-links { display:flex; align-items:center; justify-content:flex-end; gap:.35rem; margin-left:auto; }
        .public-login { min-height:38px; padding:.5rem .9rem; box-shadow:0 10px 22px rgba(22,45,120,.16); }
        .content-card { background:rgba(255,255,255,.9); border:1px solid rgba(230,234,240,.9); border-radius:8px; box-shadow:0 8px 22px rgba(16,24,40,.04); backdrop-filter:blur(10px); }
        .page-title { display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; margin-bottom:1rem; }
        .page-title h3 { margin:0; font-weight:750; }
        .page-title p { color:var(--muted); margin:.25rem 0 0; }
        .metric-card { background:rgba(255,255,255,.88); border:1px solid rgba(230,234,240,.9); border-radius:8px; padding:1rem; min-height:96px; backdrop-filter:blur(10px); }
        .metric-label { color:var(--muted); font-size:.82rem; }
        .metric-value { font-size:1.45rem; font-weight:760; margin-top:.35rem; }
        .table { --bs-table-hover-bg:#f6fbfb; }
        .table thead th { color:#475467; font-size:.78rem; text-transform:uppercase; letter-spacing:.02em; border-bottom:1px solid var(--line); }
        .table td { vertical-align:middle; }
        .form-control,.form-select,.btn { border-radius:6px; }
        .btn-primary { background:var(--brand); border-color:var(--brand); }
        .btn-primary:hover,.btn-primary:focus { background:var(--brand-dark); border-color:var(--brand-dark); }
        .btn-success { background:var(--brand-sky-dark); border-color:var(--brand-sky-dark); color:#fff; }
        .btn-success:hover,.btn-success:focus { background:var(--brand); border-color:var(--brand); color:#fff; }
        .btn-warning { background:var(--gold); border-color:var(--gold); color:#16202a; }
        .btn-danger { background:var(--accent); border-color:var(--accent); }
        .live-search-count { color:var(--muted); font-size:.82rem; }
        .empty-state { text-align:center; color:var(--muted); padding:2rem 1rem; }
        .landing-hero { min-height:82vh; position:relative; display:flex; align-items:center; padding:5rem min(8vw,6rem); background:linear-gradient(rgba(7,28,26,.42),rgba(7,28,26,.68)), url("https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1800&q=80") center/cover; color:#fff; }
        .landing-hero__content { max-width:760px; position:relative; z-index:1; }
        .landing-hero h1 { font-size:clamp(2.4rem,5vw,4.9rem); line-height:1; font-weight:800; margin:.75rem 0 1rem; }
        .landing-hero .lead { max-width:680px; color:#eef7f5; }
        .eyebrow { text-transform:uppercase; letter-spacing:.08em; font-weight:700; font-size:.78rem; margin:0; }
        .page-section { padding:3rem min(8vw,6rem); }
        .section-heading { display:flex; justify-content:space-between; gap:2rem; align-items:end; margin-bottom:1.25rem; }
        .section-heading h2 { margin:.25rem 0 0; font-weight:760; }
        .section-caption { max-width:440px; color:var(--muted); margin:0; }
        .feature-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1rem; }
        .feature-item { background:rgba(255,255,255,.9); border:1px solid rgba(230,234,240,.9); border-radius:8px; padding:1.1rem; display:flex; flex-direction:column; gap:.55rem; min-height:150px; backdrop-filter:blur(10px); }
        .feature-item span { color:var(--muted); }
        .status-lunas,.status-success,.status-settlement,.status-gratis { background:var(--brand-sky-dark); }
        .status-aktif { background:var(--brand); }
        .status-lulus { background:#6f42c1; }
        .status-belum_lunas,.status-failed { background:var(--accent); }
        .status-menunggu_konfirmasi,.status-pending { background:var(--gold); color:#212529; }
        .status-gagal,.status-expired,.status-cancelled,.status-keluar { background:#6c757d; }
        /* Interface refresh shared by all application pages. */
        :root { --surface:#ffffff; --surface-2:#f8fbfc; --brand-2:#16b7e8; --warning:#b45309; --danger:#b42318; --success:#0794c9; --shadow-sm:0 8px 24px rgba(16,24,40,.06); --shadow-md:0 18px 45px rgba(16,24,40,.1); }
        body { background:#f5f8fb; }
        body::before { content:""; position:fixed; inset:0; z-index:-1; pointer-events:none; background:linear-gradient(180deg,#f5fcff 0%,#eef8fc 52%,#f8fbff 100%); }
        .row { --bs-gutter-x:0; }
        .content-area { position:relative; z-index:1; padding:1.6rem; }
        .content-area > * { min-width:0; }
        .sidebar { background:#162d78; box-shadow:16px 0 38px rgba(22,45,120,.2); }
        .sidebar::before { background:linear-gradient(180deg,#162d78 0%,#0e205a 52%,#08394f 100%); }
        .brand-lockup { padding:.1rem .15rem .85rem; border-bottom:1px solid rgba(255,255,255,.12); margin-bottom:1rem !important; }
        .brand-mark,.school-mark { border-radius:8px; }
        .sidebar-nav { gap:.3rem; }
        .sidebar a { min-height:42px; padding:.68rem .78rem; }
        .topbar { min-height:70px; background:rgba(255,255,255,.93); }
        .date-orbit { border-radius:8px; background:linear-gradient(145deg,var(--brand-sky),var(--brand)); }
        .content-card,.metric-card,.feature-item { border:1px solid rgba(214,221,230,.92); background:rgba(255,255,255,.94); box-shadow:var(--shadow-sm); }
        .content-card:hover,.metric-card:hover,.feature-item:hover { border-color:rgba(22,183,232,.32); }
        .page-title { padding:1.05rem 1.15rem; border:1px solid rgba(214,221,230,.92); background:rgba(255,255,255,.88); border-radius:8px; box-shadow:var(--shadow-sm); align-items:center; }
        .page-title h3 { font-size:1.22rem; letter-spacing:0; }
        .page-title p { max-width:720px; font-size:.92rem; }
        .metric-card { position:relative; overflow:hidden; min-height:112px; padding:1.05rem; transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
        .metric-card::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:linear-gradient(180deg,var(--brand-sky),var(--brand)); }
        .metric-card::after { content:""; position:absolute; right:1rem; top:1rem; width:34px; height:34px; border-radius:8px; background:#eefdff; border:1px solid #c9edf8; }
        .metric-card:hover { transform:translateY(-2px); box-shadow:var(--shadow-md); }
        .metric-label { font-weight:740; text-transform:uppercase; letter-spacing:.04em; font-size:.72rem; }
        .metric-value { color:#102027; font-size:clamp(1.22rem,2.1vw,1.65rem); line-height:1.15; overflow-wrap:anywhere; }
        .table-responsive { border-radius:8px; border:1px solid rgba(230,234,240,.85); background:#fff; }
        .table { margin-bottom:0; --bs-table-bg:transparent; --bs-table-striped-bg:#f8fbfc; --bs-table-hover-bg:#eefdff; }
        .table > :not(caption) > * > * { padding:.78rem .8rem; border-bottom-color:#edf1f5; }
        .table thead th { background:#f8fafc; color:#475467; font-size:.72rem; font-weight:820; }
        .table tbody tr:last-child td { border-bottom:0; }
        .form-label { color:#475467; font-size:.8rem; font-weight:720; }
        .form-control,.form-select { min-height:42px; border-color:#d6dde6; background-color:#fff; box-shadow:none; }
        .form-control:focus,.form-select:focus { border-color:var(--brand-sky-dark); box-shadow:0 0 0 .2rem rgba(22,183,232,.15); }
        .form-control-sm,.form-select-sm { min-height:34px; }
        .role-page { display:grid; gap:1rem; min-width:0; }
        .role-hero { position:relative; overflow:hidden; display:flex; align-items:center; justify-content:space-between; gap:1rem; min-height:148px; padding:1.25rem; border:1px solid rgba(201,237,248,.95); border-radius:8px; background:radial-gradient(circle at 92% 12%, rgba(244,223,69,.34), transparent 16rem), radial-gradient(circle at 8% 14%, rgba(22,183,232,.24), transparent 18rem), linear-gradient(135deg,#ffffff 0%,#f1fbff 100%); box-shadow:var(--shadow-sm); }
        .role-hero::before { content:""; position:absolute; inset:0 0 auto; height:4px; background:linear-gradient(90deg,var(--brand),var(--brand-sky),var(--accent),var(--gold)); }
        .role-hero-copy { position:relative; z-index:1; min-width:0; }
        .role-kicker { display:inline-flex; align-items:center; gap:.45rem; margin-bottom:.55rem; padding:.32rem .62rem; border-radius:999px; background:#eefdff; color:var(--brand-sky-dark); font-size:.74rem; font-weight:850; text-transform:uppercase; letter-spacing:.05em; }
        .role-hero h3 { margin:0; color:#082f45; font-size:clamp(1.45rem,2.4vw,2.15rem); font-weight:900; letter-spacing:0; }
        .role-hero p { max-width:760px; margin:.4rem 0 0; color:#607485; line-height:1.6; }
        .role-hero-actions { position:relative; z-index:1; display:flex; flex-wrap:wrap; justify-content:flex-end; gap:.55rem; flex:0 0 auto; }
        .role-icon-tile { width:58px; height:58px; display:grid; place-items:center; border-radius:8px; background:linear-gradient(145deg,var(--brand-sky),var(--brand)); color:#fff; font-size:1.7rem; box-shadow:0 18px 32px rgba(22,45,120,.18); }
        .role-filter,.role-table-card,.role-profile-card { min-width:0; border:1px solid rgba(201,237,248,.95); border-radius:8px; background:rgba(255,255,255,.96); box-shadow:var(--shadow-sm); }
        .role-card-head { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.8rem; padding:1rem 1rem .85rem; border-bottom:1px solid #edf1f5; }
        .role-card-head h6 { margin:0; color:#102027; font-weight:850; }
        .role-card-head p,.role-card-head span { margin:.18rem 0 0; color:var(--muted); }
        .role-search { position:relative; width:min(360px,100%); }
        .role-search i { position:absolute; left:.85rem; top:50%; transform:translateY(-50%); color:#78909c; }
        .role-search .form-control { padding-left:2.35rem; border-radius:999px; background:#f8fcfd; }
        .role-chip { display:inline-flex; align-items:center; gap:.35rem; padding:.28rem .58rem; border-radius:999px; background:#eef8fc; color:#0e205a; font-size:.82rem; font-weight:760; }
        .role-money { color:#0794c9; font-weight:850; white-space:nowrap; }
        .role-money-danger { color:var(--accent); font-weight:850; white-space:nowrap; }
        .role-student { display:flex; align-items:center; gap:.7rem; min-width:0; }
        .role-avatar { width:40px; height:40px; flex:0 0 auto; display:grid; place-items:center; border-radius:8px; background:#eefdff; color:var(--brand); font-weight:900; }
        .role-student strong { display:block; color:#102027; line-height:1.2; }
        .role-student span { display:block; color:var(--muted); font-size:.8rem; margin-top:.12rem; }
        .role-table-card .table-responsive { border:0; border-radius:0; }
        .role-table-card .table thead th { background:#f8fcfd; }
        .role-table-card .table tbody tr { transition:background .18s ease, transform .18s ease; }
        .role-table-card .table tbody tr:hover { transform:translateY(-1px); }
        .role-stat-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1rem; min-width:0; }
        .role-stat-card { position:relative; overflow:hidden; display:flex; align-items:center; gap:.85rem; min-height:118px; padding:1rem; border:1px solid rgba(214,221,230,.92); border-radius:8px; background:#fff; box-shadow:var(--shadow-sm); transition:transform .18s ease, box-shadow .18s ease; }
        .role-stat-card:hover { transform:translateY(-2px); box-shadow:var(--shadow-md); }
        .role-stat-card::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:linear-gradient(180deg,var(--brand-sky),var(--brand)); }
        .role-stat-icon { width:48px; height:48px; flex:0 0 auto; display:grid; place-items:center; border-radius:8px; color:#fff; background:linear-gradient(145deg,var(--brand-sky),var(--brand)); box-shadow:0 14px 28px rgba(22,45,120,.16); }
        .role-stat-card.is-danger .role-stat-icon { background:linear-gradient(145deg,#fb7185,var(--accent)); }
        .role-stat-card.is-warning .role-stat-icon { background:linear-gradient(145deg,#f4df45,#b45309); }
        .role-stat-card.is-success .role-stat-icon { background:linear-gradient(145deg,#16b7e8,#0794c9); }
        .role-stat-label { margin:0; color:var(--muted); font-size:.74rem; font-weight:840; text-transform:uppercase; letter-spacing:.04em; }
        .role-stat-value { display:block; margin-top:.24rem; color:#102027; font-size:clamp(1.25rem,2vw,1.65rem); font-weight:900; line-height:1.1; overflow-wrap:anywhere; }
        .role-dashboard-grid { display:grid; grid-template-columns:minmax(0,1.12fr) minmax(0,.88fr); gap:1rem; align-items:start; min-width:0; }
        .role-chart-box { min-height:340px; position:relative; }
        .student-header,
        .spp-header,
        .arrears-page-title,
        .cash-hero,
        .payments-hero,
        .seq-hero,
        .report-hero,
        .user-hero {
            position:relative;
            overflow:hidden;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:1rem;
            min-height:148px;
            padding:1.25rem !important;
            margin-bottom:1rem;
            border:1px solid rgba(201,237,248,.95) !important;
            border-radius:8px;
            background:radial-gradient(circle at 92% 12%, rgba(244,223,69,.34), transparent 16rem), radial-gradient(circle at 8% 14%, rgba(22,183,232,.24), transparent 18rem), linear-gradient(135deg,#ffffff 0%,#f1fbff 100%) !important;
            box-shadow:var(--shadow-sm) !important;
        }
        .student-header::before,
        .spp-header::before,
        .arrears-page-title::before,
        .cash-hero::before,
        .payments-hero::before,
        .seq-hero::before,
        .report-hero::before,
        .user-hero::before {
            content:"";
            position:absolute;
            inset:0 0 auto;
            width:auto !important;
            height:4px;
            background:linear-gradient(90deg,var(--brand),var(--brand-sky),var(--accent),var(--gold)) !important;
        }
        .student-kicker,
        .spp-kicker,
        .arrears-kicker,
        .cash-kicker,
        .payments-kicker,
        .seq-kicker,
        .report-kicker,
        .user-kicker {
            display:inline-flex;
            align-items:center;
            gap:.45rem;
            margin-bottom:.55rem;
            padding:.32rem .62rem !important;
            border:0 !important;
            border-radius:999px;
            background:#eefdff !important;
            color:var(--brand-sky-dark) !important;
            font-size:.74rem !important;
            font-weight:850 !important;
            text-transform:uppercase;
            letter-spacing:.05em !important;
        }
        .student-header h3,
        .spp-header h3,
        .arrears-page-title h3,
        .cash-hero h3,
        .payments-hero h3,
        .seq-hero h3,
        .report-hero h3,
        .user-hero h3 {
            margin:0;
            color:#082f45;
            font-size:clamp(1.45rem,2.4vw,2.15rem) !important;
            font-weight:900 !important;
            letter-spacing:0;
        }
        .student-header p,
        .spp-header p,
        .arrears-page-title p,
        .cash-hero p,
        .payments-hero p,
        .seq-hero p,
        .report-hero p,
        .user-hero p {
            max-width:760px;
            margin:.4rem 0 0;
            color:#607485;
            line-height:1.6;
        }
        .student-table-panel,
        .spp-form-card,
        .spp-list-card,
        .arrears-board,
        .cash-card,
        .payments-manual-card,
        .payments-history-card,
        .seq-card,
        .seq-explain,
        .report-filter-card,
        .report-card,
        .user-form-card,
        .content-card:has(#userTable) {
            min-width:0;
            border:1px solid rgba(201,237,248,.95) !important;
            border-radius:8px;
            background:rgba(255,255,255,.96) !important;
            box-shadow:var(--shadow-sm) !important;
        }
        .student-table-panel,
        .arrears-board,
        .cash-card,
        .payments-history-card,
        .report-card,
        .content-card:has(#userTable) {
            padding:0 !important;
        }
        .table-toolbar,
        .arrears-toolbar,
        .cash-toolbar,
        .payments-history-card > .d-flex:first-child,
        .report-card > .d-flex:first-child,
        .user-table-toolbar {
            display:flex;
            flex-wrap:wrap;
            align-items:center;
            justify-content:space-between;
            gap:.8rem;
            padding:1rem 1rem .85rem;
            margin:0 !important;
            border-bottom:1px solid #edf1f5;
        }
        .student-search,
        .arrears-search-panel,
        .cash-search,
        .payments-search,
        .seq-search,
        .report-search-field,
        .user-search {
            min-width:0 !important;
            max-width:100%;
        }
        .student-search .form-control,
        .cash-search .form-control,
        .payments-search .form-control,
        .seq-search .form-control,
        .report-search-field .form-control,
        .user-search .form-control,
        .student-table-panel input[data-sequential-url],
        .report-card input[data-live-search] {
            border-radius:999px !important;
            background:#f8fcfd;
        }
        .student-stat,
        .cash-hero-stat,
        .payments-hero-stat,
        .user-stat,
        .spp-stat {
            border:1px solid rgba(214,221,230,.92) !important;
            background:#fff !important;
            box-shadow:var(--shadow-sm) !important;
        }
        .student-page,
        .arrears-page,
        .spp-page {
            min-width:0;
        }
        .spp-tabs {
            position:relative !important;
            top:auto !important;
        }
        .spp-form-card {
            position:static !important;
        }
        .btn { min-height:38px; font-weight:720; display:inline-flex; align-items:center; justify-content:center; gap:.35rem; border-radius:6px; }
        .btn-sm { min-height:32px; }
        .btn-outline-primary { color:var(--brand); border-color:#a9c5ee; }
        .btn-outline-primary:hover { background:var(--brand); border-color:var(--brand); color:#fff; }
        .btn-outline-success { color:var(--brand-sky-dark); border-color:#9ee4f6; }
        .btn-outline-success:hover { background:var(--brand-sky-dark); border-color:var(--brand-sky-dark); color:#fff; }
        .btn-outline-danger { color:var(--danger); border-color:#f2b8b5; }
        .btn-outline-danger:hover { background:var(--danger); border-color:var(--danger); }
        .alert { border-radius:8px; border:0; box-shadow:var(--shadow-sm); }
        .alert-success { background:#eefdff; color:#075f7f; }
        .alert-danger { background:#fef3f2; color:#b42318; }
        .nav-tabs { border-bottom:1px solid #d6dde6; }
        .nav-tabs .nav-link { color:#475467; font-weight:720; border-radius:6px 6px 0 0; }
        .nav-tabs .nav-link.active { color:var(--brand); border-color:#d6dde6 #d6dde6 #fff; }
        .modal-content { border:1px solid #d6dde6; border-radius:8px; box-shadow:var(--shadow-md); }
        .modal-header,.modal-footer { background:#f8fafc; border-color:#e6eaf0; }
        .badge { border-radius:999px; font-weight:760; letter-spacing:0; }
        .bg-success,.text-bg-success { background-color:var(--brand-sky-dark) !important; }
        .text-success { color:var(--brand-sky-dark) !important; }
        .text-primary { color:var(--brand) !important; }
        .bg-primary { background-color:var(--brand) !important; }
        .text-warning { color:#b89000 !important; }
        .live-search-count { font-weight:680; }
        .empty-state { background:#fbfcfd; }
        .public-navbar { background:rgba(255,255,255,.94); }
        .landing-hero { background:linear-gradient(rgba(7,28,26,.34),rgba(7,28,26,.72)), url("https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1800&q=80") center/cover; }
        .landing-hero__content { text-shadow:0 12px 34px rgba(0,0,0,.28); }
        .feature-item { transition:transform .18s ease, box-shadow .18s ease; }
        .feature-item:hover { transform:translateY(-2px); box-shadow:var(--shadow-md); }
        @media (max-width: 1199px) {
            .role-stat-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .role-dashboard-grid { grid-template-columns:1fr; }
            .role-hero { align-items:flex-start; }
            .role-hero-actions { max-width:100%; }
            .student-header,
            .spp-header,
            .arrears-page-title,
            .cash-hero,
            .payments-hero,
            .seq-hero,
            .report-hero,
            .user-hero {
                align-items:flex-start;
            }
            .spp-summary,
            .spp-flow-modern,
            .user-stat-grid {
                grid-template-columns:repeat(2,minmax(0,1fr)) !important;
            }
            .student-grid,
            .spp-panel-grid,
            #tab-otomatis .spp-panel-grid {
                grid-template-columns:1fr !important;
            }
        }
        @media (max-width: 767px) {
            .page-title { padding:.95rem; }
            .page-title .btn { margin-top:.85rem; width:100%; }
            .role-hero { display:block; min-height:auto; padding:1rem; }
            .role-hero-actions { justify-content:flex-start; margin-top:1rem; }
            .role-search { width:100%; }
            .role-stat-grid,.role-dashboard-grid { grid-template-columns:1fr; }
            .role-card-head { display:grid; }
            .student-header,
            .spp-header,
            .arrears-page-title,
            .cash-hero,
            .payments-hero,
            .seq-hero,
            .report-hero,
            .user-hero,
            .table-toolbar,
            .arrears-toolbar,
            .cash-toolbar,
            .payments-history-card > .d-flex:first-child,
            .report-card > .d-flex:first-child,
            .user-table-toolbar {
                display:grid !important;
                width:100%;
            }
            .student-header-actions,
            .spp-header-actions,
            .role-hero-actions {
                justify-content:flex-start !important;
                width:100%;
            }
            .student-header-actions .btn,
            .spp-header-actions .btn,
            .arrears-primary-action,
            .cash-hero-stat,
            .payments-hero-stat,
            .student-search,
            .cash-search,
            .payments-search,
            .user-search,
            .report-card input[data-live-search] {
                width:100% !important;
            }
            .spp-summary,
            .spp-flow-modern,
            .user-stat-grid,
            .arrears-summary-strip {
                grid-template-columns:1fr !important;
            }
            .bulk-actions,
            .bulk-form,
            .student-filters,
            .arrears-search-panel {
                display:grid !important;
                grid-template-columns:1fr !important;
                width:100% !important;
            }
            .table > :not(caption) > * > * { padding:.68rem .65rem; }
            .w-25 { width:100% !important; }
        }
        @media (min-width: 1200px) {
            .sidebar { width:16.66666667%; }
            .app-main { margin-left:16.66666667%; width:83.33333333%; }
            .sidebar { position:fixed; left:0; top:0; bottom:0; }
        }
        @media (max-width: 1199px) {
            .sidebar { position:relative; top:auto; min-height:auto; }
            .app-main { margin-left:0; max-width:100%; }
        }
        @media (max-width: 991px) {
            .app-shell > .row { display:block; }
            .sidebar { min-height:auto; position:relative; top:auto; width:100%; box-shadow:0 12px 30px rgba(22,45,120,.18); border-bottom:1px solid rgba(255,255,255,.14); }
            .app-main { margin-left:0; width:100%; max-width:100%; }
            .sidebar-collapse { overflow:hidden; transition:height .28s ease, opacity .22s ease, transform .22s ease; }
            .sidebar-collapse:not(.show) { opacity:0; transform:translateY(-8px); }
            .sidebar-collapse.show,.sidebar-collapse.collapsing { opacity:1; transform:translateY(0); }
            .brand-lockup { margin-bottom:0 !important; }
            .nav-user { margin-top:1rem; }
            .sidebar-nav { max-height:calc(100vh - 150px); overflow:auto; padding-bottom:.35rem; }
            .mobile-menu-btn { display:inline-flex !important; border-color:rgba(255,255,255,.34); background:rgba(255,255,255,.08); transition:background .18s ease, transform .18s ease; }
            .mobile-menu-btn[aria-expanded="true"] { background:#fff; color:#0f4f49; transform:rotate(90deg); }
            .topbar { min-height:64px; padding:.85rem 1rem; }
            .date-orbit { width:40px; height:40px; }
            .content-area { padding:1rem; }
            .public-navbar { min-height:66px; padding:.7rem 1rem; }
            .global-three-wrap { width:72vw; height:46vh; opacity:.34; }
            .feature-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .section-heading,.page-title { display:block; }
        }
        @media (max-width: 575px) {
            .feature-grid { grid-template-columns:1fr; }
            .landing-hero { min-height:78vh; padding:3rem 1rem; }
            .brand-copy strong { font-size:.95rem; }
            .brand-copy span { font-size:.72rem; }
            .topbar-title strong { font-size:.96rem; }
            .topbar-title { gap:.65rem; }
            .topbar-title span { max-width:42vw; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
            .date-orbit { display:none; }
            .topbar { gap:.65rem; }
            .topbar-actions { gap:.45rem; }
            .avatar-menu { width:38px; height:38px; }
            .content-area { padding:.9rem; }
            .topbar { position:relative; top:auto; }
            .public-navbar { flex-wrap:wrap; gap:.65rem; }
            .public-links { flex:1 0 100%; align-items:stretch; justify-content:stretch; gap:.4rem; margin-left:0; padding:.55rem 0 .15rem; }
            .public-login { width:100%; min-height:42px; display:flex; align-items:center; justify-content:center; }
            .global-three-wrap { width:88vw; height:42vh; opacity:.28; }
        }
    </style>
</head>
<body>
<div class="global-three-wrap" aria-hidden="true">
    <canvas id="globalThreeBg"></canvas>
</div>
<div class="container-fluid app-shell">
    <div class="row">
        @auth
            <aside class="col-12 col-lg-2 sidebar p-3">
                <div class="brand-lockup mb-3">
                    <div class="brand-mark"><img src="{{ asset('images/logo-sekolah.svg') }}" alt="Logo MA Taruna Teknik Al Jabbar"></div>
                    <div class="brand-copy"><strong>Al Jabbar</strong><span>Portal pembayaran</span></div>
                    <button class="btn btn-sm btn-outline-light mobile-menu-btn ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Buka menu">
                        <i class="bi bi-list"></i>
                    </button>
                </div>
                <div id="sidebarMenu" class="sidebar-collapse collapse d-lg-block">
                    <div class="nav-user">
                        <strong>{{ auth()->user()->name }}</strong>
                        <span>{{ str_replace('_',' ',auth()->user()->role) }}</span>
                    </div>
                    <nav class="sidebar-nav" aria-label="Navigasi utama">
                        <div class="nav-section-label">Utama</div>
                        <a class="{{ request()->routeIs('dashboard', 'wali.dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2"></i>Dashboard</a>
                        @if(auth()->user()->role === 'siswa')
                            <div class="nav-section-label">Siswa</div>
                            <a class="{{ request()->routeIs('siswa.tagihan') ? 'active' : '' }}" href="{{ route('siswa.tagihan') }}"><i class="bi bi-receipt"></i>Tagihan Saya</a>
                            <a class="{{ request()->routeIs('siswa.riwayat') ? 'active' : '' }}" href="{{ route('siswa.riwayat') }}"><i class="bi bi-clock-history"></i>Riwayat Pembayaran</a>
                            <a class="{{ request()->routeIs('siswa.profil') ? 'active' : '' }}" href="{{ route('siswa.profil') }}"><i class="bi bi-person"></i>Profil</a>
                        @elseif(auth()->user()->role === 'admin_tu')
                            <div class="nav-section-label">Administrasi</div>
                            <a class="{{ request()->routeIs('admin.siswa*') ? 'active' : '' }}" href="{{ route('admin.siswa') }}"><i class="bi bi-people"></i>Data Siswa</a>
                            <a class="{{ request()->routeIs('admin.settings','admin.kelas*','admin.tahun*','admin.biaya*') ? 'active' : '' }}" href="{{ route('admin.settings') }}"><i class="bi bi-sliders"></i>Pengaturan SPP</a>
                            <a class="{{ request()->routeIs('admin.arrears.*') ? 'active' : '' }}" href="{{ route('admin.arrears.students') }}"><i class="bi bi-calendar2-check"></i>Tunggakan Siswa</a>
                            <a class="{{ request()->routeIs('admin.cash.*') ? 'active' : '' }}" href="{{ route('admin.cash.queue') }}"><i class="bi bi-wallet2"></i>Antrian Tunai</a>
                            <a class="{{ request()->routeIs('admin.payments*') ? 'active' : '' }}" href="{{ route('admin.payments') }}"><i class="bi bi-credit-card"></i>Transaksi Pembayaran</a>
                            <a class="{{ request()->routeIs('admin.sequential') ? 'active' : '' }}" href="{{ route('admin.sequential') }}"><i class="bi bi-search"></i>Pencarian Sequential</a>
                            <a class="{{ request()->routeIs('admin.laporan*') ? 'active' : '' }}" href="{{ route('admin.laporan') }}"><i class="bi bi-file-earmark-bar-graph"></i>Laporan Bulanan</a>
                            <a class="{{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users') }}"><i class="bi bi-person-gear"></i>User Management</a>
                        @elseif(auth()->user()->role === 'wali_kelas')
                            <div class="nav-section-label">Wali Kelas</div>
                            <a class="{{ request()->routeIs('wali.students') ? 'active' : '' }}" href="{{ route('wali.students') }}"><i class="bi bi-people"></i>Siswa Kelas</a>
                            <a class="{{ request()->routeIs('wali.bills') ? 'active' : '' }}" href="{{ route('wali.bills') }}"><i class="bi bi-receipt"></i>Tagihan Kelas</a>
                            <a class="{{ request()->routeIs('wali.payments') ? 'active' : '' }}" href="{{ route('wali.payments') }}"><i class="bi bi-clock-history"></i>Riwayat Pembayaran</a>
                            <a class="{{ request()->routeIs('wali.arrears') ? 'active' : '' }}" href="{{ route('wali.arrears') }}"><i class="bi bi-exclamation-circle"></i>Tunggakan</a>
                            <a class="{{ request()->routeIs('wali.report') ? 'active' : '' }}" href="{{ route('wali.report') }}"><i class="bi bi-file-earmark-bar-graph"></i>Laporan Kelas</a>
                            <a class="{{ request()->routeIs('wali.contacts') ? 'active' : '' }}" href="{{ route('wali.contacts') }}"><i class="bi bi-whatsapp"></i>Kontak Orang Tua</a>
                        @else
                            <div class="nav-section-label">Kepala Sekolah</div>
                            <a class="{{ request()->routeIs('kepala.laporan') ? 'active' : '' }}" href="{{ route('kepala.laporan') }}"><i class="bi bi-calendar2-week"></i>Laporan Per Bulan</a>
                            <a class="{{ request()->routeIs('kepala.laporan.kelas') ? 'active' : '' }}" href="{{ route('kepala.laporan.kelas') }}"><i class="bi bi-building"></i>Laporan Per Kelas</a>
                            <a class="{{ request()->routeIs('kepala.grafik') ? 'active' : '' }}" href="{{ route('kepala.grafik') }}"><i class="bi bi-graph-up"></i>Grafik Pemasukan</a>
                        @endif
                    </nav>
                </div>
            </aside>
        @endauth
        <main class="@auth col-12 col-lg-10 app-main @else col-12 @endauth">
            @auth
                <header class="topbar">
                    <div class="topbar-title">
                        <div class="date-orbit" aria-hidden="true">
                            <div class="date-bars"><i></i><i></i><i></i><i></i></div>
                        </div>
                        <div>
                            <span>{{ now()->translatedFormat('l, d F Y') }}</span>
                            <strong>{{ $title ?? 'SPP Al Jabbar' }}</strong>
                        </div>
                    </div>
                    <div class="topbar-actions">
                        <div class="dropdown">
                            <button class="avatar-menu notification-menu-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Buka notifikasi">
                                <i class="bi bi-bell"></i>
                                @if(($unreadNotificationCount ?? 0) > 0)
                                    <span class="notification-dot">{{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}</span>
                                @endif
                            </button>
                            <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                                <div class="notification-head">
                                    <div><strong>Notifikasi</strong><span>{{ $unreadNotificationCount ?? 0 }} belum dibaca</span></div>
                                    <form method="post" action="{{ route('notifications.read-all') }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-check2-all"></i></button>
                                    </form>
                                </div>
                                <div class="notification-mini-list">
                                    @forelse(($topbarNotifications ?? collect()) as $notification)
                                        <form method="post" action="{{ route('notifications.read', $notification) }}" class="m-0">
                                            @csrf
                                            <button class="notification-mini-item {{ $notification->read_at ? '' : 'is-unread' }}" type="submit">
                                                <span class="notification-icon notification-{{ $notification->type }}"><i class="bi bi-bell"></i></span>
                                                <span class="notification-copy">
                                                    <strong>{{ $notification->title }}</strong>
                                                    <small>{{ $notification->message }}</small>
                                                    <em>{{ $notification->created_at->diffForHumans() }}</em>
                                                </span>
                                            </button>
                                        </form>
                                    @empty
                                        <div class="empty-state py-4">Belum ada notifikasi.</div>
                                    @endforelse
                                </div>
                                <div class="notification-footer">
                                    <a class="btn btn-sm btn-primary w-100" href="{{ route('notifications.index') }}"><i class="bi bi-list-ul"></i>Lihat Semua</a>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="avatar-menu" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Buka menu profil">
                                <i class="bi bi-person-circle"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end profile-dropdown">
                                <div class="profile-summary">
                                    <strong>{{ auth()->user()->name }}</strong>
                                    <span>{{ str_replace('_',' ',auth()->user()->role) }}</span>
                                </div>
                                <form method="post" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item text-danger py-2" type="submit">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>
            @else
                @unless(request()->routeIs('login'))
                <header class="public-navbar">
                    <a class="public-brand" href="{{ route('landing') }}">
                        <img class="brand-logo" src="{{ asset('images/logo-sekolah.svg') }}" alt="Logo MA Taruna Teknik Al Jabbar">
                        <span>Al Jabbar</span>
                    </a>
                    <nav class="public-links" aria-label="Navigasi publik">
                        <a class="btn btn-primary btn-sm public-login" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right me-1"></i>Masuk</a>
                    </nav>
                </header>
                @endunless
            @endauth
            <div class="@auth content-area @else p-0 @endauth">
                <div class="app-toast-stack" aria-live="polite" aria-atomic="true">
                    @if(session('success'))
                        <div class="app-toast app-toast-success" data-app-toast>
                            <i class="bi bi-check2"></i>
                            <div><strong>Berhasil</strong><span>{{ session('success') }}</span></div>
                            <button type="button" data-toast-close aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="app-toast app-toast-danger" data-app-toast>
                            <i class="bi bi-exclamation-triangle"></i>
                            <div><strong>Perlu Dicek</strong><span>{{ $errors->first() }}</span></div>
                            <button type="button" data-toast-close aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
                        </div>
                    @endif
                </div>
                @yield('content')
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('[data-live-search]').forEach(function (input) {
    const target = document.querySelector(input.dataset.liveSearch);
    if (!target) return;
    const rows = Array.from(target.querySelectorAll('tbody tr'));
    const counter = input.dataset.liveCount ? document.querySelector(input.dataset.liveCount) : null;
    const emptyRow = target.querySelector('[data-empty-row]');

    function filterRows() {
        const keyword = input.value.trim().toLowerCase();
        let visible = 0;
        rows.forEach(function (row) {
            if (row.hasAttribute('data-empty-row')) return;
            const match = row.textContent.toLowerCase().includes(keyword);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        if (emptyRow) emptyRow.style.display = visible === 0 ? '' : 'none';
        if (counter) counter.textContent = visible + ' data tampil';
    }

    input.addEventListener('input', filterRows);
    filterRows();
});

document.querySelectorAll('[data-app-toast]').forEach(function (toast) {
    const close = toast.querySelector('[data-toast-close]');
    const remove = function () {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-8px) scale(.98)';
        setTimeout(function () { toast.remove(); }, 180);
    };
    if (close) close.addEventListener('click', remove);
    setTimeout(remove, 5200);
});

document.querySelectorAll('[data-sequential-url]').forEach(function (input) {
    const target = document.querySelector(input.dataset.resultsTarget);
    const meta = document.querySelector(input.dataset.metaTarget);
    if (!target) return;
    let timer = null;

    function runSearch() {
        const url = new URL(input.dataset.sequentialUrl, window.location.origin);
        url.searchParams.set('keyword', input.value);
        if (input.dataset.extraFilterTargets) {
            document.querySelectorAll(input.dataset.extraFilterTargets).forEach(function (filter) {
                if (filter.name) url.searchParams.set(filter.name, filter.value);
            });
        }
        if (input.dataset.includeCancelledTarget) {
            const checkbox = document.querySelector(input.dataset.includeCancelledTarget);
            if (checkbox && checkbox.checked) url.searchParams.set('show_cancelled', '1');
        }

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (response) {
                if (!response.ok) throw new Error('Pencarian gagal dimuat.');
                return response.json();
            })
            .then(function (data) {
                target.innerHTML = data.html;
                if (meta) {
                    const checked = data.checked ?? 0;
                    const count = data.count ?? 0;
                    const duration = data.duration_ms ?? 0;
                    meta.textContent = 'Data diperiksa: ' + checked + ' | Hasil: ' + count + ' | Waktu: ' + duration + ' ms';
                }
            })
            .catch(function () {
                if (meta) meta.textContent = 'Pencarian gagal dimuat. Coba ulangi.';
            });
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(runSearch, 180);
    });

    if (input.dataset.includeCancelledTarget) {
        const checkbox = document.querySelector(input.dataset.includeCancelledTarget);
        if (checkbox) checkbox.addEventListener('change', runSearch);
    }

    if (input.dataset.extraFilterTargets) {
        document.querySelectorAll(input.dataset.extraFilterTargets).forEach(function (filter) {
            filter.addEventListener('change', runSearch);
        });
    }
});

document.querySelectorAll('form[data-auto-submit]').forEach(function (form) {
    let timer = null;
    const delay = Number(form.dataset.autoSubmitDelay || 450);

    function submitForm() {
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }

    form.querySelectorAll('select, input[type="checkbox"], input[type="radio"]').forEach(function (field) {
        field.addEventListener('change', submitForm);
    });

    form.querySelectorAll('input[type="search"], input[type="text"], input:not([type])').forEach(function (field) {
        field.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(submitForm, delay);
        });
    });
});

document.querySelectorAll('[data-select-filter]').forEach(function (input) {
    const select = document.querySelector(input.dataset.selectFilter);
    if (!select) return;
    const classFilter = input.dataset.selectClassFilter ? document.querySelector(input.dataset.selectClassFilter) : null;
    const meta = input.dataset.selectMeta ? document.querySelector(input.dataset.selectMeta) : null;
    const options = Array.from(select.options);

    function filterOptions() {
        const keyword = input.value.toLowerCase();
        const selectedClass = classFilter ? classFilter.value : '';
        let checked = 0;
        let visible = 0;
        let firstVisibleValue = '';

        options.forEach(function (option) {
            if (option.value === '' || option.value === 'all') {
                option.hidden = false;
                return;
            }

            checked++;
            const matchKeyword = option.textContent.toLowerCase().includes(keyword);
            const matchClass = selectedClass === '' || option.dataset.kelasId === selectedClass;
            const match = matchKeyword && matchClass;
            option.hidden = !match;

            if (match) {
                visible++;
                if (firstVisibleValue === '') firstVisibleValue = option.value;
            }
        });

        if (meta) {
            meta.textContent = 'Data diperiksa: ' + checked + ' | Hasil: ' + visible;
        }

        const selectedOption = select.selectedOptions[0];
        if (selectedOption && selectedOption.hidden) {
            select.value = visible === 1 ? firstVisibleValue : (options.some(function (option) { return option.value === 'all'; }) ? 'all' : '');
        } else if (visible === 1 && keyword !== '') {
            select.value = firstVisibleValue;
        }
    }

    input.addEventListener('input', filterOptions);
    if (classFilter) classFilter.addEventListener('change', filterOptions);
    filterOptions();
});

document.querySelectorAll('[data-bs-toggle="collapse"][data-bs-target]').forEach(function (button) {
    const target = document.querySelector(button.dataset.bsTarget);
    const icon = button.querySelector('i');
    if (!target || !icon) return;

    target.addEventListener('shown.bs.collapse', function () {
        icon.classList.remove('bi-list');
        icon.classList.add('bi-x-lg');
    });

    target.addEventListener('hidden.bs.collapse', function () {
        icon.classList.remove('bi-x-lg');
        icon.classList.add('bi-list');
    });
});

document.addEventListener('show.bs.modal', function (event) {
    if (event.target.parentElement !== document.body) {
        document.body.appendChild(event.target);
    }
});
</script>
<script type="module">
import * as THREE from 'https://cdn.jsdelivr.net/npm/three@0.164.1/build/three.module.js';

const canvas = document.getElementById('globalThreeBg');
if (canvas) {
    const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(46, 1, 0.1, 100);
    camera.position.set(0, 0, 8.5);

    const group = new THREE.Group();
    scene.add(group);

    const geometries = [
        new THREE.IcosahedronGeometry(0.75, 1),
        new THREE.TorusKnotGeometry(0.45, 0.13, 80, 12),
        new THREE.OctahedronGeometry(0.7, 1),
    ];
    const materials = [
        new THREE.MeshStandardMaterial({ color: 0x0f766e, roughness: 0.32, metalness: 0.16, transparent: true, opacity: 0.82 }),
        new THREE.MeshStandardMaterial({ color: 0xb45309, roughness: 0.42, metalness: 0.1, transparent: true, opacity: 0.74 }),
        new THREE.MeshStandardMaterial({ color: 0x2563eb, roughness: 0.36, metalness: 0.12, transparent: true, opacity: 0.66 }),
    ];

    const shapes = Array.from({ length: 11 }, function (_, index) {
        const mesh = new THREE.Mesh(geometries[index % geometries.length], materials[index % materials.length]);
        mesh.position.set((Math.random() - 0.42) * 8, (Math.random() - 0.48) * 5.8, -Math.random() * 4.6);
        mesh.scale.setScalar(0.35 + Math.random() * 0.7);
        mesh.userData = {
            speed: 0.0028 + Math.random() * 0.0045,
            float: Math.random() * Math.PI * 2,
            drift: 0.0005 + Math.random() * 0.0008,
        };
        group.add(mesh);
        return mesh;
    });

    scene.add(new THREE.AmbientLight(0xffffff, 1.2));
    const keyLight = new THREE.DirectionalLight(0xffffff, 1.55);
    keyLight.position.set(5, 5, 7);
    scene.add(keyLight);
    const colorLight = new THREE.PointLight(0x0f766e, 1.4, 10);
    colorLight.position.set(-3, 2, 4);
    scene.add(colorLight);

    function resizeThree() {
        const rect = canvas.parentElement.getBoundingClientRect();
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
        renderer.setSize(rect.width, rect.height, false);
        camera.aspect = rect.width / Math.max(rect.height, 1);
        camera.updateProjectionMatrix();
    }

    function animateThree(time) {
        shapes.forEach(function (mesh, index) {
            mesh.rotation.x += mesh.userData.speed;
            mesh.rotation.y += mesh.userData.speed * 1.35;
            mesh.position.y += Math.sin(time * 0.001 + mesh.userData.float + index) * 0.0016;
            mesh.position.x += Math.cos(time * mesh.userData.drift + index) * 0.0009;
        });
        group.rotation.z = Math.sin(time * 0.00025) * 0.09;
        group.rotation.y = Math.sin(time * 0.00018) * 0.12;
        renderer.render(scene, camera);
        requestAnimationFrame(animateThree);
    }

    resizeThree();
    window.addEventListener('resize', resizeThree);
    requestAnimationFrame(animateThree);
}
</script>
@stack('scripts')
</body>
</html>
