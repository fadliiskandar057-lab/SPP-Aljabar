@if($compact ?? false)
<div class="small text-muted mt-1" aria-label="Ringkasan pelunasan SPP">
    Lunas sampai: <strong>{{ $coverage['through'] ?? 'Belum tercatat' }}</strong>
</div>
@else
<div class="border rounded p-3 my-2" aria-label="Ringkasan pelunasan SPP">
    <div><strong>Lunas sampai: {{ $coverage['through'] ?? 'Belum ada periode lunas berurutan' }}</strong></div>
    @if($coverage['latest'] && $coverage['latest'] !== $coverage['through'])
        <div class="small">Periode lunas paling akhir: {{ $coverage['latest'] }}. Ada periode sebelumnya yang belum lunas atau belum tercatat.</div>
    @endif
    @if($coverage['first_unpaid'])
        <div class="small text-danger">Tagihan belum lunas paling awal: {{ $coverage['first_unpaid'] }}</div>
    @endif
    <div class="small text-muted">Berdasarkan urutan tagihan yang tercatat sejak awal data. Termasuk bulan gratis; pembayaran menunggu konfirmasi belum dihitung lunas.</div>
</div>
@endif
