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
        $this->travelBack();
    }
}
