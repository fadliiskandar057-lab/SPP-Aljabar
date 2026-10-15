<?php

namespace App\Services;

use App\Models\AutoBillSetting;
use App\Models\BiayaSpp;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TagihanExemption;
use App\Models\TahunAjaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FuturePaymentService
{
    /** Build a server-side quote without persisting any bills or payments. */
    public function quote(Siswa $student, string $period): array
    {
        abort_unless($student->status === 'aktif', 403);
        $until = Carbon::createFromFormat('!Y-m', $period);
        if ($until->lt(now()->startOfMonth()) || $until->gt(now()->startOfMonth()->addYears(100))) {
            throw ValidationException::withMessages(['until' => 'Pilih periode bulan ini hingga 100 tahun mendatang.']);
        }
        $year = TahunAjaran::where('is_active', true)->first();
        if (! $year) {
            throw ValidationException::withMessages(['until' => 'Tahun ajaran aktif belum diatur. Hubungi TU.']);
        }
        $fees = BiayaSpp::where(fn ($q) => $q->where('kelas_id', $student->kelas_id)->orWhereNull('kelas_id'))
            ->orderByRaw('kelas_id is null asc')->latest('tahun_ajaran_id')->get();
        $fee = $fees->firstWhere('tahun_ajaran_id', $year->id) ?? $fees->first();
        if (! $fee) {
            throw ValidationException::withMessages(['until' => 'Biaya SPP belum diatur. Hubungi TU.']);
        }
        if (Pembayaran::where('siswa_id', $student->id)->where('status', 'pending')->whereNotNull('future_plan')->exists()) {
            throw ValidationException::withMessages(['until' => 'Masih ada pembayaran masa depan yang menunggu. Selesaikan atau batalkan melalui Riwayat Pembayaran.']);
        }
        $existing = $student->tagihan()->get();
        $cursor = app(SppCoverageSummary::class)->nextPaymentMonth($existing);
        $byPeriod = $existing->groupBy(fn ($bill) => $bill->bulan.' '.$bill->tahun);
        $exemptions = TagihanExemption::where('tahun_ajaran_id', $year->id)->get()->groupBy(fn ($item) => $item->bulan.' '.$item->tahun);
        $dueDay = max(1, min(28, (int) (AutoBillSetting::first()?->due_day ?? 10)));
        $items = [];
        while ($cursor->lte($until)) {
            $month = $cursor->copy()->locale('id')->translatedFormat('F');
            $key = $month.' '.$cursor->year;
            $recorded = $byPeriod->get($key, collect());
            foreach ($recorded as $bill) {
                if ($bill->status === 'menunggu_konfirmasi') {
                    throw ValidationException::withMessages(['until' => 'Ada tagihan dalam periode pilihan yang sedang menunggu konfirmasi.']);
                }
                if (! in_array($bill->status, ['lunas', 'gratis'], true)) {
                    $items[] = ['id' => $bill->id] + $bill->only(['siswa_id', 'tahun_ajaran_id', 'bulan', 'tahun', 'nominal'])
                        + ['jatuh_tempo' => $bill->jatuh_tempo->toDateString()];
                }
            }
            if ($recorded->isEmpty()) {
                $exemption = $exemptions->get($key, collect())->filter(fn ($item) => match ($item->scope_type) {
                    'siswa' => $item->siswa_id === $student->id,
                    'kelas' => $item->kelas_id === $student->kelas_id,
                    default => true,
                })->sortByDesc(fn ($item) => match ($item->scope_type) {'siswa' => 3, 'kelas' => 2, default => 1})->first();
                $base = (int) $fee->nominal;
                $amount = match ($exemption?->benefit_type) {
                    'free' => 0,
                    'nominal' => max(0, $base - (int) $exemption->amount),
                    'percent' => max(0, $base - (int) floor($base * min(100, (int) $exemption->amount) / 100)),
                    default => $base,
                };
                $items[] = ['id' => null, 'siswa_id' => $student->id, 'tahun_ajaran_id' => $year->id,
                    'bulan' => $month, 'tahun' => $cursor->year, 'nominal' => $amount,
                    'jatuh_tempo' => $cursor->copy()->day($dueDay)->toDateString()];
            }
            $cursor->addMonth();
        }
        if (collect($items)->sum('nominal') <= 0) {
            throw ValidationException::withMessages(['until' => 'Tidak ada SPP yang perlu dibayar sampai periode pilihan.']);
        }
        return ['until' => $period, 'items' => $items, 'total' => (int) collect($items)->sum('nominal')];
    }

    /** Called only after a verified payment, inside the caller's transaction. */
    public function settle(Pembayaran $payment): void
    {
        if (! $payment->future_plan || $payment->tagihan_id) {
            return;
        }
        Siswa::whereKey($payment->siswa_id)->lockForUpdate()->firstOrFail();
        $items = $payment->future_plan['items'];
        $existing = Tagihan::where('siswa_id', $payment->siswa_id)->lockForUpdate()->get();
        $inserts = [];
        foreach ($items as $item) {
            $matches = $existing->filter(fn ($bill) => $bill->bulan === $item['bulan'] && (int) $bill->tahun === (int) $item['tahun']);
            if ($matches->isEmpty()) {
                unset($item['id']);
                $inserts[] = $item + ['status' => $item['nominal'] > 0 ? 'lunas' : 'gratis', 'created_at' => now(), 'updated_at' => now()];
            } else {
                // Keep the amount captured by the invoice even if a fee was edited later.
                foreach ($matches as $bill) {
                    if (! in_array($bill->status, ['lunas', 'gratis'], true)) {
                        $bill->update(['nominal' => $item['nominal'], 'status' => $item['nominal'] > 0 ? 'lunas' : 'gratis']);
                    }
                }
            }
        }
        foreach (array_chunk($inserts, 200) as $chunk) {
            DB::table('tagihan')->insert($chunk);
        }
        $recorded = Tagihan::where('siswa_id', $payment->siswa_id)->get()->keyBy(fn ($bill) => $bill->bulan.' '.$bill->tahun);
        $payable = collect($items)->filter(fn ($item) => $item['nominal'] > 0)->values();
        $last = $payable->last();
        $bill = $recorded->get($last['bulan'].' '.$last['tahun']);
        // Keep one ledger entry per paid month, as in the existing cumulative payment flow.
        $parts = [];
        foreach ($payable->slice(0, -1) as $item) {
            $partBill = $recorded->get($item['bulan'].' '.$item['tahun']);
            $parts[] = ['siswa_id' => $payment->siswa_id, 'tagihan_id' => $partBill->id,
                'kode_invoice' => $payment->kode_invoice.'-'.$partBill->id, 'metode' => $payment->metode,
                'nominal' => $item['nominal'], 'status' => $payment->status,
                'midtrans_order_id' => $payment->midtrans_order_id ? $payment->midtrans_order_id.'-'.$partBill->id : null,
                'midtrans_transaction_id' => $payment->midtrans_transaction_id, 'paid_at' => $payment->paid_at,
                'verified_by' => $payment->verified_by, 'created_at' => now(), 'updated_at' => now()];
        }
        foreach (array_chunk($parts, 200) as $chunk) {
            DB::table('pembayaran')->insert($chunk);
        }
        $payment->update(['tagihan_id' => $bill->id, 'nominal' => $last['nominal']]);
        $payment->unsetRelation('tagihan');
    }

    public function release(Pembayaran $payment): void
    {
        $ids = collect($payment->future_plan['items'])->pluck('id')->filter();
        Tagihan::where('siswa_id', $payment->siswa_id)->whereIn('id', $ids)
            ->where('status', 'menunggu_konfirmasi')->update(['status' => 'belum_lunas']);
    }
}
