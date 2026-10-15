<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Carbon\Carbon;

class SppCoverageSummary
{
    public function nextPaymentMonth(Collection $bills): Carbon
    {
        $months = array_flip(['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']);
        $periods = $bills->groupBy(fn ($bill) => (int) $bill->tahun * 12 + $months[$bill->bulan])->sortKeys();
        if ($periods->isEmpty()) {
            return now()->startOfMonth();
        }

        $next = (int) $periods->keys()->first();
        foreach ($periods as $period => $items) {
            if ((int) $period !== $next || ! $items->every(fn ($bill) => in_array($bill->status, ['lunas', 'gratis'], true))) {
                break;
            }
            $next++;
        }

        return Carbon::create(intdiv($next, 12), $next % 12 + 1, 1)->startOfMonth();
    }

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
