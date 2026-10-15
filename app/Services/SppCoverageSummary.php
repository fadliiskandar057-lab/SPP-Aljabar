<?php

namespace App\Services;

use Illuminate\Support\Collection;

class SppCoverageSummary
{
    public function summarize(Collection $bills): array
    {
        $months = array_flip(['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']);
        $periods = $bills->groupBy(fn ($bill) => (int) $bill->tahun * 12 + $months[$bill->bulan])->sortKeys();
        $through = $latest = $previous = $firstUnpaid = null;
        $continuous = true;
        foreach ($periods as $period => $items) {
            $label = $items->first()->bulan.' '.$items->first()->tahun;
            $settled = $items->every(fn ($bill) => in_array($bill->status, ['lunas', 'gratis'], true));
            if (! $settled) {
                $firstUnpaid ??= $label;
            }
            if (($previous !== null && $period !== $previous + 1) || ! $settled) {
                $continuous = false;
            }
            if ($settled) {
                $latest = $label;
                if ($continuous) {
                    $through = $label;
                }
            }
            $previous = $period;
        }
        return ['through' => $through, 'latest' => $latest, 'first_unpaid' => $firstUnpaid];
    }
}
