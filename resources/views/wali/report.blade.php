@extends('layouts.app')

@section('content')
<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-file-earmark-bar-graph"></i>Kelas {{ $kelas->nama_kelas ?? '-' }}</span>
            <h3>Laporan Kelas</h3>
            <p>Rekap pembayaran kelas {{ $kelas->nama_kelas ?? '-' }} berdasarkan bulan dan status tagihan.</p>
        </div>
        <div class="role-hero-actions"><span class="role-icon-tile"><i class="bi bi-clipboard-data"></i></span></div>
    </section>

<div class="role-filter p-3">
    <form class="row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label">Bulan</label><input name="bulan" value="{{ request('bulan') }}" class="form-control" placeholder="Contoh: Juni"></div>
        <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">Semua</option>
                @foreach(['belum_lunas' => 'Belum Lunas', 'menunggu_konfirmasi' => 'Menunggu', 'lunas' => 'Lunas', 'gratis' => 'Gratis', 'gagal' => 'Gagal'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4"><button class="btn btn-primary w-100"><i class="bi bi-funnel"></i>Filter</button></div>
    </form>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="metric-card"><div class="metric-label">Pemasukan</div><div class="metric-value">Rp {{ number_format($summary['pemasukan'], 0, ',', '.') }}</div></div></div>
    <div class="col-md-3"><div class="metric-card"><div class="metric-label">Tunggakan</div><div class="metric-value">Rp {{ number_format($summary['tunggakan'], 0, ',', '.') }}</div></div></div>
    <div class="col-md-3"><div class="metric-card"><div class="metric-label">Lunas / Gratis</div><div class="metric-value">{{ $summary['lunas'] }} / {{ $summary['gratis'] }}</div></div></div>
    <div class="col-md-3"><div class="metric-card"><div class="metric-label">Belum Lunas</div><div class="metric-value">{{ $summary['belum_lunas'] }}</div></div></div>
</div>

<div class="role-table-card">
    <div class="role-card-head"><div><h6>Detail Laporan</h6><span>Tagihan yang cocok dengan filter.</span></div></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Siswa</th><th>Bulan</th><th>Nominal</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($bills as $bill)
                    <tr>
                        <td><div class="role-student"><span class="role-avatar">{{ strtoupper(substr($bill->siswa->nama,0,1)) }}</span><div><strong>{{ $bill->siswa->nama }}</strong><span>{{ $bill->siswa->nis }}</span></div></div></td>
                        <td>{{ $bill->bulan }} {{ $bill->tahun }}</td>
                        <td><span class="role-money">Rp {{ number_format($bill->nominal, 0, ',', '.') }}</span></td>
                        <td><x-status :status="$bill->status"/></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty-state">Laporan tidak ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection
