<?php

namespace App\Services;

use Illuminate\Support\Collection;

class SequentialSearchService
{
    public function searchStudents(Collection $students, string $keyword): array
    {
        $keyword = mb_strtolower(trim($keyword));
        $startedAt = microtime(true);
        $checked = 0;
        $results = collect();

        foreach ($students as $student) {
            $checked++;
            $haystack = mb_strtolower($student->nis.' '.$student->nama.' '.$student->email.' '.optional($student->kelas)->nama_kelas);

            if ($keyword === '' || str_contains($haystack, $keyword)) {
                $results->push($student);
            }
        }

        return $this->response($results, $checked, $startedAt);
    }

    public function searchPayments(Collection $payments, string $keyword): array
    {
        $keyword = mb_strtolower(trim($keyword));
        $startedAt = microtime(true);
        $checked = 0;
        $results = collect();

        foreach ($payments as $payment) {
            $checked++;
            $student = $payment->siswa;
            $bill = $payment->tagihan;
            $haystack = mb_strtolower(implode(' ', [
                $payment->kode_invoice,
                $student?->nis,
                $student?->nama,
                $bill?->bulan,
                $bill?->tahun,
                $payment->paid_at?->format('d/m/Y H:i'),
                $payment->paid_at?->format('Y-m-d'),
                $payment->metode,
                $payment->status,
            ]));

            if ($keyword === '' || str_contains($haystack, $keyword)) {
                $results->push($payment);
            }
        }

        return $this->response($results, $checked, $startedAt);
    }

    public function searchBills(Collection $bills, string $keyword): array
    {
        $keyword = mb_strtolower(trim($keyword));
        $startedAt = microtime(true);
        $checked = 0;
        $results = collect();

        foreach ($bills as $bill) {
            $checked++;
            $student = $bill->siswa;
            $haystack = mb_strtolower(implode(' ', [
                $student?->nis,
                $student?->nama,
                $student?->email,
                optional($student?->kelas)->nama_kelas,
                $bill->bulan,
                $bill->tahun,
                $bill->nominal,
                $bill->status,
            ]));

            if ($keyword === '' || str_contains($haystack, $keyword)) {
                $results->push($bill);
            }
        }

        return $this->response($results, $checked, $startedAt);
    }

    private function response(Collection $results, int $checked, float $startedAt): array
    {
        return [
            'results' => $results,
            'checked' => $checked,
            'duration_ms' => round((microtime(true) - $startedAt) * 1000, 3),
        ];
    }
}
