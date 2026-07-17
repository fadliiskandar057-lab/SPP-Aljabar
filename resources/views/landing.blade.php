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
        --tta-soft:#f4f9fc;
        --tta-line:#d9e8ef;
    }
    .tta-landing { background:#f6fbfd; color:var(--tta-ink); overflow:hidden; }
    .tta-hero {
        position:relative;
        min-height:calc(100vh - 118px);
        display:flex;
        align-items:center;
        padding:clamp(4.2rem,8vw,6.5rem) min(7vw,5.5rem);
        color:#082f45;
        background:
            radial-gradient(circle at 84% 12%, rgba(244,223,69,.5), transparent 18rem),
            radial-gradient(circle at 10% 20%, rgba(22,183,232,.32), transparent 26rem),
            linear-gradient(90deg,rgba(246,251,253,.98) 0%,rgba(246,251,253,.9) 46%,rgba(246,251,253,.45) 100%),
            url("https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1900&q=80") center/cover;
    }
    .tta-hero::after {
        content:"";
        position:absolute;
        inset:auto 0 0;
        height:38%;
        background:linear-gradient(0deg,rgba(246,251,253,1),rgba(246,251,253,0));
        pointer-events:none;
    }
    .tta-hero-inner {
        position:relative;
        z-index:1;
        width:min(960px,100%);
    }
    .tta-brand-chip {
        display:inline-flex;
        align-items:center;
        gap:.85rem;
        padding:.55rem .8rem;
        border:1px solid #c9edf8;
        border-radius:999px;
        background:#fff;
        color:var(--tta-navy);
        box-shadow:0 12px 30px rgba(16,34,53,.08);
        font-weight:850;
    }
    .tta-brand-chip img { width:42px; height:42px; object-fit:contain; filter:drop-shadow(0 8px 12px rgba(22,45,120,.16)); }
    .tta-hero h1 {
        margin:1.15rem 0 .9rem;
        font-size:clamp(2.35rem,5vw,5rem);
        line-height:1.02;
        font-weight:900;
        letter-spacing:0;
    }
    .tta-hero h1 span { color:var(--tta-red); }
    .tta-hero .lead {
        max-width:760px;
        margin:0;
        color:#41586a;
        font-size:clamp(1rem,1.7vw,1.2rem);
        line-height:1.75;
    }
    .tta-actions { display:flex; flex-wrap:wrap; gap:.85rem; margin-top:1.6rem; }
    .tta-primary { background:var(--tta-navy); border-color:var(--tta-navy); color:#fff; font-weight:850; box-shadow:0 18px 34px rgba(22,45,120,.18); }
    .tta-primary:hover { background:#0e205a; border-color:#0e205a; color:#fff; }
    .tta-glass { color:var(--tta-navy); border-color:rgba(22,45,120,.24); background:#fff; font-weight:800; }
    .tta-glass:hover { background:var(--tta-red); color:#fff; border-color:var(--tta-red); }
    .tta-overview {
        position:relative;
        z-index:2;
        width:min(1180px,calc(100% - 2rem));
        margin:-2.4rem auto 0;
        display:grid;
        grid-template-columns:1.12fr repeat(3,.72fr);
        border:1px solid rgba(217,232,239,.9);
        border-radius:8px;
        background:#fff;
        box-shadow:0 22px 50px rgba(16,34,53,.12);
        overflow:hidden;
    }
    .tta-overview-main,.tta-overview-item { padding:1.2rem; }
    .tta-overview-main { background:linear-gradient(135deg,var(--tta-navy),var(--tta-sky-dark)); color:#fff; }
    .tta-overview-main strong,.tta-overview-item strong { display:block; font-size:1.02rem; }
    .tta-overview-main span,.tta-overview-item span { display:block; margin-top:.35rem; color:#6b7f8e; line-height:1.55; }
    .tta-overview-main span { color:rgba(255,255,255,.76); }
    .tta-overview-item { border-left:1px solid var(--tta-line); }
    .tta-overview-item i { color:var(--tta-sky-dark); font-size:1.45rem; margin-bottom:.55rem; display:inline-block; }
    .tta-section { padding:clamp(3rem,6vw,5rem) min(7vw,5.5rem); }
    .tta-section.alt { background:#fff; }
    .tta-section-head {
        display:flex;
        align-items:end;
        justify-content:space-between;
        gap:1.5rem;
        margin-bottom:1.35rem;
    }
    .tta-kicker { margin:0 0 .35rem; color:var(--tta-red); font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; }
    .tta-section h2 { margin:0; color:#082f45; font-weight:900; letter-spacing:0; }
    .tta-section-caption { max-width:580px; margin:0; color:#607485; line-height:1.7; }
    .tta-story-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
    .tta-panel,.tta-feature,.tta-meaning,.tta-cta {
        border:1px solid var(--tta-line);
        border-radius:8px;
        background:#fff;
        box-shadow:0 12px 30px rgba(16,34,53,.06);
    }
    .tta-panel { padding:1.25rem; }
    .tta-panel p { margin:0; color:#42586a; line-height:1.82; }
    .tta-values {
        display:grid;
        grid-template-columns:.8fr 1.2fr;
        gap:1rem;
        align-items:stretch;
    }
    .tta-vision {
        padding:1.35rem;
        color:#fff;
        border-radius:8px;
        background:linear-gradient(135deg,#162d78,#0794c9);
        min-height:100%;
    }
    .tta-vision .tta-kicker { color:var(--tta-gold); }
    .tta-vision h3 { margin:.35rem 0 0; font-size:1.55rem; font-weight:900; line-height:1.32; }
    .tta-missions { display:grid; gap:.75rem; margin:0; padding:0; list-style:none; }
    .tta-missions li {
        display:grid;
        grid-template-columns:auto 1fr;
        gap:.8rem;
        align-items:start;
        padding:.9rem 1rem;
        border:1px solid var(--tta-line);
        border-radius:8px;
        background:#fff;
        color:#42586a;
        line-height:1.58;
    }
    .tta-missions i { color:var(--tta-red); font-size:1.05rem; margin-top:.15rem; }
    .tta-feature-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1rem; }
    .tta-feature { padding:1.15rem; min-height:180px; }
    .tta-feature i { color:var(--tta-sky-dark); font-size:1.8rem; }
    .tta-feature strong { display:block; margin:.7rem 0 .35rem; color:#082f45; font-size:1.02rem; }
    .tta-feature span { color:#607485; line-height:1.6; }
    .tta-meaning-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; }
    .tta-meaning { padding:1rem; border-top:4px solid var(--tta-sky); }
    .tta-meaning:nth-child(2) { border-top-color:var(--tta-red); }
    .tta-meaning:nth-child(3) { border-top-color:var(--tta-gold); }
    .tta-meaning:nth-child(4) { border-top-color:var(--tta-navy); }
    .tta-meaning strong { color:#082f45; }
    .tta-meaning p { margin:.35rem 0 0; color:#607485; line-height:1.62; }
    .tta-cta {
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:1.2rem;
        padding:1.35rem;
        background:linear-gradient(135deg,var(--tta-navy),#0b5f87);
        color:#fff;
    }
    .tta-cta h2 { color:#fff; }
    .tta-cta p { margin:.35rem 0 0; color:rgba(255,255,255,.74); }
    @media (max-width: 991px) {
        .tta-hero { min-height:72vh; padding:3.5rem 1rem; }
        .tta-overview,.tta-story-grid,.tta-values { grid-template-columns:1fr; }
        .tta-overview-item { border-left:0; border-top:1px solid var(--tta-line); }
        .tta-feature-grid,.tta-meaning-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .tta-section-head { display:block; }
        .tta-section-caption { margin-top:.7rem; }
    }
    @media (max-width: 575px) {
        .tta-hero { min-height:76vh; align-items:center; }
        .tta-actions .btn { width:100%; justify-content:center; }
        .tta-section { padding:2.5rem 1rem; }
        .tta-feature-grid,.tta-meaning-grid { grid-template-columns:1fr; }
        .tta-cta { display:block; }
        .tta-cta .btn { width:100%; margin-top:1rem; }
    }
</style>

<div class="tta-landing">
    <section class="tta-hero">
        <div class="tta-hero-inner">
            <div class="tta-brand-chip">
                <img src="{{ asset('images/logo-sekolah.svg') }}" alt="Logo MA Taruna Teknik Al Jabbar">
                <span>Portal SPP Digital</span>
            </div>
            <h1>MA Taruna Teknik <span>Al Jabbar</span></h1>
            <p class="lead">Portal pembayaran sekolah untuk administrasi SPP yang lebih tertib, transparan, dan mudah dipantau oleh siswa, Admin TU, serta Kepala Sekolah.</p>
            <div class="tta-actions">
                <a href="{{ route('login') }}" class="btn btn-lg tta-primary"><i class="bi bi-box-arrow-in-right"></i>Masuk Portal</a>
                <a href="#fitur" class="btn btn-lg tta-glass"><i class="bi bi-grid-3x3-gap"></i>Lihat Fitur</a>
            </div>
        </div>
    </section>

    <div class="tta-overview">
        <div class="tta-overview-main">
            <strong>Pendidikan Islam, taruna, dan teknologi</strong>
            <span>Satu identitas sekolah yang menekankan adab, kedisiplinan, ilmu, dan kesiapan digital.</span>
        </div>
        <div class="tta-overview-item">
            <i class="bi bi-shield-check"></i>
            <strong>Disiplin</strong>
            <span>Data pembayaran dan tunggakan tersusun jelas.</span>
        </div>
        <div class="tta-overview-item">
            <i class="bi bi-credit-card"></i>
            <strong>Digital</strong>
            <span>Pembayaran online dan tunai berada dalam satu alur.</span>
        </div>
        <div class="tta-overview-item">
            <i class="bi bi-file-earmark-bar-graph"></i>
            <strong>Laporan</strong>
            <span>Pemasukan, status lunas, dan tunggakan mudah diaudit.</span>
        </div>
    </div>

    <section id="sejarah" class="tta-section">
        <div class="tta-section-head">
            <div>
                <p class="tta-kicker">Sejarah Sekolah</p>
                <h2>Berawal dari gagasan pendidikan yang utuh</h2>
            </div>
            <p class="tta-section-caption">Sekolah ini tumbuh dari kebutuhan masyarakat akan pendidikan yang membentuk karakter, kedisiplinan, dan keterampilan teknis siswa.</p>
        </div>
        <div class="tta-story-grid">
            <div class="tta-panel">
                <p>MA Plus Taruna Teknik Al Jabbar didirikan sebagai respon terhadap kebutuhan sekolah yang mampu mengintegrasikan nilai Islam dengan keterampilan teknis modern. Sistem pendidikannya dirancang untuk menumbuhkan akhlak mulia, kedisiplinan taruna, dan kesiapan siswa menghadapi perkembangan zaman.</p>
            </div>
            <div class="tta-panel">
                <p>Dalam perkembangannya, fasilitas, kurikulum, dan program unggulan terus ditingkatkan. Pendekatan teknik dan teknologi diperkenalkan untuk menjawab kebutuhan dunia industri dan digital, dengan dukungan masyarakat serta komitmen tenaga pendidik.</p>
            </div>
        </div>
    </section>

    <section class="tta-section alt">
        <div class="tta-values">
            <div class="tta-vision">
                <p class="tta-kicker">Visi</p>
                <h3>Menjadi lembaga pendidikan yang berdedikasi dalam mendidik generasi bangsa agar tumbuh berkualitas, bertakwa, dan menjunjung tinggi adab.</h3>
            </div>
            <ul class="tta-missions">
                <li><i class="bi bi-check2-circle"></i><span>Memberikan pendidikan berkualitas melalui kurikulum yang komprehensif dan berorientasi masa depan.</span></li>
                <li><i class="bi bi-check2-circle"></i><span>Mengembangkan karakter mulia dengan nilai etika, moral, dan sosial yang kuat.</span></li>
                <li><i class="bi bi-check2-circle"></i><span>Mendukung potensi siswa melalui lingkungan belajar, akademik, dan ekstrakurikuler yang beragam.</span></li>
                <li><i class="bi bi-check2-circle"></i><span>Mendorong inovasi, kreativitas, berpikir kritis, dan kesiapan menghadapi tantangan global.</span></li>
            </ul>
        </div>
    </section>

    <section id="fitur" class="tta-section">
        <div class="tta-section-head">
            <div>
                <p class="tta-kicker">Portal Pembayaran</p>
                <h2>Administrasi SPP yang lebih rapi</h2>
            </div>
            <p class="tta-section-caption">Sistem membantu pengelolaan tagihan, pembayaran, tunggakan, pencarian sequential, dan laporan bulanan dalam satu portal.</p>
        </div>
        <div class="tta-feature-grid">
            <div class="tta-feature">
                <i class="bi bi-search"></i>
                <strong>Sequential Search</strong>
                <span>Pencarian siswa, pembayaran, dan laporan menampilkan jumlah data diperiksa serta waktu proses.</span>
            </div>
            <div class="tta-feature">
                <i class="bi bi-wallet2"></i>
                <strong>Antrian Tunai</strong>
                <span>Admin TU dapat mengonfirmasi pembayaran tunai dengan status yang jelas.</span>
            </div>
            <div class="tta-feature">
                <i class="bi bi-calendar2-check"></i>
                <strong>Tunggakan Siswa</strong>
                <span>Tagihan yang belum lunas ditampilkan lengkap dengan bulan dan nominal yang harus dibayar.</span>
            </div>
            <div class="tta-feature">
                <i class="bi bi-filetype-pdf"></i>
                <strong>Export Laporan</strong>
                <span>Laporan bulanan dapat dipantau dan diekspor untuk kebutuhan administrasi sekolah.</span>
            </div>
        </div>
    </section>

    <section class="tta-section alt">
        <div class="tta-section-head">
            <div>
                <p class="tta-kicker">Makna Logo</p>
                <h2>Simbol sekolah dalam warna dan bentuk</h2>
            </div>
            <p class="tta-section-caption">Logo menggambarkan lembaga pendidikan yang beriman, berilmu, disiplin, tangguh, dan siap bersaing dalam teknik serta teknologi modern.</p>
        </div>
        <div class="tta-meaning-grid">
            <div class="tta-meaning"><strong>Segi lima</strong><p>Melambangkan Rukun Islam, ketangguhan taruna, dan komitmen membangun karakter Islami.</p></div>
            <div class="tta-meaning"><strong>Bintang merah</strong><p>Menunjukkan cita-cita tinggi, keberanian, kedisiplinan, energi, dan ketegasan.</p></div>
            <div class="tta-meaning"><strong>Sayap merah</strong><p>Mewakili semangat maju, pantang menyerah, serta keseimbangan ilmu agama dan ilmu teknik.</p></div>
            <div class="tta-meaning"><strong>Pilar biru</strong><p>Menjadi simbol keteguhan, integritas, dan obor ilmu sebagai penuntun kehidupan.</p></div>
            <div class="tta-meaning"><strong>Pita TTA</strong><p>Menegaskan identitas Taruna Teknik Al Jabbar dengan suasana pendidikan profesional.</p></div>
            <div class="tta-meaning"><strong>Tulisan kuning</strong><p>Melambangkan kejayaan, harapan, masa depan cerah, dan kebanggaan asal Medan.</p></div>
        </div>
    </section>

    <section class="tta-section">
        <div class="tta-cta">
            <div>
                <p class="tta-kicker">Portal SPP Digital</p>
                <h2>Masuk untuk melihat tagihan, pembayaran, dan laporan.</h2>
                <p>Satu pintu untuk siswa, Admin TU, dan Kepala Sekolah.</p>
            </div>
            <a href="{{ route('login') }}" class="btn btn-light btn-lg"><i class="bi bi-box-arrow-in-right"></i>Masuk Sistem</a>
        </div>
    </section>
</div>
@endsection
