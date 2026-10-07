<?php

namespace App\Models;

use App\Services\BrevoTransactionalMail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'username', 'email', 'nip', 'is_report_signer', 'password', 'role', 'siswa_id', 'kelas_id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_report_signer' => 'boolean'];
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function webNotifications()
    {
        return $this->hasMany(WebNotification::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function isManagementStaff(): bool
    {
        return in_array($this->role, ['admin_tu', 'bendahara', 'kepala_sekolah'], true);
    }

    public function sendPasswordResetNotification($token): void
    {
        if (blank(config('services.brevo.api_key'))) {
            $this->notify(new ResetPassword($token));

            return;
        }

        app(BrevoTransactionalMail::class)->sendPasswordReset($this, $token);
    }
}
