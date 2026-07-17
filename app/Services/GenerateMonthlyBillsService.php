<?php

namespace App\Services;

use App\Models\BiayaSpp;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TagihanExemption;

class GenerateMonthlyBillsService
{
    public function generate(int $tahunAjaranId, string $bulan, int $tahun, string $jatuhTempo): array
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

        foreach (Siswa::with('kelas')->where('status', 'aktif')->get() as $siswa) {
            $biaya = $this->feeForStudent($siswa, $tahunAjaranId);
            if (! $biaya) {
                $stats['skipped_no_fee']++;
                continue;
            }

            $baseNominal = (int) $biaya->nominal;
            $exemption = $this->matchingExemption($exemptions, $siswa);
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

    private function feeForStudent(Siswa $siswa, int $tahunAjaranId): ?BiayaSpp
    {
        $fee = BiayaSpp::where('tahun_ajaran_id', $tahunAjaranId)
            ->where(fn ($query) => $query->where('kelas_id', $siswa->kelas_id)->orWhereNull('kelas_id'))
            ->orderByRaw('kelas_id is null asc')
            ->first();

        if ($fee) {
            return $fee;
        }

        return BiayaSpp::where(fn ($query) => $query->where('kelas_id', $siswa->kelas_id)->orWhereNull('kelas_id'))
            ->orderByRaw('kelas_id is null asc')
            ->latest('tahun_ajaran_id')
            ->first();
    }

    private function matchingExemption($exemptions, Siswa $siswa): ?TagihanExemption
    {
        return $exemptions
            ->filter(function (TagihanExemption $exemption) use ($siswa) {
                return match ($exemption->scope_type) {
                    'siswa' => $exemption->siswa_id === $siswa->id,
                    'kelas' => $exemption->kelas_id === $siswa->kelas_id,
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
