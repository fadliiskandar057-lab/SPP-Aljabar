<div class="table-responsive">
<table class="table table-hover align-middle mb-0" id="{{ $tableId ?? 'paymentsTable' }}">
    <thead><tr><th>Invoice</th><th>Siswa</th><th>Bulan</th><th>Tanggal</th><th>Metode</th><th>Nominal</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody id="{{ $tbodyId ?? '' }}">
        @include('partials.payment_rows', ['payments' => $payments])
    </tbody>
</table>
</div>
