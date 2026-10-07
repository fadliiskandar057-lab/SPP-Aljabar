<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class BrevoTransactionalMail
{
    public function sendPasswordReset(User $user, string $token): void
    {
        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ], false));

        Http::withHeaders(['api-key' => config('services.brevo.api_key')])
            ->acceptJson()
            ->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => [
                    'email' => config('services.brevo.sender_email'),
                    'name' => config('services.brevo.sender_name'),
                ],
                'to' => [[
                    'email' => $user->getEmailForPasswordReset(),
                    'name' => $user->name,
                ]],
                'subject' => 'Atur ulang kata sandi '.config('app.name'),
                'htmlContent' => $this->passwordResetHtml($user, $resetUrl),
            ])
            ->throw();
    }

    private function passwordResetHtml(User $user, string $resetUrl): string
    {
        $name = e($user->name);
        $url = e($resetUrl);
        $appName = e(config('app.name'));

        return <<<HTML
<!doctype html>
<html lang="id"><body style="margin:0;background:#f5fcff;font-family:Arial,sans-serif;color:#102235">
    <main style="max-width:560px;margin:24px auto;padding:32px;background:#ffffff;border:1px solid #d9e8ef;border-radius:8px">
        <h1 style="margin:0 0 16px;font-size:24px;color:#162d78">Atur ulang kata sandi</h1>
        <p>Halo {$name},</p>
        <p>Kami menerima permintaan untuk mengatur ulang kata sandi akun {$appName} Anda. Klik tombol di bawah untuk melanjutkan.</p>
        <p style="margin:28px 0"><a href="{$url}" style="display:inline-block;padding:12px 18px;background:#162d78;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:bold">Atur ulang kata sandi</a></p>
        <p>Tautan ini berlaku selama 60 menit. Jika Anda tidak meminta perubahan kata sandi, abaikan email ini.</p>
    </main>
</body></html>
HTML;
    }
}
