<?php

namespace Tests\Feature;

use App\Models\BiayaSpp;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuturePaymentTest extends TestCase
{
    use RefreshDatabase;

    private function studentFixture(): array
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-15'));
        $kelas = Kelas::create(['nama_kelas' => 'X']);
        $year = TahunAjaran::create(['nama' => '2026/2027', 'is_active' => true]);
        BiayaSpp::create(['kelas_id' => $kelas->id, 'tahun_ajaran_id' => $year->id, 'nominal' => 100000]);
        $student = Siswa::create(['nis' => 'ST-FUT-1', 'nama' => 'Siswa', 'kelas_id' => $kelas->id,
            'nama_orang_tua' => 'Wali', 'no_hp_orang_tua' => '0800000000', 'status' => 'aktif']);
        $user = User::create(['name' => 'Siswa', 'username' => 'student-future', 'password' => bcrypt('password'), 'role' => 'siswa', 'siswa_id' => $student->id]);
        $treasurer = User::create(['name' => 'Bendahara', 'username' => 'treasurer-future', 'password' => bcrypt('password'), 'role' => 'bendahara']);
        $this->actingAs($user);
        return [$student, $user, $treasurer, $year];
    }

    public function test_preview_shows_methods_and_never_creates_bills_even_for_twelve_years(): void
    {
        $this->studentFixture();
        $this->get(route('siswa.tagihan'))->assertOk()->assertSee('Bayar Masa Depan');
        foreach (['2026-12' => 3, '2029-10' => 37, '2038-10' => 145] as $until => $count) {
            $this->post(route('siswa.future-payment'), ['until' => $until])
                ->assertOk()->assertViewIs('siswa.future_payment_methods')
                ->assertSee('Bayar Tunai')->assertSee('Bayar Online')
                ->assertViewHas('plan', fn ($plan) => count($plan['items']) === $count && $plan['total'] === $count * 100000);
        }
        foreach (['2026-09', '2126-11', 'invalid'] as $until) {
            $this->post(route('siswa.future-payment'), ['until' => $until])->assertSessionHasErrors('until');
        }
        $this->get(route('siswa.tagihan'))->assertOk();
        $this->assertDatabaseCount('tagihan', 0);
        $this->assertDatabaseCount('pembayaran', 0);
    }

    public function test_cash_invoice_creates_no_bills_and_cancellation_leaves_no_future_bills(): void
    {
        [$student] = $this->studentFixture();
        $this->post(route('siswa.future-payment.start'), ['until' => '2026-12', 'method' => 'tunai', 'nominal' => 1, 'siswa_id' => 999])
            ->assertRedirect();
        $payment = \App\Models\Pembayaran::firstOrFail();
        $this->assertEquals($student->id, $payment->siswa_id);
        $this->assertEquals(300000, $payment->nominal);
        $this->assertNull($payment->tagihan_id);
        $this->assertDatabaseCount('tagihan', 0);
        $this->get(route('invoice.show', $payment))->assertOk()->assertSee('3 bulan sampai');
        $this->get(route('siswa.riwayat'))->assertOk();
        $this->post(route('siswa.future-payment.start'), ['until' => '2026-12', 'method' => 'tunai'])->assertSessionHasErrors('until');
        $this->assertDatabaseCount('pembayaran', 1);
        $this->post(route('siswa.payments.cancel', $payment))->assertRedirect(route('siswa.tagihan'));
        $this->assertDatabaseCount('tagihan', 0);
        $this->assertEquals('cancelled', $payment->fresh()->status);
        $this->post(route('siswa.future-payment'), ['until' => '2026-12'])->assertOk();
    }

    public function test_cash_confirmation_materializes_all_selected_months_once(): void
    {
        [$student, $user, $treasurer] = $this->studentFixture();
        $this->post(route('siswa.future-payment.start'), ['until' => '2026-12', 'method' => 'tunai'])->assertRedirect();
        $payment = \App\Models\Pembayaran::firstOrFail();
        $this->actingAs($treasurer)->get(route('treasury.cash.queue'))->assertOk();
        $this->post(route('treasury.cash.confirm', $payment))->assertRedirect();
        $this->assertDatabaseCount('tagihan', 3);
        $this->assertDatabaseCount('pembayaran', 3);
        $this->assertEquals(3, $student->tagihan()->where('status', 'lunas')->count());
        $this->assertEquals(300000, (int) \App\Models\Pembayaran::sum('nominal'));
        $this->assertEquals(100000, $payment->fresh()->nominal);
        $this->assertNotNull($payment->fresh()->tagihan_id);
        $this->assertSame(300000, \App\Models\ActivityLog::where('action', 'payment.cash_confirmed')->firstOrFail()->metadata['total']);
        $this->assertStringContainsString('3 bulan', \App\Models\WebNotification::where('title', 'Pembayaran tunai dikonfirmasi')->firstOrFail()->message);
        $this->post(route('treasury.cash.confirm', $payment))->assertSessionHasErrors('pembayaran');
        $this->assertDatabaseCount('tagihan', 3);
        $this->actingAs($user)->post(route('siswa.payments.cancel', $payment))->assertSessionHasErrors('pembayaran');
        $this->get(route('invoice.download', $payment))->assertOk();
    }

    public function test_online_failure_rolls_back_invoice_and_preserves_existing_bills(): void
    {
        [$student, , , $year] = $this->studentFixture();
        $bill = \App\Models\Tagihan::create(['siswa_id' => $student->id, 'tahun_ajaran_id' => $year->id,
            'bulan' => 'Oktober', 'tahun' => 2026, 'nominal' => 100000, 'jatuh_tempo' => '2026-10-10', 'status' => 'belum_lunas']);
        $this->mock(\App\Services\MidtransService::class, fn ($mock) => $mock->shouldReceive('createSnapToken')->once()->andThrow(new \RuntimeException('Gateway unavailable')));
        $this->post(route('siswa.future-payment.start'), ['until' => '2026-12', 'method' => 'midtrans'])->assertSessionHasErrors('until');
        $this->assertDatabaseCount('tagihan', 1);
        $this->assertDatabaseCount('pembayaran', 0);
        $this->assertEquals('belum_lunas', $bill->fresh()->status);
    }

    public function test_verified_online_payment_materializes_bills_once_and_keeps_invoice_total(): void
    {
        [$student] = $this->studentFixture();
        $this->mock(\App\Services\MidtransService::class, fn ($mock) => $mock->shouldReceive('createSnapToken')->once()->andReturn('test-snap-token'));
        $this->post(route('siswa.future-payment.start'), ['until' => '2026-12', 'method' => 'midtrans'])->assertOk()->assertViewIs('siswa.midtrans');
        $payment = \App\Models\Pembayaran::firstOrFail();
        $this->assertDatabaseCount('tagihan', 0);
        $processor = app(\App\Services\MidtransPaymentProcessor::class);
        $notifications = app(\App\Services\WebNotificationService::class);
        $processor->process($payment, ['transaction_status' => 'pending'], $notifications);
        $this->assertDatabaseCount('tagihan', 0);
        $processor->process($payment, ['transaction_status' => 'settlement', 'transaction_id' => 'test-id'], $notifications);
        $this->assertDatabaseCount('tagihan', 3);
        $this->assertEquals(3, $student->tagihan()->where('status', 'lunas')->count());
        $this->assertEquals(300000, (int) \App\Models\Pembayaran::sum('nominal'));
        $this->assertEquals(100000, $payment->fresh()->nominal);
        $processor->process($payment, ['transaction_status' => 'settlement'], $notifications);
        $processor->process($payment, ['transaction_status' => 'expire'], $notifications);
        $this->assertDatabaseCount('tagihan', 3);
        $this->assertDatabaseCount('pembayaran', 3);
        $this->assertEquals('settlement', $payment->fresh()->status);
    }

    public function test_expired_online_payment_creates_no_bills_and_releases_existing_bill(): void
    {
        [$student, , , $year] = $this->studentFixture();
        $bill = \App\Models\Tagihan::create(['siswa_id' => $student->id, 'tahun_ajaran_id' => $year->id,
            'bulan' => 'Oktober', 'tahun' => 2026, 'nominal' => 100000, 'jatuh_tempo' => '2026-10-10', 'status' => 'belum_lunas']);
        $this->mock(\App\Services\MidtransService::class, fn ($mock) => $mock->shouldReceive('createSnapToken')->once()->andReturn('test-snap-token'));
        $this->post(route('siswa.future-payment.start'), ['until' => '2026-12', 'method' => 'midtrans'])->assertOk();
        $this->assertEquals('menunggu_konfirmasi', $bill->fresh()->status);
        $this->travel(31)->minutes();
        app(\App\Services\MidtransPendingPaymentCleaner::class)->deleteExpired();
        $this->assertDatabaseCount('tagihan', 1);
        $this->assertDatabaseCount('pembayaran', 0);
        $this->assertEquals('belum_lunas', $bill->fresh()->status);
    }

    public function test_discounts_and_free_months_are_quoted_without_writes_then_recorded_on_confirmation(): void
    {
        [$student, , $treasurer, $year] = $this->studentFixture();
        \App\Models\TagihanExemption::create(['tahun_ajaran_id' => $year->id, 'bulan' => 'November', 'tahun' => 2026,
            'scope_type' => 'siswa', 'siswa_id' => $student->id, 'benefit_type' => 'free']);
        \App\Models\TagihanExemption::create(['tahun_ajaran_id' => $year->id, 'bulan' => 'Desember', 'tahun' => 2026,
            'scope_type' => 'kelas', 'kelas_id' => $student->kelas_id, 'benefit_type' => 'percent', 'amount' => 50]);
        $this->post(route('siswa.future-payment'), ['until' => '2026-12'])->assertOk()
            ->assertViewHas('plan', fn ($plan) => $plan['total'] === 150000);
        $this->assertDatabaseCount('tagihan', 0);
        $this->post(route('siswa.future-payment.start'), ['until' => '2026-12', 'method' => 'tunai'])->assertRedirect();
        $payment = \App\Models\Pembayaran::firstOrFail();
        $this->actingAs($treasurer)->post(route('treasury.cash.confirm', $payment))->assertRedirect();
        $this->assertDatabaseCount('tagihan', 3);
        $this->assertDatabaseCount('pembayaran', 2);
        $this->assertDatabaseHas('tagihan', ['bulan' => 'November', 'nominal' => 0, 'status' => 'gratis']);
        $this->assertDatabaseHas('tagihan', ['bulan' => 'Desember', 'nominal' => 50000, 'status' => 'lunas']);
        $this->assertEquals(150000, \App\Models\Pembayaran::sum('nominal'));
    }

    public function test_gateway_expiry_leaves_no_future_bills_and_other_students_cannot_cancel(): void
    {
        [$student, $user] = $this->studentFixture();
        $this->mock(\App\Services\MidtransService::class, fn ($mock) => $mock->shouldReceive('createSnapToken')->once()->andReturn('test-snap-token'));
        $this->post(route('siswa.future-payment.start'), ['until' => '2026-12', 'method' => 'midtrans'])->assertOk();
        $payment = \App\Models\Pembayaran::firstOrFail();
        $other = User::create(['name' => 'Other', 'username' => 'other-student', 'password' => bcrypt('password'), 'role' => 'siswa']);
        $this->actingAs($other)->post(route('siswa.payments.cancel', $payment))->assertForbidden();
        $this->actingAs($user);
        app(\App\Services\MidtransPaymentProcessor::class)->process($payment, ['transaction_status' => 'expire'], app(\App\Services\WebNotificationService::class));
        $this->assertDatabaseCount('tagihan', 0);
        $this->assertEquals('expired', $payment->fresh()->status);
        $this->post(route('siswa.future-payment'), ['until' => '2026-12'])->assertOk();
    }

    public function test_preparing_future_payments_shows_methods_without_settling_bills(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-15'));
        $user = User::create(['name' => 'Bendahara', 'username' => 'future-test', 'password' => bcrypt('password'), 'role' => 'bendahara']);
        $kelas = Kelas::create(['nama_kelas' => 'X']);
        $year = TahunAjaran::create(['nama' => '2026/2027', 'is_active' => true]);
        $student = Siswa::create([
            'nis' => 'FUT-001', 'nama' => 'Siswa Uji', 'kelas_id' => $kelas->id,
            'nama_orang_tua' => 'Orang Tua', 'no_hp_orang_tua' => '0800000000', 'status' => 'aktif',
        ]);
        BiayaSpp::create(['kelas_id' => $kelas->id, 'tahun_ajaran_id' => $year->id, 'nominal' => 100000]);
        $data = ['siswa_id' => $student->id, 'tahun_ajaran_id' => $year->id, 'until_month' => 12, 'until_year' => 2026];

        $response = $this->actingAs($user)->post(route('treasury.arrears.future-payment'), $data);
        $response->assertOk()->assertViewIs('admin.future_payment_methods')
            ->assertSee('Konfirmasi Bayar Tunai')->assertSee('Bayar melalui Midtrans')
            ->assertSee('300.000');
        $this->assertDatabaseCount('tagihan', 3);
        $this->assertDatabaseCount('pembayaran', 0);
        $this->assertDatabaseMissing('tagihan', ['status' => 'lunas']);

        $this->post(route('treasury.arrears.future-payment'), $data)->assertOk();
        $this->assertDatabaseCount('tagihan', 3);
        $this->assertDatabaseCount('pembayaran', 0);

        $data['until_month'] = 9;
        $this->post(route('treasury.arrears.future-payment'), $data)->assertSessionHasErrors('until_month');

        $student->tagihan()->update(['status' => 'lunas']);
        $nextYear = TahunAjaran::create(['nama' => '2027/2028', 'is_active' => false]);
        $data = ['siswa_id' => $student->id, 'tahun_ajaran_id' => $nextYear->id, 'until_month' => 1, 'until_year' => 2027];
        $this->post(route('treasury.arrears.future-payment'), $data)->assertOk()->assertSee('100.000');
        $this->assertDatabaseCount('tagihan', 4);
        $this->assertDatabaseHas('tagihan', ['siswa_id' => $student->id, 'bulan' => 'Januari', 'tahun' => 2027, 'status' => 'belum_lunas']);
        $this->assertDatabaseCount('pembayaran', 0);

        $this->get(route('treasury.arrears.students'))->assertOk()
            ->assertSee('futureStudentSummary')->assertSee('futureUnpaidList')->assertSee('futurePaymentRange');

        $payment = \App\Models\Pembayaran::create([
            'siswa_id' => $student->id, 'tagihan_id' => $student->tagihan()->where('status', 'belum_lunas')->firstOrFail()->id,
            'kode_invoice' => 'INV-FINISH-TEST', 'metode' => 'midtrans', 'nominal' => 100000, 'status' => 'pending',
        ]);
        $this->mock(\App\Services\MidtransService::class, function ($mock) {
            $mock->shouldReceive('getTransactionStatus')->twice()->andReturn(['transaction_status' => 'pending']);
        });
        $this->mock(\App\Services\MidtransPaymentProcessor::class, function ($mock) {
            $mock->shouldReceive('process')->twice()->andReturn('pending', 'settlement');
        });
        $this->postJson(route('treasury.arrears.midtrans.finish', $payment))
            ->assertOk()->assertJson(['status' => 'pending', 'paid' => false]);
        $this->postJson(route('treasury.arrears.midtrans.finish', $payment))
            ->assertOk()->assertJson(['status' => 'settlement', 'paid' => true]);
        $this->travelBack();
    }
}
