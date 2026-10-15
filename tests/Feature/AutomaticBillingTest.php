<?php

namespace Tests\Feature;

use App\Models\AutoBillSetting;
use App\Models\BiayaSpp;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AutomaticBillingTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $this->travelTo(Carbon::parse('2026-10-15 12:00:00', 'Asia/Jakarta'));
        $class = Kelas::create(['nama_kelas' => 'X Perhotelan']);
        $year = TahunAjaran::create(['nama' => '2026/2027', 'is_active' => true]);
        BiayaSpp::create(['kelas_id' => $class->id, 'tahun_ajaran_id' => $year->id, 'nominal' => 300000]);
        $student = Siswa::create(['nis' => 'AUTO-1', 'nama' => 'Siswa', 'kelas_id' => $class->id,
            'nama_orang_tua' => 'Wali', 'no_hp_orang_tua' => '0800000000', 'status' => 'aktif']);
        $user = User::create(['name' => 'Siswa', 'username' => 'auto-student', 'password' => bcrypt('password'), 'role' => 'siswa', 'siswa_id' => $student->id]);
        $setting = AutoBillSetting::create(['is_enabled' => true, 'generate_day' => 10, 'due_day' => 20, 'starts_on' => '2026-10-01']);
        $this->actingAs($user);

        return [$student, $year, $setting];
    }

    public function test_student_opens_bills_and_current_month_is_synchronized_without_cron(): void
    {
        [$student, $year, $setting] = $this->fixture();
        $this->get(route('siswa.tagihan'))->assertOk()->assertSee('Oktober');
        $this->assertDatabaseHas('tagihan', ['siswa_id' => $student->id, 'tahun' => 2026, 'bulan' => 'Oktober', 'jatuh_tempo' => '2026-10-20']);
        $this->get(route('siswa.tagihan'))->assertOk();
        $this->assertDatabaseCount('tagihan', 1);
        $this->assertEquals('2026-10-01', $setting->fresh()->last_generated_period->toDateString());
    }

    public function test_release_date_and_disabled_setting_are_respected(): void
    {
        [, , $setting] = $this->fixture();
        $setting->update(['generate_day' => 20]);
        $this->get(route('siswa.tagihan'))->assertOk();
        $this->assertDatabaseCount('tagihan', 0);
        $setting->update(['generate_day' => 1, 'is_enabled' => false, 'last_checked_at' => null]);
        $this->travel(6)->minutes();
        $this->get(route('siswa.tagihan'))->assertOk();
        $this->assertDatabaseCount('tagihan', 0);
    }

    public function test_missed_months_catch_up_from_configured_start_without_changing_old_bills(): void
    {
        [$student, $year, $setting] = $this->fixture();
        $setting->update(['starts_on' => '2026-08-01']);
        $old = Tagihan::create(['siswa_id' => $student->id, 'tahun_ajaran_id' => $year->id, 'bulan' => 'September',
            'tahun' => 2025, 'nominal' => 250000, 'jatuh_tempo' => '2025-09-10', 'status' => 'belum_lunas']);
        DB::table('tagihan')->where('id', $old->id)->update(['created_at' => '2030-01-01 00:00:00']);
        $this->get(route('siswa.tagihan'))->assertOk()->assertViewHas('tagihan', fn ($bills) => $bills->first()->bulan === 'Oktober' && (int) $bills->first()->tahun === 2026);
        $this->assertDatabaseCount('tagihan', 4);
        $this->assertEquals(250000, $old->fresh()->nominal);
        $this->assertDatabaseHas('tagihan', ['bulan' => 'Agustus', 'tahun' => 2026]);
        $this->assertDatabaseMissing('tagihan', ['bulan' => 'November', 'tahun' => 2026]);
    }

    public function test_paid_future_period_under_another_academic_year_is_not_billed_twice(): void
    {
        [$student, , $setting] = $this->fixture();
        $oldYear = TahunAjaran::create(['nama' => '2025/2026', 'is_active' => false]);
        Tagihan::create(['siswa_id' => $student->id, 'tahun_ajaran_id' => $oldYear->id, 'bulan' => 'Oktober',
            'tahun' => 2026, 'nominal' => 250000, 'jatuh_tempo' => '2026-10-10', 'status' => 'lunas']);
        $this->get(route('siswa.tagihan'))->assertOk();
        $this->assertDatabaseCount('tagihan', 1);
    }

    public function test_missing_fee_can_be_retried_and_new_students_receive_current_bill(): void
    {
        [$student, $year, $setting] = $this->fixture();
        BiayaSpp::query()->delete();
        $this->get(route('siswa.tagihan'))->assertOk();
        $this->assertEquals(1, $setting->fresh()->last_missing_fee_count);
        BiayaSpp::create(['kelas_id' => $student->kelas_id, 'tahun_ajaran_id' => $year->id, 'nominal' => 300000]);
        $this->travel(6)->minutes();
        $this->get(route('siswa.tagihan'))->assertOk();
        $this->assertDatabaseCount('tagihan', 1);
        $other = Siswa::create(['nis' => 'AUTO-2', 'nama' => 'Baru', 'kelas_id' => $student->kelas_id,
            'nama_orang_tua' => 'Wali', 'no_hp_orang_tua' => '0800000000', 'status' => 'aktif']);
        $this->travel(6)->minutes();
        $this->get(route('siswa.tagihan'))->assertOk();
        $this->assertDatabaseCount('tagihan', 2);
        $this->assertDatabaseHas('tagihan', ['siswa_id' => $other->id, 'bulan' => 'Oktober']);
    }

    public function test_release_day_crosses_recent_check_and_due_date_can_be_in_next_month(): void
    {
        [, , $setting] = $this->fixture();
        $setting->update(['generate_day' => 20, 'due_day' => 10]);
        $this->travelTo(Carbon::parse('2026-10-19 23:59:00', 'Asia/Jakarta'));
        $this->get(route('siswa.tagihan'))->assertOk();
        $this->assertDatabaseCount('tagihan', 0);
        $this->travel(2)->minutes();
        $this->get(route('siswa.tagihan'))->assertOk();
        $this->assertDatabaseHas('tagihan', ['bulan' => 'Oktober', 'tahun' => 2026, 'jatuh_tempo' => '2026-11-10']);
    }

    public function test_november_rollover_does_not_backbill_a_new_student_for_october(): void
    {
        [$student, , $setting] = $this->fixture();
        $this->get(route('siswa.tagihan'))->assertOk();
        $this->travelTo(Carbon::parse('2026-11-10 12:00:00', 'Asia/Jakarta'));
        $other = Siswa::create(['nis' => 'AUTO-NOV', 'nama' => 'Baru November', 'kelas_id' => $student->kelas_id,
            'nama_orang_tua' => 'Wali', 'no_hp_orang_tua' => '0800000000', 'status' => 'aktif']);
        $this->get(route('siswa.tagihan'))->assertOk();
        $this->assertDatabaseCount('tagihan', 3);
        $this->assertDatabaseMissing('tagihan', ['siswa_id' => $other->id, 'bulan' => 'Oktober']);
        $this->assertDatabaseHas('tagihan', ['siswa_id' => $other->id, 'bulan' => 'November']);
    }

    public function test_admin_can_enable_schedule_and_cli_uses_the_same_idempotent_calendar(): void
    {
        [, , $setting] = $this->fixture();
        $setting->update(['is_enabled' => false]);
        $admin = User::create(['name' => 'TU', 'username' => 'auto-tu', 'password' => bcrypt('password'), 'role' => 'admin_tu']);
        $this->actingAs($admin)->post(route('admin.auto-bill.update'), ['is_enabled' => 'on', 'generate_day' => 1, 'due_day' => 10, 'starts_month' => '2026-10'])->assertRedirect();
        $this->assertDatabaseCount('tagihan', 1);
        $this->artisan('tagihan:generate-bulanan', ['--force' => true])->assertExitCode(0);
        $this->assertDatabaseCount('tagihan', 1);
        $this->get(route('admin.settings'))->assertOk()->assertSee('Otomatis aktif')->assertSee('Kalender Tagihan');
    }

    public function test_pending_future_invoice_reserves_current_month_until_cancelled(): void
    {
        [, , $setting] = $this->fixture();
        $this->post(route('siswa.future-payment.start'), ['until' => '2026-12', 'method' => 'tunai'])->assertRedirect();
        $payment = Pembayaran::firstOrFail();
        $this->get(route('siswa.tagihan'))->assertOk()->assertSee('Lihat Pembayaran Pending');
        $this->assertDatabaseCount('tagihan', 0);
        $this->post(route('siswa.payments.cancel', $payment))->assertRedirect();
        $this->get(route('siswa.tagihan'))->assertOk();
        $this->assertDatabaseCount('tagihan', 1);
        $this->assertDatabaseHas('tagihan', ['bulan' => 'Oktober', 'tahun' => 2026]);
        $this->assertDatabaseMissing('tagihan', ['bulan' => 'November', 'tahun' => 2026]);
    }
}
