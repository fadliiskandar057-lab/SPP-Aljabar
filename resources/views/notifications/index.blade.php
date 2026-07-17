@extends('layouts.app')

@section('content')
<div class="page-title">
    <div>
        <h3>Notifikasi</h3>
        <p>Riwayat pemberitahuan aktivitas pembayaran dan tagihan akun Anda.</p>
    </div>
    <form method="post" action="{{ route('notifications.read-all') }}">
        @csrf
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-check2-all"></i>Tandai Dibaca</button>
    </form>
</div>

<div class="content-card p-3">
    <div class="notification-list">
        @forelse($notifications as $notification)
            <form method="post" action="{{ route('notifications.read', $notification) }}" class="notification-row {{ $notification->read_at ? '' : 'is-unread' }}">
                @csrf
                <button type="submit">
                    <span class="notification-icon notification-{{ $notification->type }}"><i class="bi bi-bell"></i></span>
                    <span class="notification-copy">
                        <strong>{{ $notification->title }}</strong>
                        <small>{{ $notification->message }}</small>
                        <em>{{ $notification->created_at->diffForHumans() }}</em>
                    </span>
                </button>
            </form>
        @empty
            <div class="empty-state">Belum ada notifikasi.</div>
        @endforelse
    </div>
    <div class="mt-3">{{ $notifications->links() }}</div>
</div>
@endsection
