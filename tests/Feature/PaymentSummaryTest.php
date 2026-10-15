<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\MidtransPaymentProcessor;
use App\Services\WebNotificationService;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function prepareBills(): array
    {
        $this->mock(WebPushService::class, fn ($mock) => $mock->shouldReceive('sendToUsers')->andReturnNull());
        $class = Kelas::create(['nama_kelas' => 'X-A']);
        $year = TahunAjaran::create(['nama' => '2026/2027', 'is_active' => true]);
        $student = Siswa::create([
            'nis' => 'SUMMARY-001', 'nama' => 'Siswa Ringkasan', 'kelas_id' => $class->id,
            'nama_orang_tua' => 'Orang Tua', 'no_hp_orang_tua' => '6281200000000', 'status' => 'aktif',
        ]);
        $treasurer = User::create(['name' => 'Bendahara', 'username' => 'summary-treasurer', 'password' => bcrypt('password'), 'role' => 'bendahara']);
        User::create(['name' => 'Siswa', 'username' => $student->nis, 'password' => bcrypt('password'), 'role' => 'siswa', 'siswa_id' => $student->id]);
        $bills = collect(['Januari', 'Februari', 'Maret'])->map(fn ($month, $index) => Tagihan::create([
            'siswa_id' => $student->id, 'tahun_ajaran_id' => $year->id, 'bulan' => $month, 'tahun' => 2027,
            'nominal' => ($index + 1) * 100000, 'jatuh_tempo' => sprintf('2027-%02d-10', $index + 1), 'status' => 'belum_lunas',
        ]));

        return [$student, $treasurer, $bills];
    }

    public function test_midtrans_records_full_total_once_even_when_webhook_is_repeated(): void
    {
        [$student, $treasurer, $bills] = $this->prepareBills();
        $payment = Pembayaran::create([
            'siswa_id' => $student->id, 'tagihan_id' => $bills->last()->id, 'kode_invoice' => 'INV-SUMMARY',
            'metode' => 'midtrans', 'nominal' => 600000, 'status' => 'pending', 'midtrans_order_id' => 'SPP-ARR-SUMMARY',
        ]);
        $processor = app(MidtransPaymentProcessor::class);
        $notifications = app(WebNotificationService::class);
        $processor->process($payment, ['transaction_status' => 'pending'], $notifications);
        $this->assertDatabaseCount('activity_logs', 0);

        // No authenticated actor: this is also the webhook execution path.
        $processor->process($payment, ['transaction_status' => 'settlement'], $notifications);
        $this->assertDatabaseCount('activity_logs', 1);
        $activity = \App\Models\ActivityLog::firstOrFail();
        $this->assertNull($activity->user_id);
        $this->assertSame(600000, $activity->metadata['total']);
        $this->assertSame(3, $activity->metadata['months']);
        $this->assertStringContainsString('Maret 2027', $activity->summary);
        $this->assertStringContainsString('600.000', $activity->summary);
        $messages = \App\Models\WebNotification::all();
        $this->assertNotEmpty($messages);
        foreach ($messages as $message) {
            $this->assertStringContainsString('600.000', $message->message);
            $this->assertStringContainsString('3 bulan', $message->message);
        }
        $notificationCount = $messages->count();
        $processor->process($payment, ['transaction_status' => 'settlement'], $notifications);
        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertDatabaseCount('web_notifications', $notificationCount);
    }

    public function test_cash_cumulative_payment_records_activity_and_total_notification(): void
    {
        [$student, $treasurer, $bills] = $this->prepareBills();
        $this->actingAs($treasurer)->post(route('treasury.arrears.confirm-through', [$student, $bills->last()]))->assertRedirect();
        $this->assertDatabaseCount('pembayaran', 3);
        $this->assertDatabaseCount('activity_logs', 1);
        $activity = \App\Models\ActivityLog::firstOrFail();
        $this->assertSame(600000, $activity->metadata['total']);
        $this->assertSame(3, $activity->metadata['months']);
        $this->assertSame($treasurer->id, $activity->user_id);
        $studentMessage = \App\Models\WebNotification::where('title', 'Pembayaran SPP dikonfirmasi')->firstOrFail();
        $this->assertStringContainsString('600.000', $studentMessage->message);
        $this->assertStringContainsString('3 bulan', $studentMessage->message);
        $this->actingAs($treasurer)->post(route('treasury.arrears.confirm-through', [$student, $bills->last()]))->assertSessionHasErrors('tagihan');
        $this->assertDatabaseCount('activity_logs', 1);
    }
}
