<?php

namespace App\Services;

use App\Models\User;
use App\Models\WebNotification;
use Illuminate\Support\Collection;

class WebNotificationService
{
    public function toUser(?User $user, string $title, ?string $message = null, ?string $url = null, string $type = 'info'): void
    {
        if (! $user) {
            return;
        }

        WebNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'url' => $url,
        ]);
    }

    public function toUsers(Collection $users, string $title, ?string $message = null, ?string $url = null, string $type = 'info'): void
    {
        $users->each(fn (User $user) => $this->toUser($user, $title, $message, $url, $type));
    }

    public function toRole(string $role, string $title, ?string $message = null, ?string $url = null, string $type = 'info'): void
    {
        $this->toUsers(User::where('role', $role)->get(), $title, $message, $url, $type);
    }

    public function toStudent(int $siswaId, string $title, ?string $message = null, ?string $url = null, string $type = 'info'): void
    {
        $this->toUsers(User::where('role', 'siswa')->where('siswa_id', $siswaId)->get(), $title, $message, $url, $type);
    }

    public function toClassGuardians(int $kelasId, string $title, ?string $message = null, ?string $url = null, string $type = 'info'): void
    {
        $this->toUsers(User::where('role', 'wali_kelas')->where('kelas_id', $kelasId)->get(), $title, $message, $url, $type);
    }
}
