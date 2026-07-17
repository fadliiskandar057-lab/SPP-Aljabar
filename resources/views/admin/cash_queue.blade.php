@extends('layouts.app')

@section('content')
<div class="cash-hero page-title">
    <div>
        <span class="cash-kicker">Antrian TU</span>
        <h3>Antrian Pembayaran Tunai</h3>
        <p>Konfirmasi invoice tunai yang dibawa siswa/orang tua ke TU.</p>
    </div>
    <div class="cash-hero-stat">
        <span>Total Antrian</span>
        <strong>{{ $payments->count() }}</strong>
    </div>
</div>

<div class="content-card p-3 cash-card">
    <div class="cash-toolbar">
        <div>
            <h6 class="mb-1 fw-bold">Daftar Konfirmasi Tunai</h6>
            <div class="small text-muted">Pastikan invoice dan nominal sesuai sebelum mengonfirmasi lunas.</div>
        </div>
        <div class="cash-search">
            <i class="bi bi-search"></i>
            <input class="form-control form-control-sm" placeholder="Cari antrian..." data-live-search="#cashQueueTable">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm align-middle" id="cashQueueTable">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Siswa</th>
                    <th>Kelas</th>
                    <th>Bulan</th>
                    <th>Nominal</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $p)
                    @php
                        $message = "Assalamu'alaikum Bapak/Ibu {$p->siswa->nama_orang_tua}, pembayaran tunai SPP ananda {$p->siswa->nama} untuk {$p->tagihan->bulan} {$p->tagihan->tahun} dengan invoice {$p->kode_invoice} sedang dalam antrian konfirmasi TU. Terima kasih.";
                    @endphp
                    <tr class="cash-row">
                        <td><span class="cash-invoice">{{ $p->kode_invoice }}</span></td>
                        <td>
                            <div class="cash-student">
                                <span>{{ strtoupper(mb_substr($p->siswa->nama, 0, 1)) }}</span>
                                <div>
                                    <strong>{{ $p->siswa->nama }}</strong>
                                    <small>{{ $p->siswa->no_hp_orang_tua ?: 'No WA belum ada' }}</small>
                                </div>
                            </div>
                        </td>
                        <td><span class="cash-chip">{{ $p->siswa->kelas->nama_kelas }}</span></td>
                        <td>{{ $p->tagihan->bulan }} {{ $p->tagihan->tahun }}</td>
                        <td><strong class="cash-money">Rp {{ number_format($p->nominal,0,',','.') }}</strong></td>
                        <td class="text-end text-nowrap">
                            <x-whatsapp-link :phone="$p->siswa->no_hp_orang_tua" :message="$message" label="WA" class="btn btn-sm btn-success" />
                            <form class="d-inline" method="post" action="{{ route('admin.cash.confirm',$p) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-success" onclick="return confirm('Konfirmasi pembayaran tunai {{ $p->siswa->nama }} bulan {{ $p->tagihan->bulan }} sebagai lunas?')">Konfirmasi Lunas</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr data-empty-row><td colspan="6" class="empty-state">Tidak ada antrian tunai.</td></tr>
                @endforelse
                <tr data-empty-row style="display:none"><td colspan="6" class="empty-state">Antrian tidak ditemukan.</td></tr>
            </tbody>
        </table>
    </div>
</div>

<style>
    .cash-hero { position:relative; overflow:hidden; align-items:center; padding:1.2rem; border-color:#d6edf6; background:linear-gradient(135deg,#fff,#eef8fc); box-shadow:0 18px 44px rgba(16,24,40,.07); }
    .cash-hero::before { content:""; position:absolute; inset:0 auto 0 0; width:5px; background:linear-gradient(180deg,#16b7e8,#162d78); }
    .cash-kicker { display:inline-flex; margin-bottom:.45rem; padding:.28rem .58rem; border:1px solid #c9edf8; border-radius:999px; background:#fff; color:#075f7f; font-size:.72rem; font-weight:820; text-transform:uppercase; letter-spacing:.04em; }
    .cash-hero h3 { font-weight:850; }
    .cash-hero-stat { min-width:132px; padding:.85rem 1rem; border:1px solid #c9edf8; border-radius:8px; background:#fff; box-shadow:0 10px 26px rgba(16,24,40,.06); }
    .cash-hero-stat span { display:block; color:#667085; font-size:.76rem; font-weight:800; text-transform:uppercase; }
    .cash-hero-stat strong { display:block; color:#162d78; font-size:1.55rem; line-height:1; margin-top:.2rem; }
    .cash-card { border-color:#d6edf6; box-shadow:0 18px 44px rgba(16,24,40,.07); }
    .cash-toolbar { display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:.9rem; padding-bottom:.85rem; border-bottom:1px solid #edf1f5; }
    .cash-search { position:relative; width:min(320px,100%); }
    .cash-search i { position:absolute; left:.78rem; top:50%; transform:translateY(-50%); color:#667085; }
    .cash-search input { padding-left:2.2rem; border-radius:999px; }
    .cash-row:hover { background:#f8fbfc; box-shadow:inset 3px 0 0 #16b7e8; }
    .cash-invoice,.cash-chip { display:inline-flex; padding:.32rem .58rem; border-radius:999px; font-weight:760; white-space:nowrap; }
    .cash-invoice { color:#0e205a; background:#eef8fc; border:1px solid #c9edf8; }
    .cash-chip { color:#075f7f; background:#eefdff; border:1px solid #bbf7d0; }
    .cash-student { display:flex; align-items:center; gap:.7rem; min-width:220px; }
    .cash-student > span { width:40px; height:40px; display:grid; place-items:center; border-radius:8px; color:#fff; background:linear-gradient(145deg,#16b7e8,#162d78); font-weight:850; }
    .cash-student strong,.cash-student small { display:block; }
    .cash-student small { color:#667085; }
    .cash-money { color:#075f7f; white-space:nowrap; }
    @media (max-width: 767px) { .cash-hero,.cash-toolbar { display:grid; } .cash-search,.cash-hero-stat { width:100%; } }
</style>
@endsection
