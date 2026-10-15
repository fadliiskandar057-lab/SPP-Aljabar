<?php

namespace Tests\Feature;

use App\Exports\SiswaExport;
use App\Imports\SiswaImport;
use App\Models\BiayaSpp;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\FuturePaymentService;
use App\Services\GenerateMonthlyBillsService;
use App\Services\StudentFeeResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentProgressionTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $this->travelTo(Carbon::parse('2026-10-15'));
        $admin = User::create(['name' => 'TU', 'username' => 'tu', 'password' => bcrypt('password'), 'role' => 'admin_tu']);
        $oldYear = TahunAjaran::create(['nama' => '2025/2026', 'is_active' => false]);
        $newYear = TahunAjaran::create(['nama' => '2026/2027', 'is_active' => true]);
        $classes = collect([10, 11, 12])->map(fn ($level) => Kelas::create(['nama_kelas' => $level.' Perhotelan A', 'jenjang' => 'SMK', 'tingkat' => $level, 'jurusan' => 'Perhotelan']));
        foreach ($classes as $class) {
            BiayaSpp::create(['kelas_id' => $class->id, 'tahun_ajaran_id' => $oldYear->id, 'nominal' => 300000]);
            BiayaSpp::create(['kelas_id' => $class->id, 'tahun_ajaran_id' => $newYear->id, 'nominal' => 500000]);
        }
        $students = collect([0, 1, 2])->map(fn ($i) => Siswa::create(['nis' => 'PROM-'.$i, 'nama' => 'Siswa '.$i,
            'kelas_id' => $classes[$i]->id, 'angkatan' => 2025 - $i, 'tahun_ajaran_id' => $oldYear->id,
            'nama_orang_tua' => 'Wali', 'no_hp_orang_tua' => '0800000000', 'status' => 'aktif']));
        $data = ['from_year_id' => $oldYear->id, 'to_year_id' => $newYear->id, 'effective_month' => '2026-07',
            'student_ids' => $students->pluck('id')->all(), 'classes' => [
                $classes[0]->id => ['action' => 'naik', 'target_class_id' => $classes[1]->id, 'nominal' => null],
                $classes[1]->id => ['action' => 'naik', 'target_class_id' => $classes[2]->id, 'nominal' => 350000],
                $classes[2]->id => ['action' => 'lulus', 'target_class_id' => null, 'nominal' => null],
            ]];
        $this->actingAs($admin);

        return [$students, $classes, $oldYear, $newYear, $data];
    }

    private function preview(array $data): string
    {
        $response = $this->post(route('admin.progression.preview'), $data);
        $response->assertOk()->assertViewIs('admin.progression_preview');

        return $response->viewData('token');
    }

    public function test_batch_preview_and_confirmation_preserve_old_bills_and_carry_or_change_tariffs(): void
    {
        [$students, $classes, $oldYear, $newYear, $data] = $this->fixture();
        $bill = Tagihan::create(['siswa_id' => $students[0]->id, 'tahun_ajaran_id' => $oldYear->id, 'bulan' => 'Juni',
            'tahun' => 2026, 'nominal' => 300000, 'jatuh_tempo' => '2026-06-10', 'status' => 'lunas']);
        $this->get(route('admin.progression.index'))->assertOk()->assertSee('Kenaikan Kelas');
        $token = $this->preview($data);
        $this->assertEquals($classes[0]->id, $students[0]->fresh()->kelas_id);
        $this->assertDatabaseCount('student_progressions', 0);
        $this->post(route('admin.progression.confirm'), ['token' => $token])->assertRedirect(route('admin.progression.index'));
        $this->assertEquals($classes[1]->id, $students[0]->fresh()->kelas_id);
        $this->assertEquals($classes[2]->id, $students[1]->fresh()->kelas_id);
        $this->assertEquals('lulus', $students[2]->fresh()->status);
        $this->assertEquals(2025, $students[0]->fresh()->angkatan);
        $this->assertDatabaseCount('student_progressions', 3);
        $this->assertDatabaseCount('student_fee_rates', 2);
        $this->assertEquals(300000, $bill->fresh()->nominal);
        $this->assertEquals('10 Perhotelan A', $bill->fresh()->kelas_nama);
        $this->assertEquals($classes[0]->id, $bill->fresh()->kelas_id);
        $generator = app(GenerateMonthlyBillsService::class);
        $generator->generate($newYear->id, 'Oktober', 2026, '2026-10-10');
        $this->assertDatabaseHas('tagihan', ['siswa_id' => $students[0]->id, 'bulan' => 'Oktober', 'nominal' => 300000, 'kelas_nama' => '11 Perhotelan A']);
        $this->assertDatabaseHas('tagihan', ['siswa_id' => $students[1]->id, 'bulan' => 'Oktober', 'nominal' => 350000]);
        $this->assertDatabaseMissing('tagihan', ['siswa_id' => $students[2]->id]);
        $treasurer = User::create(['name' => 'Treasury', 'username' => 'report-treasury', 'password' => bcrypt('password'), 'role' => 'bendahara']);
        $admin = auth()->user();
        $this->actingAs($treasurer)->get(route('treasury.laporan', ['kelas_id' => $classes[0]->id]))->assertOk()
            ->assertViewHas('reportRows', fn ($rows) => $rows->count() === 1 && $rows->first()['kelas_nama'] === '10 Perhotelan A');
        $this->actingAs($admin);
        $this->post(route('admin.progression.confirm'), ['token' => $token])->assertSessionHasErrors('progression');
        $this->assertDatabaseCount('student_progressions', 3);
    }

    public function test_per_student_exceptions_and_stale_preview_are_atomic(): void
    {
        [$students, $classes, , , $data] = $this->fixture();
        $data['exceptions'] = [$students[0]->id => ['action' => 'tetap', 'target_class_id' => null, 'nominal' => null]];
        $token = $this->preview($data);
        $students[1]->update(['status' => 'keluar']);
        $this->post(route('admin.progression.confirm'), ['token' => $token])->assertSessionHasErrors();
        $this->assertDatabaseCount('student_progressions', 0);
        $students[1]->update(['status' => 'aktif']);
        $token = $this->preview($data);
        $this->post(route('admin.progression.confirm'), ['token' => $token])->assertRedirect();
        $this->assertEquals($classes[0]->id, $students[0]->fresh()->kelas_id);
        $this->assertDatabaseHas('student_progressions', ['siswa_id' => $students[0]->id, 'action' => 'tetap']);
    }

    public function test_semester_rate_changes_only_uncreated_periods_and_preserves_prepayment(): void
    {
        [$students, , , $year] = $this->fixture();
        $student = $students[0];
        $generator = app(GenerateMonthlyBillsService::class);
        $generator->generate($year->id, 'Januari', 2027, '2027-01-10', $student->id);
        $student->tagihan()->update(['status' => 'lunas']);
        $this->post(route('admin.fee-rates.store'), ['batch_payload' => json_encode(['student_ids' => [$student->id], 'tahun_ajaran_id' => $year->id,
            'semester' => 'genap', 'effective_month' => '2027-01', 'nominal' => 450000])])->assertRedirect();
        $generator->generate($year->id, 'Januari', 2027, '2027-01-10', $student->id);
        $generator->generate($year->id, 'Februari', 2027, '2027-02-10', $student->id);
        $this->assertDatabaseHas('tagihan', ['bulan' => 'Januari', 'nominal' => 500000, 'status' => 'lunas']);
        $this->assertDatabaseHas('tagihan', ['bulan' => 'Februari', 'nominal' => 450000, 'status' => 'belum_lunas']);
        $resolver = app(StudentFeeResolver::class);
        $this->assertEquals(500000, $resolver->nominal($student->fresh(), $year->id, Carbon::parse('2026-12-01')));
        $this->assertEquals(450000, $resolver->nominal($student->fresh(), $year->id, Carbon::parse('2027-02-01')));
    }

    public function test_only_tu_can_promote_and_duplicate_year_is_rejected(): void
    {
        [$students, , , , $data] = $this->fixture();
        $token = $this->preview($data);
        $this->post(route('admin.progression.confirm'), ['token' => $token])->assertRedirect();
        $this->post(route('admin.progression.preview'), $data)->assertSessionHasErrors();
        $studentUser = User::create(['name' => 'Siswa', 'username' => 'student', 'password' => bcrypt('password'), 'role' => 'siswa', 'siswa_id' => $students[0]->id]);
        $this->actingAs($studentUser)->get(route('admin.progression.index'))->assertForbidden();
        $this->post(route('admin.progression.preview'), $data)->assertForbidden();
        $this->post(route('admin.fee-rates.store'), [])->assertForbidden();
    }

    public function test_future_quote_uses_semester_tariff_and_preserves_prepaid_period(): void
    {
        [$students, $classes, $oldYear, $newYear, $data] = $this->fixture();
        $student = $students[0];
        Tagihan::create(['siswa_id' => $student->id, 'tahun_ajaran_id' => $oldYear->id, 'bulan' => 'Juni',
            'tahun' => 2026, 'nominal' => 300000, 'jatuh_tempo' => '2026-06-10', 'status' => 'belum_lunas']);
        Tagihan::create(['siswa_id' => $student->id, 'tahun_ajaran_id' => $newYear->id, 'bulan' => 'Januari',
            'tahun' => 2027, 'nominal' => 500000, 'jatuh_tempo' => '2027-01-10', 'status' => 'lunas']);
        $data['student_ids'] = [$student->id];
        $data['classes'][$classes[0]->id]['nominal'] = 350000;
        $token = $this->preview($data);
        $this->post(route('admin.progression.confirm'), ['token' => $token])->assertRedirect();
        $this->post(route('admin.fee-rates.store'), ['student_ids' => [$student->id], 'tahun_ajaran_id' => $newYear->id,
            'semester' => 'genap', 'effective_month' => '2027-01', 'nominal' => 450000])->assertRedirect();
        $plan = app(FuturePaymentService::class)->quote($student->fresh(), '2027-02');
        $this->assertEquals(2850000, $plan['total']);
        $this->assertCount(8, $plan['items']);
        $this->assertEquals('11 Perhotelan A', collect($plan['items'])->firstWhere('bulan', 'Juli')['kelas_nama']);
        $this->assertEquals(500000, $student->tagihan()->where('bulan', 'Januari')->firstOrFail()->nominal);
        $this->assertDatabaseCount('tagihan', 2);
    }

    public function test_graduate_keeps_login_and_can_pay_old_debt_but_cannot_create_new_future_bills(): void
    {
        [$students, , $oldYear, , $data] = $this->fixture();
        $student = $students[2];
        $bill = Tagihan::create(['siswa_id' => $student->id, 'tahun_ajaran_id' => $oldYear->id, 'bulan' => 'Juni',
            'tahun' => 2026, 'nominal' => 300000, 'jatuh_tempo' => '2026-06-10', 'status' => 'belum_lunas']);
        $data['student_ids'] = [$student->id];
        $token = $this->preview($data);
        $this->post(route('admin.progression.confirm'), ['token' => $token])->assertRedirect();
        $user = User::create(['name' => 'Alumni', 'username' => 'alumni', 'password' => bcrypt('password'), 'role' => 'siswa', 'siswa_id' => $student->id]);
        $this->actingAs($user)->get(route('siswa.tagihan'))->assertOk()->assertDontSee('id="bayar-masa-depan"', false);
        $this->get(route('siswa.profil'))->assertOk()->assertSee('Riwayat Kelas');
        $this->post(route('siswa.future-payment'), ['until' => '2026-12'])->assertForbidden();
        $this->post(route('siswa.cash', $bill))->assertRedirect();
        $this->assertDatabaseCount('tagihan', 1);
    }

    public function test_json_batch_preview_and_class_metadata_validation(): void
    {
        [, $classes, , , $data] = $this->fixture();
        $this->post(route('admin.progression.preview'), ['batch_payload' => json_encode($data)])
            ->assertOk()->assertViewIs('admin.progression_preview');
        $this->put(route('admin.kelas.update', $classes[0]), ['nama_kelas' => 'SD 6 A', 'jenjang' => 'SD', 'tingkat' => 10])
            ->assertSessionHasErrors('tingkat');
        $this->get(route('admin.kelas'))->assertOk()->assertSee('Jenjang');
        $this->assertDatabaseCount('student_progressions', 0);
    }

    public function test_changed_fee_invalidates_preview_without_partial_updates(): void
    {
        [$students, $classes, $oldYear, , $data] = $this->fixture();
        $token = $this->preview($data);
        BiayaSpp::where('tahun_ajaran_id', $oldYear->id)->where('kelas_id', $classes[0]->id)->update(['nominal' => 325000]);
        $this->post(route('admin.progression.confirm'), ['token' => $token])->assertSessionHasErrors('progression');
        $this->assertDatabaseCount('student_progressions', 0);
        $this->assertEquals($classes[0]->id, $students[0]->fresh()->kelas_id);
    }

    public function test_graduation_requires_pending_future_invoice_to_be_cancelled_first(): void
    {
        [$students, , , , $data] = $this->fixture();
        $admin = auth()->user();
        $student = $students[2];
        $user = User::create(['name' => 'Siswa XII', 'username' => 'pending-graduate', 'password' => bcrypt('password'), 'role' => 'siswa', 'siswa_id' => $student->id]);
        $this->actingAs($user)->post(route('siswa.future-payment.start'), ['until' => '2026-12', 'method' => 'tunai'])->assertRedirect();
        $payment = Pembayaran::firstOrFail();
        $data['student_ids'] = [$student->id];
        $this->actingAs($admin)->post(route('admin.progression.preview'), $data)->assertSessionHasErrors('progression');
        $this->assertEquals('aktif', $student->fresh()->status);
        $this->assertDatabaseCount('student_progressions', 0);
        $this->actingAs($user)->post(route('siswa.payments.cancel', $payment))->assertRedirect();
        $this->actingAs($admin);
        $this->preview($data);
    }

    public function test_cohort_and_academic_year_survive_student_export_and_import(): void
    {
        [$students] = $this->fixture();
        $export = new SiswaExport;
        $records = collect($export->array())->map(fn ($row) => collect(array_combine($export->headings(), $row)));
        $students[0]->update(['angkatan' => null, 'tahun_ajaran_id' => null]);
        $import = new SiswaImport;
        $import->collection($records);
        $this->assertEquals(0, $import->skipped);
        $this->assertEquals(2025, $students[0]->fresh()->angkatan);
        $this->assertNotNull($students[0]->fresh()->tahun_ajaran_id);
        $this->assertDatabaseCount('siswa', 3);
    }

    public function test_whole_school_batch_uses_bounded_database_round_trips(): void
    {
        [, $classes, $oldYear, , $data] = $this->fixture();
        $rows = [];
        for ($i = 0; $i < 100; $i++) {
            $rows[] = ['nis' => 'BATCH-'.$i, 'nama' => 'Batch '.$i, 'kelas_id' => $classes[0]->id,
                'tahun_ajaran_id' => $oldYear->id, 'angkatan' => 2025, 'status' => 'aktif',
                'nama_orang_tua' => 'Wali', 'no_hp_orang_tua' => '0800000000', 'created_at' => now(), 'updated_at' => now()];
        }
        DB::table('siswa')->insert($rows);
        $data['student_ids'] = Siswa::pluck('id')->all();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $token = $this->preview($data);
        $this->post(route('admin.progression.confirm'), ['token' => $token])->assertRedirect();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertLessThan(80, $queryCount, 'Preview + confirmation should not issue queries per student.');
        $this->assertDatabaseCount('student_progressions', 103);
        $this->assertDatabaseCount('student_fee_rates', 102);
    }
}
