<?php

namespace App\Services;

use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TagihanExemption;

class GenerateMonthlyBillsService
{
    public function generate(int $tahunAjaranId, string $bulan, int $tahun, string $jatuhTempo, ?int $siswaId = null): array
    {
        $stats = [
            'created' => 0,
            'free' => 0,
            'discounted' => 0,
            'skipped_existing' => 0,
            'skipped_no_fee' => 0,
        ];

        $exemptions = TagihanExemption::where('tahun_ajaran_id', $tahunAjaranId)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get();

        $resolver = app(StudentFeeResolver::class);
        $months = array_flip(['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']);
        $period = \Carbon\Carbon::create($tahun, $months[$bulan] + 1, 1)->startOfMonth();
        foreach (Siswa::with('kelas', 'feeRates', 'progressions')->where('status', 'aktif')->when($siswaId !== null, fn ($query) => $query->whereKey($siswaId))->get() as $siswa) {
            $baseNominal = $resolver->nominal($siswa, $tahunAjaranId, $period);
            if ($baseNominal === null) {
                $stats['skipped_no_fee']++;
                continue;
            }

            $exemption = $this->matchingExemption($exemptions, $siswa, $resolver->classAt($siswa, $period)['id']);
            $nominal = $this->discountedNominal($baseNominal, $exemption);
            $status = $nominal === 0 ? 'gratis' : 'belum_lunas';

            $tagihan = Tagihan::firstOrCreate([
                'siswa_id' => $siswa->id,
                'tahun_ajaran_id' => $tahunAjaranId,
                'bulan' => $bulan,
                'tahun' => $tahun,
            ], [
                'nominal' => $nominal,
                'jatuh_tempo' => $jatuhTempo,
                'status' => $status,
                'kelas_nama' => $resolver->classAt($siswa, $period)['name'],
                'kelas_id' => $resolver->classAt($siswa, $period)['id'],
            ]);

            if (! $tagihan->wasRecentlyCreated) {
                $stats['skipped_existing']++;
                continue;
            }

            $stats['created']++;
            if ($status === 'gratis') {
                $stats['free']++;
            } elseif ($exemption) {
                $stats['discounted']++;
            }
        }

        return $stats;
    }

    private function matchingExemption($exemptions, Siswa $siswa, int $classId): ?TagihanExemption
    {
        return $exemptions
            ->filter(function (TagihanExemption $exemption) use ($siswa, $classId) {
                return match ($exemption->scope_type) {
                    'siswa' => $exemption->siswa_id === $siswa->id,
                    'kelas' => $exemption->kelas_id === $classId,
                    default => true,
                };
            })
            ->sortByDesc(fn (TagihanExemption $exemption) => match ($exemption->scope_type) {
                'siswa' => 3,
                'kelas' => 2,
                default => 1,
            })
            ->first();
    }

    private function discountedNominal(int $nominal, ?TagihanExemption $exemption): int
    {
        if (! $exemption) {
            return $nominal;
        }

        return match ($exemption->benefit_type) {
            'free' => 0,
            'nominal' => max(0, $nominal - (int) $exemption->amount),
            'percent' => max(0, $nominal - (int) floor($nominal * min(100, (int) $exemption->amount) / 100)),
            default => $nominal,
        };
    }
}
