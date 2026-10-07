@extends('layouts.app')

@section('content')
<div class="role-page">
    <div class="role-hero"><div class="role-hero-copy"><span class="role-kicker">Transparansi</span><h3>Aktivitas Manajemen</h3><p>Riwayat perubahan penting oleh Admin Manajemen dan Bendahara.</p></div></div>
    <section class="role-table-card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Waktu</th><th>Pelaku</th><th>Aktivitas</th></tr></thead><tbody>
        @forelse($activities as $activity)
            <tr><td>{{ $activity->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }} WIB</td><td>{{ $activity->user?->name ?? 'Sistem' }}</td><td>{{ $activity->summary }}</td></tr>
        @empty
            <tr><td colspan="3" class="text-center text-muted py-4">Belum ada aktivitas.</td></tr>
        @endforelse
    </tbody></table></div><div class="p-3">{{ $activities->links() }}</div></section>
</div>
@endsection
