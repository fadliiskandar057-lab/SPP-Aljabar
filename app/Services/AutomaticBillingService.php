<?php

namespace App\Services;

use App\Models\AutoBillSetting;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\DB;

class AutomaticBillingService
{
    public function sync(bool $force = false): array
    {
        return DB::transaction(function () use ($force) {
            $result = ['created' => 0, 'missing_fee' => 0, 'status' => 'disabled'];
            $setting = AutoBillSetting::lockForUpdate()->first();
            if (! $setting || ! $setting->is_enabled) {
                return $result;
            }
            $today = now();
            $target = $today->copy()->startOfMonth();
            if (! $force && $today->day < $setting->generate_day) {
                $target->subMonth();
            }
            $start = $setting->starts_on ?? $today->copy()->startOfMonth();
            $previousTarget = $setting->last_checked_at?->copy()->startOfMonth();
            if ($previousTarget && $setting->last_checked_at->day < $setting->generate_day) {
                $previousTarget->subMonth();
            }
            if (! $force && $setting->last_checked_at?->gt($today->copy()->subMinutes(5)) && $previousTarget?->equalTo($target)) {
                return array_replace($result, ['status' => 'throttled']);
            }
            $setting->update(['starts_on' => $start, 'last_checked_at' => $today]);
            if ($target->lt($start)) {
                return array_replace($result, ['status' => 'not_due']);
            }
            // Keep the first consistent read after the generator locks its students.
            $year = TahunAjaran::where('is_active', true)->lockForUpdate()->first();
            if (! $year) {
                return array_replace($result, ['status' => 'missing_year']);
            }
            $cursor = $setting->last_generated_period && $setting->last_generated_period->gte($start)
                ? $setting->last_generated_period->copy() : $start->copy();
            if ($setting->last_generated_period && $cursor->equalTo($setting->last_generated_period) && $cursor->lt($target)) {
                $cursor->addMonth();
            }
            $generator = app(GenerateMonthlyBillsService::class);
            $checkpointAllowed = true;
            while ($cursor->lte($target)) {
                $due = $cursor->copy();
                if ($setting->due_day < $setting->generate_day) {
                    $due->addMonth();
                }
                $stats = $generator->generate($year->id, $cursor->copy()->locale('id')->translatedFormat('F'),
                    $cursor->year, $due->day($setting->due_day)->toDateString());
                $result['created'] += $stats['created'];
                $result['missing_fee'] += $stats['skipped_no_fee'];
                if ($stats['skipped_no_fee'] > 0) {
                    $checkpointAllowed = false;
                }
                if ($checkpointAllowed) {
                    $setting->last_generated_period = $cursor->copy();
                }
                $cursor->addMonth();
            }
            $setting->last_missing_fee_count = $result['missing_fee'];
            if ($result['created'] > 0) {
                $setting->last_generated_at = $today;
                $setting->last_created_count = $result['created'];
            }
            $setting->save();
            $result['status'] = $result['missing_fee'] > 0 ? 'missing_fee' : 'synchronized';

            return $result;
        });
    }
}
