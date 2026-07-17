<?php

namespace App\Console\Commands;

use App\Models\AutoBillSetting;
use App\Models\TahunAjaran;
use App\Services\GenerateMonthlyBillsService;
use App\Services\WebNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateMonthlyBillsCommand extends Command
{
    protected $signature = 'tagihan:generate-bulanan {--force : Jalankan meskipun tanggal hari ini belum mencapai tanggal keluar}';

    protected $description = 'Generate tagihan bulanan otomatis berdasarkan jadwal pengaturan SPP.';

    public function handle(GenerateMonthlyBillsService $generator, WebNotificationService $notifications): int
    {
        $setting = AutoBillSetting::first();
        if (! $setting || ! $setting->is_enabled) {
            $this->info('Generate tagihan otomatis nonaktif.');
            return self::SUCCESS;
        }

        $today = now();
        if (! $this->option('force') && $today->day < $setting->generate_day) {
            $this->info('Belum masuk tanggal keluar tagihan.');
            return self::SUCCESS;
        }

        $tahunAjaran = TahunAjaran::where('is_active', true)->first();
        if (! $tahunAjaran) {
            $this->error('Tahun ajaran aktif belum diatur.');
            return self::FAILURE;
        }

        $dueDay = min($setting->due_day, $today->copy()->endOfMonth()->day);
        $jatuhTempo = Carbon::create($today->year, $today->month, $dueDay)->toDateString();
        $stats = $generator->generate(
            $tahunAjaran->id,
            $this->months()[$today->month],
            $today->year,
            $jatuhTempo,
        );

        $this->info("Generate selesai. {$stats['created']} tagihan baru dibuat, {$stats['free']} gratis, {$stats['discounted']} diskon, {$stats['skipped_existing']} sudah ada, {$stats['skipped_no_fee']} dilewati karena biaya belum diatur.");
        if ($stats['created'] > 0) {
            $notifications->toRole(
                'admin_tu',
                'Tagihan bulanan otomatis dibuat',
                "{$stats['created']} tagihan baru dibuat untuk {$this->months()[$today->month]} {$today->year}.",
                route('admin.laporan', ['bulan' => $this->months()[$today->month]]),
                'success',
            );
        }

        return self::SUCCESS;
    }

    private function months(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }
}
