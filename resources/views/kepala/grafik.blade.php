@extends('layouts.app')

@section('content')
<div class="role-page">
    <section class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker"><i class="bi bi-graph-up-arrow"></i>Analitik Pemasukan</span>
            <h3>Grafik Pemasukan {{ $year }}</h3>
            <p>Visualisasi tren pemasukan bulanan dan rasio status tagihan untuk membantu membaca kondisi pembayaran sekolah.</p>
        </div>
        <div class="role-hero-actions"><span class="role-icon-tile"><i class="bi bi-bar-chart-line"></i></span></div>
    </section>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="role-table-card p-3">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h6 class="mb-1 fw-bold">Pemasukan Bulanan</h6>
                        <span class="text-muted small">Januari sampai Desember {{ $year }}</span>
                    </div>
                    <span class="role-chip"><i class="bi bi-calendar3"></i>{{ $year }}</span>
                </div>
                <div style="height:340px"><canvas id="principalIncomeChart"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="role-table-card p-3">
                <h6 class="mb-1 fw-bold">Rasio Tagihan</h6>
                <span class="text-muted small">Perbandingan lunas dan belum lunas</span>
                <div class="mt-3" style="height:310px"><canvas id="principalStatusChart"></canvas></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
new Chart(document.getElementById('principalIncomeChart'),{type:'line',data:{labels:['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],datasets:[{label:'Pemasukan',data:@json($monthlyIncome),borderColor:'#16b7e8',backgroundColor:'rgba(22,183,232,.16)',fill:true,tension:.35,pointBackgroundColor:'#162d78',pointBorderColor:'#fff',pointBorderWidth:2,pointRadius:4}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{ticks:{callback:function(value){return 'Rp '+new Intl.NumberFormat('id-ID').format(value);}}}}}});
new Chart(document.getElementById('principalStatusChart'),{type:'doughnut',data:{labels:['Lunas','Belum Lunas'],datasets:[{data:[{{ $statusChart['lunas'] }},{{ $statusChart['belum_lunas'] }}],backgroundColor:['#16b7e8','#e52632'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}});
</script>
@endpush
@endsection
