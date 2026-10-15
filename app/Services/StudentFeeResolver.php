<?php

namespace App\Services;

use App\Models\BiayaSpp;
use App\Models\Siswa;
use Carbon\Carbon;

class StudentFeeResolver
{
    private array $classFees = [];

    public function nominal(Siswa $student, int $yearId, Carbon $period): ?int
    {
        $student->loadMissing('feeRates', 'progressions');
        $rate = $student->feeRates->filter(fn ($rate) => $rate->effective_from->lte($period->copy()->startOfMonth()))
            ->sortByDesc('effective_from')->first();
        if ($rate) {
            return (int) $rate->nominal;
        }
        $classId = $this->classAt($student, $period)['id'];
        $history = $student->progressions->sortBy('effective_from');
        $previous = $history->filter(fn ($entry) => $entry->effective_from->lte($period->copy()->startOfMonth()))->last();
        $yearId = $previous?->to_year_id ?? $history->first()?->from_year_id ?? $yearId;
        $fees = $this->classFees[$classId] ??= BiayaSpp::where(fn ($query) => $query->where('kelas_id', $classId)->orWhereNull('kelas_id'))
            ->orderByRaw('kelas_id is null asc')->latest('tahun_ajaran_id')->get();
        $fee = $fees->firstWhere('tahun_ajaran_id', $yearId) ?? $fees->first();

        return $fee ? (int) $fee->nominal : null;
    }

    public function classAt(Siswa $student, Carbon $period): array
    {
        $student->loadMissing('progressions', 'kelas');
        $history = $student->progressions->sortBy('effective_from');
        $previous = $history->filter(fn ($entry) => $entry->effective_from->lte($period->copy()->startOfMonth()))->last();
        if ($previous) {
            return ['id' => $previous->to_class_id ?? $previous->from_class_id,
                'name' => $previous->to_class_name ?? $previous->from_class_name];
        }
        $first = $history->first();

        return ['id' => $first?->from_class_id ?? $student->kelas_id,
            'name' => $first?->from_class_name ?? $student->kelas?->nama_kelas ?? '-'];
    }
}
