<?php

namespace App\Console\Commands;

use App\Services\AutomaticBillingService;
use App\Services\WebNotificationService;
use Illuminate\Console\Command;

class GenerateMonthlyBillsCommand extends Command
{
    protected $signature = 'tagihan:generate-bulanan {--force : Jalankan meskipun tanggal hari ini belum mencapai tanggal keluar}';

    protected $description = 'Sinkronisasi tagihan bulanan mengikuti kalender dan jadwal sekolah.';

    public function handle(AutomaticBillingService $billing, WebNotificationService $notifications): int
    {
        $result = $billing->sync((bool) $this->option('force'));
        if ($result['status'] === 'missing_year') {
            $this->error('Tahun ajaran aktif belum diatur.');

            return self::FAILURE;
        }
        if ($result['status'] === 'disabled') {
            $this->info('Generate tagihan otomatis nonaktif.');

            return self::SUCCESS;
        }
        $this->info("Sinkronisasi selesai. {$result['created']} tagihan baru, {$result['missing_fee']} belum memiliki biaya SPP. Status: {$result['status']}.");
        if ($result['created'] > 0) {
            $notifications->toRole('bendahara', 'Tagihan bulanan otomatis dibuat',
                $result['created'].' tagihan diterbitkan mengikuti jadwal dan kalender sekolah.', route('treasury.laporan'), 'success');
        }

        return self::SUCCESS;
    }
}
