<?php

namespace App\Http\Controllers;

use App\Exports\LaporanPembayaranExport;
use App\Exports\SiswaExport;
use App\Imports\SiswaImport;
use App\Models\AutoBillSetting;
use App\Models\BiayaSpp;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TagihanExemption;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\SequentialSearchService;
use App\Services\MidtransPendingPaymentCleaner;
use App\Services\WebNotificationService;
use App\Services\MidtransService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class AdminController extends Controller
{
    public function settings()
    {
        return view('admin.settings', [
            'autoBillSetting' => AutoBillSetting::firstOrCreate([]),
            'kelas' => Kelas::latest()->get(),
            'tahun' => TahunAjaran::latest()->get(),
            'biaya' => BiayaSpp::with('kelas', 'tahunAjaran')->latest()->get(),
            'exemptions' => TagihanExemption::with('tahunAjaran', 'kelas', 'siswa')->latest()->get(),
            'siswaList' => Siswa::with('kelas')->orderBy('nama')->get(),
        ]);
    }

    public function siswa(Request $request, SequentialSearchService $search)
    {
        $all = Siswa::with(['kelas', 'tagihan' => fn ($query) => $query->orderBy('tahun')->orderBy('jatuh_tempo')])
            ->when($request->filled('kelas_id'), fn ($query) => $query->where('kelas_id', $request->kelas_id))
            ->orderBy('nama')
            ->get();
        $searchResult = $search->searchStudents($all, $request->input('keyword') ?? '');
        return view('admin.siswa', ['siswa' => $searchResult['results'], 'meta' => $searchResult, 'kelas' => Kelas::all()]);
    }

    public function searchSiswa(Request $request, SequentialSearchService $search)
    {
        $all = Siswa::with(['kelas', 'tagihan' => fn ($query) => $query->orderBy('tahun')->orderBy('jatuh_tempo')])
            ->when($request->filled('kelas_id'), fn ($query) => $query->where('kelas_id', $request->kelas_id))
            ->orderBy('nama')
            ->get();
        $result = $search->searchStudents($all, $request->input('keyword') ?? '');

        return response()->json([
            'html' => view('admin.partials.student_rows', ['siswa' => $result['results'], 'kelas' => Kelas::all()])->render(),
            'checked' => $result['checked'],
            'duration_ms' => $result['duration_ms'],
            'count' => $result['results']->count(),
        ]);
    }

    public function storeSiswa(Request $request)
    {
        $data = $request->validate([
            'nis' => ['required', 'unique:siswa,nis'],
            'nama' => ['required'], 'email' => ['nullable', 'email'],
            'kelas_id' => ['required', 'exists:kelas,id'],
            'nama_orang_tua' => ['required'], 'no_hp_orang_tua' => ['required'],
            'alamat' => ['nullable'], 'status' => ['required', 'in:aktif,lulus,keluar'],
        ]);
        $siswa = Siswa::create($data);
        $this->syncStudentAccount($siswa, true);
        return back()->with('success', 'Siswa dan akun portal berhasil dibuat. Username memakai NIS, password default: password.');
    }

    public function importSiswa(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $import = new SiswaImport();

        try {
            Excel::import($import, $request->file('file'));
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['file' => 'File Excel gagal dibaca. Pastikan format kolom dan tipe file sudah benar.']);
        }

        $message = "Import selesai. {$import->created} siswa baru dibuat, {$import->updated} siswa diperbarui";
        if ($import->skipped > 0) {
            $message .= ", {$import->skipped} baris dilewati";
        }
        $message .= '.';

        return back()
            ->with('success', $message)
            ->with('import_warnings', array_slice($import->errors, 0, 6));
    }

    public function exportSiswa()
    {
        return Excel::download(new SiswaExport, 'data-siswa.xlsx');
    }

    public function updateSiswa(Request $request, Siswa $siswa)
    {
        $data = $request->validate([
            'nis' => ['required', 'unique:siswa,nis,'.$siswa->id],
            'nama' => ['required'], 'email' => ['nullable', 'email'],
            'kelas_id' => ['required', 'exists:kelas,id'],
            'nama_orang_tua' => ['required'], 'no_hp_orang_tua' => ['required'],
            'alamat' => ['nullable'], 'status' => ['required', 'in:aktif,lulus,keluar'],
        ]);
        $siswa->update($data);
        $this->syncStudentAccount($siswa);
        return back()->with('success', 'Data siswa diperbarui.');
    }

    public function bulkUpdateSiswa(Request $request)
    {
        $data = $request->validate([
            'siswa_ids' => ['required', 'array', 'min:1'],
            'siswa_ids.*' => ['integer', 'exists:siswa,id'],
            'kelas_id' => ['nullable', 'exists:kelas,id'],
            'status' => ['nullable', 'in:aktif,lulus,keluar'],
        ]);

        $updates = collect($data)->only(['kelas_id', 'status'])->filter(fn ($value) => filled($value))->all();

        if ($updates === []) {
            return back()->withErrors(['bulk' => 'Pilih minimal satu perubahan: kelas atau status.']);
        }

        $updated = Siswa::whereIn('id', $data['siswa_ids'])->update($updates);

        return back()->with('success', "{$updated} data siswa berhasil diperbarui.");
    }

    public function bulkDestroySiswa(Request $request)
    {
        $data = $request->validate([
            'siswa_ids' => ['required', 'array', 'min:1'],
            'siswa_ids.*' => ['integer', 'exists:siswa,id'],
        ]);

        $deleted = Siswa::whereIn('id', $data['siswa_ids'])->delete();

        return back()->with('success', "{$deleted} data siswa berhasil dihapus.");
    }

    public function destroySiswa(Siswa $siswa)
    {
        $siswa->delete();
        return back()->with('success', 'Data siswa dihapus.');
    }

    public function kelas()
    {
        return view('admin.kelas', ['kelas' => Kelas::latest()->get()]);
    }

    public function storeKelas(Request $request)
    {
        Kelas::create($request->validate(['nama_kelas' => ['required', 'max:50']]));
        return back()->with('success', 'Kelas tersimpan.');
    }

    public function tahunAjaran()
    {
        return view('admin.tahun', ['tahun' => TahunAjaran::latest()->get()]);
    }

    public function storeTahunAjaran(Request $request)
    {
        $data = $request->validate(['nama' => ['required'], 'is_active' => ['nullable']]);
        if ($request->boolean('is_active')) {
            TahunAjaran::query()->update(['is_active' => false]);
        }
        TahunAjaran::create(['nama' => $data['nama'], 'is_active' => $request->boolean('is_active')]);
        return back()->with('success', 'Tahun ajaran tersimpan.');
    }

    public function updateTahunAjaran(Request $request, TahunAjaran $tahunAjaran)
    {
        $data = $request->validate(['nama' => ['required'], 'is_active' => ['nullable']]);
        if ($request->boolean('is_active')) {
            TahunAjaran::whereKeyNot($tahunAjaran->id)->update(['is_active' => false]);
        }
        $tahunAjaran->update(['nama' => $data['nama'], 'is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Tahun ajaran diperbarui.');
    }

    public function destroyTahunAjaran(TahunAjaran $tahunAjaran)
    {
        if ($tahunAjaran->tagihan()->exists() || BiayaSpp::where('tahun_ajaran_id', $tahunAjaran->id)->exists()) {
            return back()->withErrors(['tahun' => 'Tahun ajaran tidak bisa dihapus karena masih dipakai tagihan atau biaya SPP.']);
        }

        $tahunAjaran->delete();
        return back()->with('success', 'Tahun ajaran dihapus.');
    }

    public function biaya()
    {
        return view('admin.biaya', ['biaya' => BiayaSpp::with('kelas', 'tahunAjaran')->latest()->get(), 'kelas' => Kelas::all(), 'tahun' => TahunAjaran::all()]);
    }

    public function storeBiaya(Request $request)
    {
        BiayaSpp::create($request->validate([
            'tahun_ajaran_id' => ['required', 'exists:tahun_ajaran,id'],
            'kelas_id' => ['nullable', 'exists:kelas,id'],
            'nominal' => ['required', 'integer', 'min:1'],
        ]));
        return back()->with('success', 'Biaya SPP tersimpan.');
    }

    public function updateBiaya(Request $request, BiayaSpp $biayaSpp)
    {
        $biayaSpp->update($request->validate([
            'tahun_ajaran_id' => ['required', 'exists:tahun_ajaran,id'],
            'kelas_id' => ['nullable', 'exists:kelas,id'],
            'nominal' => ['required', 'integer', 'min:1'],
        ]));

        return back()->with('success', 'Biaya SPP diperbarui.');
    }

    public function destroyBiaya(BiayaSpp $biayaSpp)
    {
        $biayaSpp->delete();
        return back()->with('success', 'Biaya SPP dihapus.');
    }

    public function updateAutoBillSetting(Request $request)
    {
        $data = $request->validate([
            'is_enabled' => ['nullable'],
            'generate_day' => ['required', 'integer', 'between:1,28'],
            'due_day' => ['required', 'integer', 'between:1,28'],
        ]);

        AutoBillSetting::firstOrCreate([])->update([
            'is_enabled' => $request->boolean('is_enabled'),
            'generate_day' => $data['generate_day'],
            'due_day' => $data['due_day'],
        ]);

        return back()->with('success', 'Jadwal tagihan otomatis diperbarui.');
    }

    public function storeTagihanExemption(Request $request)
    {
        $data = $request->validate([
            'tahun_ajaran_id' => ['required', 'exists:tahun_ajaran,id'],
            'bulan' => ['required', Rule::in(array_values($this->months()))],
            'tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
            'scope_type' => ['required', 'in:all,kelas,siswa'],
            'kelas_id' => ['nullable', 'exists:kelas,id'],
            'siswa_id' => ['nullable', 'exists:siswa,id'],
            'benefit_type' => ['required', 'in:free,nominal,percent'],
            'amount' => ['exclude_if:benefit_type,free', 'required', 'integer', 'min:1'],
            'alasan' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['scope_type'] === 'kelas' && blank($data['kelas_id'] ?? null)) {
            return back()->withErrors(['kelas_id' => 'Pilih kelas untuk cakupan kelas tertentu.'])->withInput();
        }

        if ($data['scope_type'] === 'siswa' && blank($data['siswa_id'] ?? null)) {
            return back()->withErrors(['siswa_id' => 'Pilih siswa untuk cakupan siswa tertentu.'])->withInput();
        }

        if ($data['benefit_type'] === 'percent' && (int) $data['amount'] > 100) {
            return back()->withErrors(['amount' => 'Diskon persen maksimal 100%.']);
        }

        if ($data['scope_type'] === 'siswa') {
            $student = Siswa::findOrFail($data['siswa_id']);
            $data['kelas_id'] = $student->kelas_id;
        } elseif ($data['scope_type'] === 'kelas') {
            $data['siswa_id'] = null;
        } else {
            $data['kelas_id'] = null;
            $data['siswa_id'] = null;
        }

        if ($data['benefit_type'] === 'free') {
            $data['amount'] = null;
        }

        TagihanExemption::create($data);

        return back()->with('success', 'Aturan gratis/diskon tagihan tersimpan.');
    }

    public function destroyTagihanExemption(TagihanExemption $tagihanExemption)
    {
        $tagihanExemption->delete();

        return back()->with('success', 'Aturan gratis/diskon dihapus.');
    }

    public function studentArrears(Request $request, SequentialSearchService $search)
    {
        $students = Siswa::with(['kelas', 'tagihan.pembayaran' => fn ($query) => $query->latest(), 'pembayaran.tagihan'])
            ->where('status', 'aktif')
            ->orderBy('nama')
            ->get();

        $studentResult = $search->searchStudents($students, $request->input('keyword') ?? '');
        $filteredStudents = $studentResult['results'];
        $minArrearsMonths = (int) $request->input('min_arrears_months', 0);

        if ($request->filled('kelas_id')) {
            $filteredStudents = $filteredStudents->where('kelas_id', (int) $request->kelas_id)->values();
        }

        if ($minArrearsMonths > 0) {
            $filteredStudents = $filteredStudents
                ->filter(fn ($student) => $student->tagihan->whereNotIn('status', ['lunas', 'gratis'])->count() >= $minArrearsMonths)
                ->values();
        }

        $arrearsSummary = [
            'students' => $filteredStudents->count(),
            'months' => $filteredStudents->sum(fn ($student) => $student->tagihan->whereNotIn('status', ['lunas', 'gratis'])->count()),
            'total' => $filteredStudents->sum(fn ($student) => $student->tagihan->whereNotIn('status', ['lunas', 'gratis'])->sum('nominal')),
        ];

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 25;
        $paginatedStudents = new LengthAwarePaginator(
            $filteredStudents->forPage($page, $perPage)->values(),
            $filteredStudents->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.student_arrears', [
            'siswa' => $paginatedStudents,
            'siswaOptions' => Siswa::with('kelas:id,nama_kelas')
                ->where('status', 'aktif')
                ->orderBy('nama')
                ->get(['id', 'nis', 'nama', 'kelas_id']),
            'kelas' => Kelas::orderBy('nama_kelas')->get(),
            'tahun' => TahunAjaran::orderByDesc('is_active')->latest()->get(),
            'activeYear' => TahunAjaran::where('is_active', true)->first() ?? TahunAjaran::latest()->first(),
            'months' => $this->months(),
            'autoBillSetting' => AutoBillSetting::firstOrCreate([]),
            'meta' => $studentResult + ['count' => $filteredStudents->count()],
            'arrearsSummary' => $arrearsSummary,
        ]);
    }

    public function setLastPaidSiswa(Request $request)
    {
        $data = $request->validate([
            'siswa_id' => ['required'],
            'kelas_id' => ['nullable', 'exists:kelas,id'],
            'tahun_ajaran_id' => ['nullable', 'exists:tahun_ajaran,id'],
            'last_paid_month' => ['required', 'integer', 'between:1,12'],
            'last_paid_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'due_day' => ['nullable', 'integer', 'between:1,28'],
        ]);

        $tahunAjaran = isset($data['tahun_ajaran_id'])
            ? TahunAjaran::find($data['tahun_ajaran_id'])
            : (TahunAjaran::where('is_active', true)->first() ?? TahunAjaran::latest()->first());

        if (! $tahunAjaran) {
            return back()->withErrors(['tahun_ajaran_id' => 'Tahun ajaran belum tersedia. Buat tahun ajaran terlebih dahulu.']);
        }

        if ($data['siswa_id'] !== 'all' && ! Siswa::whereKey($data['siswa_id'])->exists()) {
            return back()->withErrors(['siswa_id' => 'Siswa yang dipilih tidak valid.']);
        }

        $lastPaid = Carbon::create($data['last_paid_year'], $data['last_paid_month'], 1)->startOfMonth();
        $targetMonth = $this->scheduledArrearsTargetMonth();
        $dueDay = (int) ($data['due_day'] ?? $this->scheduledDueDay());

        if ($data['siswa_id'] === 'all') {
            $students = Siswa::with('kelas')
                ->where('status', 'aktif')
                ->when(filled($data['kelas_id'] ?? null), fn ($query) => $query->where('kelas_id', $data['kelas_id']))
                ->orderBy('nama')
                ->get();
            $created = 0;
            $baselinePaid = 0;
            $skipped = collect();

            foreach ($students as $siswa) {
                $result = $this->prepareArrearsForStudent($siswa, $tahunAjaran->id, $lastPaid, $targetMonth, $dueDay);
                $created += $result['created'];
                $baselinePaid += $result['baseline_paid'];

                if ($result['skipped']) {
                    $skipped->push($siswa->nama);
                }
            }

            $scope = filled($data['kelas_id'] ?? null)
                ? 'semua siswa aktif kelas '.(Kelas::find($data['kelas_id'])?->nama_kelas ?? '-')
                : 'semua siswa aktif';
            $message = "Tunggakan {$scope} disiapkan sampai {$this->months()[$targetMonth->month]} {$targetMonth->year}. {$created} tagihan baru dibuat, {$baselinePaid} tagihan lama ditandai sudah dibayar.";

            if ($skipped->isNotEmpty()) {
                $message .= ' '.$skipped->count().' siswa dilewati karena biaya SPP kelasnya belum diatur: '.$skipped->take(5)->implode(', ');
                $message .= $skipped->count() > 5 ? ', dan lainnya.' : '.';
            }

            return back()->with('success', $message);
        }

        $siswa = Siswa::with('kelas')->findOrFail($data['siswa_id']);
        $result = $this->prepareArrearsForStudent($siswa, $tahunAjaran->id, $lastPaid, $targetMonth, $dueDay);

        if ($result['skipped']) {
            return back()->withErrors(['biaya' => 'Biaya SPP untuk kelas siswa ini belum diatur.']);
        }

        return back()->with('success', "Tunggakan {$siswa->nama} disiapkan sampai {$this->months()[$targetMonth->month]} {$targetMonth->year}. {$result['created']} tagihan baru dibuat, {$result['baseline_paid']} tagihan lama ditandai sudah dibayar.");
    }

    public function confirmArrearsThrough(Request $request, Siswa $siswa, Tagihan $tagihan)
    {
        abort_unless($tagihan->siswa_id === $siswa->id, 404);

        $data = $request->validate([
            'bukti' => ['nullable', 'image', 'max:2048'],
            'paid_at' => ['nullable', 'date'],
        ]);

        $targetDate = Carbon::create((int) $tagihan->tahun, $this->monthNumber($tagihan->bulan), 1)->endOfMonth();
        $bills = Tagihan::where('siswa_id', $siswa->id)
            ->whereNotIn('status', ['lunas', 'gratis'])
            ->get()
            ->filter(fn ($bill) => Carbon::create((int) $bill->tahun, $this->monthNumber($bill->bulan), 1)->endOfMonth()->lte($targetDate))
            ->sortBy(fn ($bill) => sprintf('%04d%02d', $bill->tahun, $this->monthNumber($bill->bulan)))
            ->values();

        if ($bills->isEmpty()) {
            return back()->withErrors(['tagihan' => 'Tidak ada tunggakan yang bisa dikonfirmasi.']);
        }

        $proofPath = null;
        if ($request->hasFile('bukti')) {
            $directory = public_path('payment-proofs');
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            $filename = 'bukti-'.$siswa->id.'-'.now()->format('YmdHis').'.'.$request->file('bukti')->extension();
            $request->file('bukti')->move($directory, $filename);
            $proofPath = 'payment-proofs/'.$filename;
        }

        $paidAt = filled($data['paid_at'] ?? null) ? Carbon::parse($data['paid_at']) : now();
        $lastPayment = null;
        foreach ($bills as $bill) {
            $payment = Pembayaran::create([
                'tagihan_id' => $bill->id,
                'siswa_id' => $siswa->id,
                'kode_invoice' => 'INV-ARR-'.now()->format('YmdHis').'-'.$bill->id,
                'metode' => 'manual',
                'nominal' => $bill->nominal,
                'status' => 'success',
                'paid_at' => $paidAt,
                'verified_by' => auth()->id(),
                'bukti_path' => $proofPath,
            ]);
            $bill->update(['status' => 'lunas']);
            $lastPayment = $payment;
        }

        return redirect()
            ->route('invoice.show', $lastPayment)
            ->with('success', "{$bills->count()} bulan tunggakan {$siswa->nama} dikonfirmasi lunas. Invoice terakhir ditampilkan sebagai bukti transaksi.");
    }

    public function confirmArrearsMidtrans(Request $request, Siswa $siswa, Tagihan $tagihan, MidtransService $midtrans, WebNotificationService $notifications, MidtransPendingPaymentCleaner $midtransCleaner)
    {
        abort_unless($tagihan->siswa_id === $siswa->id, 404);

        $midtransCleaner->deleteExpired();
        $tagihan->refresh();

        if ($tagihan->status === 'lunas' || $tagihan->status === 'gratis') {
            return back()->withErrors(['tagihan' => 'Tagihan ini sudah lunas atau gratis.']);
        }

        $targetDate = Carbon::create((int) $tagihan->tahun, $this->monthNumber($tagihan->bulan), 1)->endOfMonth();
        $bills = Tagihan::where('siswa_id', $siswa->id)
            ->whereNotIn('status', ['lunas', 'gratis'])
            ->get()
            ->filter(fn ($bill) => Carbon::create((int) $bill->tahun, $this->monthNumber($bill->bulan), 1)->endOfMonth()->lte($targetDate))
            ->sortBy(fn ($bill) => sprintf('%04d%02d', $bill->tahun, $this->monthNumber($bill->bulan)))
            ->values();

        if ($bills->isEmpty()) {
            return back()->withErrors(['tagihan' => 'Tidak ada tunggakan yang bisa dibayar.']);
        }

        $paidTotal = $bills->sum('nominal');

        // Cancel any pending midtrans payments for these bills
        foreach ($bills as $bill) {
            Pembayaran::where('tagihan_id', $bill->id)
                ->where('metode', 'midtrans')
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);
        }

        // Create the pending master Pembayaran record
        $payment = Pembayaran::create([
            'tagihan_id' => $tagihan->id,
            'siswa_id' => $siswa->id,
            'kode_invoice' => 'INV-MID-ARR-'.now()->format('YmdHis').'-'.$tagihan->id,
            'metode' => 'midtrans',
            'nominal' => $paidTotal,
            'status' => 'pending',
            'midtrans_order_id' => 'SPP-ARR-'.\Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(8)).'-'.$tagihan->id,
        ]);

        try {
            $snapToken = $midtrans->createSnapToken($payment->load('siswa', 'tagihan'));

            // Mark all target bills as menunggu_konfirmasi
            foreach ($bills as $bill) {
                $bill->update(['status' => 'menunggu_konfirmasi']);
            }

            $notifications->toStudent(
                $siswa->id,
                'Transaksi online dibuat oleh TU',
                "Invoice {$payment->kode_invoice} senilai Rp " . number_format($paidTotal, 0, ',', '.') . " siap dibayar via Midtrans.",
                route('siswa.riwayat'),
                'info'
            );

            $notifications->toRole(
                'admin_tu',
                'Invoice Midtrans baru dibuat',
                "Invoice tunggakan {$payment->kode_invoice} untuk {$siswa->nama} dibuat.",
                route('admin.payments'),
                'info'
            );
        } catch (\Exception $exception) {
            $payment->update(['status' => 'failed']);
            foreach ($bills as $bill) {
                $bill->update(['status' => 'belum_lunas']);
            }

            return back()->withErrors([
                'midtrans' => 'Gagal membuat transaksi Midtrans: '.$exception->getMessage(),
            ]);
        }

        return redirect()->route('admin.arrears.midtrans-pay-page', $payment);
    }

    public function payArrearsMidtransPage(Pembayaran $pembayaran)
    {
        abort_unless($pembayaran->metode === 'midtrans', 404);

        $midtrans = app(MidtransService::class);
        $snapToken = $midtrans->createSnapToken($pembayaran->load('siswa', 'tagihan'));

        return view('admin.midtrans_pay', compact('pembayaran', 'snapToken'));
    }

    public function finishMidtransArrears(Request $request, Pembayaran $pembayaran, WebNotificationService $notifications)
    {
        abort_unless($pembayaran->metode === 'midtrans', 422);

        $data = $request->validate([
            'transaction_status' => ['nullable', 'string'],
            'transaction_id' => ['nullable', 'string'],
        ]);

        $status = $data['transaction_status'] ?? 'settlement';

        if (in_array($status, ['capture', 'settlement'], true)) {
            $wasPaid = in_array($pembayaran->status, ['settlement', 'success'], true);
            $pembayaran->update([
                'status' => $status === 'settlement' ? 'settlement' : 'success',
                'midtrans_transaction_id' => $data['transaction_id'] ?? $pembayaran->midtrans_transaction_id,
                'paid_at' => now(),
            ]);
            $pembayaran->tagihan()->update(['status' => 'lunas']);

            // Cancel any other pending payments
            Pembayaran::where('tagihan_id', $pembayaran->tagihan_id)
                ->where('id', '!=', $pembayaran->id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            // Split the payment for earlier months
            Pembayaran::resolveMultiBillPayment($pembayaran);

            $pembayaran->load('siswa.kelas', 'tagihan');

            if (! $wasPaid) {
                $notifications->toStudent(
                    $pembayaran->siswa_id,
                    'Pembayaran online berhasil',
                    "Pembayaran tunggakan sampai {$pembayaran->tagihan->bulan} {$pembayaran->tagihan->tahun} sudah lunas.",
                    route('siswa.riwayat'),
                    'success'
                );

                $adminMessage = "{$pembayaran->siswa->nama} baru saja membayar tunggakan menggunakan Midtrans. "
                    ."Kelas {$pembayaran->siswa->kelas->nama_kelas}, tagihan sampai {$pembayaran->tagihan->bulan} {$pembayaran->tagihan->tahun}, "
                    ."status {$status}"
                    .($pembayaran->midtrans_transaction_id ? ", trx {$pembayaran->midtrans_transaction_id}" : '').'.';
                $notifications->toRole(
                    'admin_tu',
                    'Pembayaran Midtrans berhasil',
                    $adminMessage,
                    route('admin.payments'),
                    'success'
                );
            }

            return response()->json(['ok' => true, 'redirect' => route('admin.arrears.students')]);
        }

        if (in_array($status, ['deny', 'cancel', 'expire', 'failure'], true)) {
            $pembayaran->update(['status' => $status === 'expire' ? 'expired' : 'failed']);
            $pembayaran->tagihan()->update(['status' => 'gagal']);

            // Revert preceding bills
            Pembayaran::revertPrecedingBills($pembayaran);

            $pembayaran->load('siswa.kelas', 'tagihan');
            $notifications->toStudent(
                $pembayaran->siswa_id,
                'Pembayaran Midtrans belum berhasil',
                "Pembayaran {$pembayaran->tagihan->bulan} via Midtrans berstatus {$status}.",
                route('siswa.tagihan'),
                'danger'
            );
        }

        return response()->json(['ok' => true, 'redirect' => route('admin.arrears.students')]);
    }

    public function reopenArrearsBill(Siswa $siswa, Tagihan $tagihan)
    {
        abort_unless($tagihan->siswa_id === $siswa->id, 404);

        $hasOnlinePayment = $tagihan->pembayaran()
            ->whereIn('status', ['settlement', 'success'])
            ->where('metode', '!=', 'manual')
            ->exists();

        if ($hasOnlinePayment) {
            return back()->withErrors(['tagihan' => 'Tagihan ini punya pembayaran online/tunai yang sudah sah. Batalkan dari alur transaksi yang sesuai.']);
        }

        $payment = $tagihan->pembayaran()
            ->where('metode', 'manual')
            ->where('status', 'success')
            ->latest()
            ->first();

        $payment?->update(['status' => 'cancelled']);

        $tagihan->update(['status' => 'belum_lunas']);

        return back()->with('success', "Tagihan {$tagihan->bulan} {$tagihan->tahun} untuk {$siswa->nama} dibuka ulang dan kembali masuk tunggakan.");
    }

    public function cashQueue()
    {
        return view('admin.cash_queue', ['payments' => Pembayaran::with('siswa.kelas', 'tagihan')->where('metode', 'tunai')->where('status', 'pending')->latest()->get()]);
    }

    public function confirmCash(Pembayaran $pembayaran, WebNotificationService $notifications)
    {
        abort_unless($pembayaran->metode === 'tunai', 422);
        $pembayaran->update(['status' => 'success', 'paid_at' => now(), 'verified_by' => auth()->id()]);
        $pembayaran->tagihan()->update(['status' => 'lunas']);
        $pembayaran = $pembayaran->fresh()->load('siswa.kelas', 'tagihan');
        $notifications->toStudent(
            $pembayaran->siswa_id,
            'Pembayaran tunai dikonfirmasi',
            "Pembayaran tunai {$pembayaran->tagihan->bulan} {$pembayaran->tagihan->tahun} sebesar Rp ".number_format($pembayaran->nominal, 0, ',', '.')." sudah lunas. Invoice {$pembayaran->kode_invoice}.",
            route('siswa.riwayat'),
            'success',
        );
        if ($pembayaran->siswa->kelas_id) {
            $notifications->toClassGuardians(
                $pembayaran->siswa->kelas_id,
                'Pembayaran tunai siswa lunas',
                "{$pembayaran->siswa->nama} ({$pembayaran->siswa->kelas->nama_kelas}) sudah dikonfirmasi lunas untuk {$pembayaran->tagihan->bulan} sebesar Rp ".number_format($pembayaran->nominal, 0, ',', '.').'.',
                route('wali.payments'),
                'success',
            );
        }
        return back()->with('success', 'Pembayaran tunai dikonfirmasi lunas.');
    }

    public function payments(Request $request, SequentialSearchService $search)
    {
        app(MidtransPendingPaymentCleaner::class)->deleteExpired();

        $all = Pembayaran::with('siswa.kelas', 'tagihan')
            ->when(!$request->boolean('show_cancelled'), fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->latest()
            ->get();
        $result = $search->searchPayments($all, $request->input('keyword') ?? '');
        return view('admin.payments', [
            'payments' => $result['results'],
            'meta' => $result,
            'unpaidBills' => Tagihan::with('siswa')
                ->whereIn('status', ['belum_lunas', 'gagal'])
                ->latest()
                ->limit(50)
                ->get(),
        ]);
    }

    public function sequential(Request $request, SequentialSearchService $search)
    {
        app(MidtransPendingPaymentCleaner::class)->deleteExpired();

        $students = Siswa::with('kelas')->orderBy('nama')->get();
        $studentResult = $search->searchStudents($students, $request->input('student_keyword') ?? '');

        $payments = Pembayaran::with('siswa.kelas', 'tagihan')->latest()->get();
        $paymentResult = $search->searchPayments($payments, $request->input('payment_keyword') ?? '');

        return view('admin.sequential', [
            'students' => $studentResult['results'],
            'studentMeta' => $studentResult,
            'payments' => $paymentResult['results'],
            'paymentMeta' => $paymentResult,
        ]);
    }

    public function searchSequentialSiswa(Request $request, SequentialSearchService $search)
    {
        $students = Siswa::with('kelas')->orderBy('nama')->get();
        $result = $search->searchStudents($students, $request->input('keyword') ?? '');

        return response()->json([
            'html' => view('admin.partials.sequential_student_rows', ['students' => $result['results']])->render(),
            'checked' => $result['checked'],
            'duration_ms' => $result['duration_ms'],
            'count' => $result['results']->count(),
        ]);
    }

    public function searchPayments(Request $request, SequentialSearchService $search)
    {
        app(MidtransPendingPaymentCleaner::class)->deleteExpired();

        $all = Pembayaran::with('siswa.kelas', 'tagihan')
            ->when(!$request->boolean('show_cancelled'), fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->latest()
            ->get();
        $result = $search->searchPayments($all, $request->input('keyword') ?? '');

        return response()->json([
            'html' => view('partials.payment_rows', ['payments' => $result['results']])->render(),
            'checked' => $result['checked'],
            'duration_ms' => $result['duration_ms'],
            'count' => $result['results']->count(),
        ]);
    }

    public function manualPayment(Request $request, WebNotificationService $notifications)
    {
        app(MidtransPendingPaymentCleaner::class)->deleteExpired();

        $data = $request->validate([
            'tagihan_id' => ['required', 'exists:tagihan,id'],
            'paid_at' => ['nullable', 'date'],
        ]);
        $tagihan = Tagihan::with('siswa')->findOrFail($data['tagihan_id']);

        if ($tagihan->nominal <= 0) {
            return back()->withErrors(['tagihan' => 'Nominal tagihan harus lebih dari 0.']);
        }

        if (!in_array($tagihan->status, ['belum_lunas', 'gagal'], true)) {
            return back()->withErrors(['tagihan' => 'Tagihan ini sedang diproses atau sudah lunas.']);
        }
        $paidAt = filled($data['paid_at'] ?? null) ? Carbon::parse($data['paid_at']) : now();
        Pembayaran::where('tagihan_id', $tagihan->id)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);
        $payment = Pembayaran::create([
            'tagihan_id' => $tagihan->id, 'siswa_id' => $tagihan->siswa_id,
            'kode_invoice' => 'INV-MAN-'.now()->format('YmdHis').'-'.$tagihan->id,
            'metode' => 'manual', 'nominal' => $tagihan->nominal, 'status' => 'success',
            'paid_at' => $paidAt, 'verified_by' => auth()->id(),
        ]);
        $tagihan->update(['status' => 'lunas']);
        $payment->load('siswa.kelas', 'tagihan');
        $notifications->toStudent(
            $payment->siswa_id,
            'Pembayaran manual dicatat',
            "Pembayaran manual {$payment->tagihan->bulan} {$payment->tagihan->tahun} sebesar Rp ".number_format($payment->nominal, 0, ',', '.')." sudah dicatat lunas oleh TU. Invoice {$payment->kode_invoice}.",
            route('siswa.riwayat'),
            'success',
        );
        if ($payment->siswa->kelas_id) {
            $notifications->toClassGuardians(
                $payment->siswa->kelas_id,
                'Pembayaran manual siswa lunas',
                "{$payment->siswa->nama} ({$payment->siswa->kelas->nama_kelas}) sudah lunas untuk {$payment->tagihan->bulan} sebesar Rp ".number_format($payment->nominal, 0, ',', '.').'.',
                route('wali.payments'),
                'success',
            );
        }
        return back()->with('success', 'Pembayaran manual tersimpan.');
    }

    public function cancelPayment(Pembayaran $pembayaran, WebNotificationService $notifications)
    {
        if ($pembayaran->metode !== 'manual' || $pembayaran->status !== 'success') {
            return back()->withErrors(['pembayaran' => 'Hanya pembayaran manual yang sudah sukses yang bisa dibatalkan dari halaman ini.']);
        }

        $pembayaran->update(['status' => 'cancelled']);

        $hasOtherPaidPayment = Pembayaran::where('tagihan_id', $pembayaran->tagihan_id)
            ->where('id', '!=', $pembayaran->id)
            ->whereIn('status', ['success', 'settlement'])
            ->exists();

        if (! $hasOtherPaidPayment) {
            $pembayaran->tagihan()->update(['status' => 'belum_lunas']);
        }
        $pembayaran->load('siswa.kelas', 'tagihan');
        $notifications->toStudent(
            $pembayaran->siswa_id,
            'Transaksi manual dibatalkan',
            "Pembayaran manual {$pembayaran->tagihan->bulan} {$pembayaran->tagihan->tahun} sebesar Rp ".number_format($pembayaran->nominal, 0, ',', '.')." dibatalkan. Invoice {$pembayaran->kode_invoice}. Tagihan dibuka ulang.",
            route('siswa.tagihan'),
            'danger',
        );
        if ($pembayaran->siswa->kelas_id) {
            $notifications->toClassGuardians(
                $pembayaran->siswa->kelas_id,
                'Pembayaran siswa dibatalkan',
                "Transaksi manual {$pembayaran->siswa->nama} ({$pembayaran->siswa->kelas->nama_kelas}) untuk {$pembayaran->tagihan->bulan} {$pembayaran->tagihan->tahun} dibatalkan. Nominal Rp ".number_format($pembayaran->nominal, 0, ',', '.').", invoice {$pembayaran->kode_invoice}.",
                route('wali.arrears'),
                'danger',
            );
        }

        return back()->with('success', 'Transaksi manual dibatalkan. Tagihan akan muncul lagi sebagai tunggakan jika tidak ada pembayaran sukses lain.');
    }

    public function laporan(Request $request, SequentialSearchService $search)
    {
        $query = Tagihan::with('siswa.kelas', 'pembayaran', 'tahunAjaran');
        if ($request->filled('bulan')) $query->where('bulan', $request->bulan);
        if ($request->filled('kelas_id')) $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $request->kelas_id));
        if ($request->filled('status')) $query->where('status', $request->status);
        $tagihan = $query->latest()->get();

        $tagihan = $this->applyPeriodRangeFilter($tagihan, $request);
        $billResult = $search->searchBills($tagihan, $request->input('keyword') ?? '');
        $tagihan = $billResult['results'];

        $summary = [
            'pemasukan' => $tagihan->where('status', 'lunas')->sum('nominal'),
            'tunggakan' => $tagihan->whereNotIn('status', ['lunas', 'gratis'])->sum('nominal'),
            'lunas' => $tagihan->where('status', 'lunas')->count(),
            'belum_lunas' => $tagihan->whereNotIn('status', ['lunas', 'gratis'])->count(),
        ];

        return view('admin.laporan', [
            'tagihan' => $tagihan,
            'reportRows' => $this->monthlyReportRows($tagihan),
            'summary' => $summary,
            'meta' => $billResult,
            'kelas' => Kelas::all(),
            'months' => $this->months(),
            'yearOptions' => $this->reportYearOptions(),
        ]);
    }

    private function applyPeriodRangeFilter($tagihan, Request $request)
    {
        $startMonth = $request->filled('bulan_awal') ? (int) $request->bulan_awal : null;
        $startYear = $request->filled('tahun_awal') ? (int) $request->tahun_awal : null;
        $endMonth = $request->filled('bulan_akhir') ? (int) $request->bulan_akhir : null;
        $endYear = $request->filled('tahun_akhir') ? (int) $request->tahun_akhir : null;

        if (! $startMonth && ! $startYear && ! $endMonth && ! $endYear) {
            return $tagihan->values();
        }

        $minYear = (int) ($tagihan->min('tahun') ?: now()->year);
        $maxYear = (int) ($tagihan->max('tahun') ?: now()->year);
        $startKey = ($startYear ?? $minYear) * 100 + ($startMonth ?? 1);
        $endKey = ($endYear ?? $maxYear) * 100 + ($endMonth ?? 12);

        if ($startKey > $endKey) {
            [$startKey, $endKey] = [$endKey, $startKey];
        }

        return $tagihan
            ->filter(function ($bill) use ($startKey, $endKey) {
                $billKey = (int) $bill->tahun * 100 + $this->monthNumber($bill->bulan);

                return $billKey >= $startKey && $billKey <= $endKey;
            })
            ->values();
    }

    private function monthlyReportRows($tagihan)
    {
        return $tagihan
            ->groupBy(fn ($bill) => $bill->siswa_id.'|'.$this->reportStatusGroup($bill->status))
            ->map(function ($bills) {
                $sorted = $bills
                    ->sortBy(fn ($bill) => sprintf('%04d%02d', $bill->tahun, $this->monthNumber($bill->bulan)))
                    ->values();
                $first = $sorted->first();
                $status = $this->reportStatusGroup($first->status);

                return [
                    'siswa' => $first->siswa,
                    'status' => $status,
                    'status_label' => $status === 'belum_lunas' ? 'Belum Lunas' : ucwords(str_replace('_', ' ', $status)),
                    'bulan_awal' => $sorted->first()->bulan.' '.$sorted->first()->tahun,
                    'bulan_akhir' => $sorted->last()->bulan.' '.$sorted->last()->tahun,
                    'jumlah_bulan' => $sorted->count(),
                    'total' => $sorted->sum('nominal'),
                    'details' => $sorted->map(function ($bill) {
                        $payment = $bill->pembayaran
                            ->whereIn('status', ['success', 'settlement'])
                            ->sortByDesc('paid_at')
                            ->first();

                        return [
                            'bulan' => $bill->bulan.' '.$bill->tahun,
                            'nominal' => $bill->nominal,
                            'status' => $bill->status,
                            'paid_at' => $payment?->paid_at,
                            'invoice' => $payment?->kode_invoice,
                            'payment_id' => $payment?->id,
                        ];
                    })->values(),
                ];
            })
            ->sortBy(fn ($row) => mb_strtolower(($row['siswa']->nama ?? '').' '.$row['status']))
            ->values();
    }

    private function reportStatusGroup(string $status): string
    {
        return $status === 'lunas' ? 'lunas' : (in_array($status, ['gratis'], true) ? 'gratis' : 'belum_lunas');
    }

    public function laporanPdf(Request $request)
    {
        $query = Tagihan::with('siswa.kelas', 'pembayaran', 'tahunAjaran');
        if ($request->filled('bulan')) $query->where('bulan', $request->bulan);
        if ($request->filled('kelas_id')) $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $request->kelas_id));
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->whereHas('siswa', fn ($q) => $q
                ->where('nama', 'like', "%{$keyword}%")
                ->orWhere('nis', 'like', "%{$keyword}%"));
        }

        $tagihan = $query->latest()->get();
        $tagihan = $this->applyPeriodRangeFilter($tagihan, $request);
        $summary = [
            'pemasukan' => $tagihan->where('status', 'lunas')->sum('nominal'),
            'tunggakan' => $tagihan->whereNotIn('status', ['lunas', 'gratis'])->sum('nominal'),
            'lunas' => $tagihan->where('status', 'lunas')->count(),
            'gratis' => $tagihan->where('status', 'gratis')->count(),
            'belum_lunas' => $tagihan->where('status', 'belum_lunas')->count(),
            'menunggu' => $tagihan->where('status', 'menunggu_konfirmasi')->count(),
            'total' => $tagihan->count(),
        ];

        $filters = [
            'bulan' => $this->periodRangeLabel($request),
            'tahun_ajaran' => 'Tahun tagihan',
            'kelas' => Kelas::find($request->kelas_id)?->nama_kelas ?? 'Semua kelas',
            'status' => $request->status ? ucwords(str_replace('_', ' ', $request->status)) : 'Semua status',
            'keyword' => $request->keyword ?: '-',
        ];
        $reportRows = $this->monthlyReportRows($tagihan);

        $pdf = Pdf::loadView('reports.pdf', compact('tagihan', 'reportRows', 'summary', 'filters'))->setPaper('a4', 'landscape');
        return $pdf->download('laporan-spp.pdf');
    }

    private function reportYearOptions(): array
    {
        $minYear = min(2020, (int) (Tagihan::min('tahun') ?: now()->year));
        $maxYear = max(2030, now()->addYear()->year, (int) (Tagihan::max('tahun') ?: now()->year));

        if ($minYear > $maxYear) {
            [$minYear, $maxYear] = [$maxYear, $minYear];
        }

        return range($maxYear, $minYear);
    }

    private function periodRangeLabel(Request $request): string
    {
        if ($request->filled('bulan')) {
            return $request->bulan;
        }

        $months = $this->months();
        $hasStart = $request->hasAny(['bulan_awal', 'tahun_awal']);
        $hasEnd = $request->hasAny(['bulan_akhir', 'tahun_akhir']);
        $startMonth = $request->filled('bulan_awal') ? ($months[(int) $request->bulan_awal] ?? 'Januari') : 'Januari';
        $startYear = $request->filled('tahun_awal') ? (int) $request->tahun_awal : null;
        $endMonth = $request->filled('bulan_akhir') ? ($months[(int) $request->bulan_akhir] ?? 'Desember') : 'Desember';
        $endYear = $request->filled('tahun_akhir') ? (int) $request->tahun_akhir : null;
        $start = trim($startMonth.' '.($startYear ?? ''));
        $end = trim($endMonth.' '.($endYear ?? ''));

        if ($hasStart && $hasEnd) {
            return "{$start} sampai {$end}";
        }

        if ($hasStart) {
            return "Mulai {$start}";
        }

        if ($hasEnd) {
            return "Sampai {$end}";
        }

        return 'Semua periode';
    }

    public function laporanExcel(Request $request)
    {
        $query = Tagihan::with('siswa.kelas', 'pembayaran', 'tahunAjaran');
        if ($request->filled('bulan')) $query->where('bulan', $request->bulan);
        if ($request->filled('kelas_id')) $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $request->kelas_id));
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->whereHas('siswa', fn ($q) => $q
                ->where('nama', 'like', "%{$keyword}%")
                ->orWhere('nis', 'like', "%{$keyword}%"));
        }

        $tagihan = $this->applyPeriodRangeFilter($query->latest()->get(), $request);
        $summary = [
            'pemasukan' => $tagihan->where('status', 'lunas')->sum('nominal'),
            'tunggakan' => $tagihan->whereNotIn('status', ['lunas', 'gratis'])->sum('nominal'),
            'lunas' => $tagihan->where('status', 'lunas')->count(),
            'gratis' => $tagihan->where('status', 'gratis')->count(),
            'belum_lunas' => $tagihan->where('status', 'belum_lunas')->count(),
            'menunggu' => $tagihan->where('status', 'menunggu_konfirmasi')->count(),
            'total' => $tagihan->count(),
        ];
        $filters = [
            'bulan' => $this->periodRangeLabel($request),
            'kelas' => Kelas::find($request->kelas_id)?->nama_kelas ?? 'Semua kelas',
            'status' => $request->status ? ucwords(str_replace('_', ' ', $request->status)) : 'Semua status',
            'keyword' => $request->keyword ?: '-',
        ];

        return Excel::download(new LaporanPembayaranExport($this->monthlyReportRows($tagihan), $summary, $filters), 'laporan-spp.xlsx');
    }

    public function kepalaLaporanBulanan(Request $request)
    {
        $query = Tagihan::with('siswa.kelas', 'pembayaran', 'tahunAjaran');
        if ($request->filled('bulan')) $query->where('bulan', $request->bulan);
        $tagihan = $query->latest()->get();
        $tagihan = $this->applyPeriodRangeFilter($tagihan, $request);

        $summary = [
            'pemasukan' => $tagihan->where('status', 'lunas')->sum('nominal'),
            'tunggakan' => $tagihan->whereNotIn('status', ['lunas', 'gratis'])->sum('nominal'),
            'lunas' => $tagihan->where('status', 'lunas')->count(),
            'belum_lunas' => $tagihan->whereNotIn('status', ['lunas', 'gratis'])->count(),
        ];

        return view('kepala.laporan_bulanan', [
            'tagihan' => $tagihan,
            'reportRows' => $this->monthlyReportRows($tagihan),
            'summary' => $summary,
            'months' => $this->months(),
            'yearOptions' => $this->reportYearOptions(),
        ]);
    }

    public function kepalaLaporanKelas(Request $request)
    {
        $kelas = Kelas::with(['siswa.tagihan'])->get()->map(function ($kelas) {
            $bills = $kelas->siswa->flatMap->tagihan;

            return [
                'nama_kelas' => $kelas->nama_kelas,
                'jumlah_siswa' => $kelas->siswa->count(),
                'lunas' => $bills->where('status', 'lunas')->count(),
                'belum_lunas' => $bills->whereNotIn('status', ['lunas', 'gratis'])->count(),
                'pemasukan' => $bills->where('status', 'lunas')->sum('nominal'),
                'tunggakan' => $bills->whereNotIn('status', ['lunas', 'gratis'])->sum('nominal'),
            ];
        });

        return view('kepala.laporan_kelas', compact('kelas'));
    }

    public function kepalaGrafik()
    {
        $year = now()->year;
        $monthlyIncome = collect(range(1, 12))->map(fn ($m) => Pembayaran::whereIn('status', ['settlement', 'success'])
            ->whereYear('paid_at', $year)
            ->whereMonth('paid_at', $m)
            ->sum('nominal'));
        $statusChart = [
            'lunas' => Tagihan::where('status', 'lunas')->count(),
            'belum_lunas' => Tagihan::whereNotIn('status', ['lunas', 'gratis'])->count(),
        ];

        return view('kepala.grafik', compact('monthlyIncome', 'statusChart', 'year'));
    }

    public function users()
    {
        return view('admin.users', [
            'users' => User::with('siswa.kelas', 'kelas')->latest()->get(),
            'siswa' => Siswa::with('kelas')->orderBy('nama')->get(),
            'kelas' => Kelas::orderBy('nama_kelas')->get(),
        ]);
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => ['required'], 'username' => ['required', 'unique:users,username'],
            'password' => ['required', 'min:6'],
            'role' => ['required', 'in:siswa,admin_tu,kepala_sekolah,wali_kelas'],
            'siswa_id' => ['nullable', 'exists:siswa,id'],
            'kelas_id' => ['nullable', 'required_if:role,wali_kelas', 'exists:kelas,id'],
        ]);
        if ($data['role'] !== 'siswa') {
            $data['siswa_id'] = null;
        }
        if ($data['role'] !== 'wali_kelas') {
            $data['kelas_id'] = null;
        }
        $data['password'] = Hash::make($data['password']);
        User::create($data);
        return back()->with('success', 'User tersimpan.');
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'role' => ['required', 'in:siswa,admin_tu,kepala_sekolah,wali_kelas'],
            'siswa_id' => ['nullable', 'exists:siswa,id'],
            'kelas_id' => ['nullable', 'required_if:role,wali_kelas', 'exists:kelas,id'],
        ]);

        if ($data['role'] !== 'siswa') {
            $data['siswa_id'] = null;
        }
        if ($data['role'] !== 'wali_kelas') {
            $data['kelas_id'] = null;
        }

        $user->update($data);

        return back()->with('success', 'Data login user diperbarui.');
    }

    public function changeUserPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', "Password untuk {$user->name} berhasil diganti.");
    }

    public function resetUserPassword(User $user)
    {
        $user->update(['password' => Hash::make('password')]);

        return back()->with('success', "Password {$user->name} direset ke default: password.");
    }

    private function syncStudentAccount(Siswa $siswa, bool $withDefaultPassword = false): void
    {
        $payload = [
            'name' => $siswa->nama,
            'username' => $siswa->nis,
            'role' => 'siswa',
            'siswa_id' => $siswa->id,
            'kelas_id' => null,
        ];

        $user = User::where('siswa_id', $siswa->id)
            ->orWhere('username', $siswa->nis)
            ->first();

        if ($user) {
            $user->update($payload);
            return;
        }

        User::create($payload + ['password' => Hash::make('password')]);
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

    private function prepareArrearsForStudent(Siswa $siswa, int $tahunAjaranId, Carbon $lastPaid, Carbon $targetMonth, int $dueDay): array
    {
        $biaya = $this->feeForStudent($siswa, $tahunAjaranId);

        if (! $biaya) {
            return ['created' => 0, 'baseline_paid' => 0, 'skipped' => true];
        }

        $baselinePaid = Tagihan::where('siswa_id', $siswa->id)
            ->whereNotIn('status', ['lunas', 'gratis'])
            ->get()
            ->filter(fn ($bill) => Carbon::create((int) $bill->tahun, $this->monthNumber($bill->bulan), 1)->endOfMonth()->lte($lastPaid->copy()->endOfMonth()));

        Tagihan::whereIn('id', $baselinePaid->pluck('id'))->update(['status' => 'lunas']);

        $cursor = $lastPaid->copy()->addMonth();
        $created = 0;

        while ($cursor->lte($targetMonth)) {
            $bill = Tagihan::firstOrCreate([
                'siswa_id' => $siswa->id,
                'tahun_ajaran_id' => $tahunAjaranId,
                'bulan' => $this->months()[$cursor->month],
                'tahun' => $cursor->year,
            ], [
                'nominal' => $biaya->nominal,
                'jatuh_tempo' => $cursor->copy()->day($dueDay)->toDateString(),
                'status' => 'belum_lunas',
            ]);

            if ($bill->wasRecentlyCreated) {
                $created++;
            }

            $cursor->addMonth();
        }

        return ['created' => $created, 'baseline_paid' => $baselinePaid->count(), 'skipped' => false];
    }

    private function scheduledArrearsTargetMonth(): Carbon
    {
        $setting = AutoBillSetting::first();
        $today = now()->startOfDay();
        $generateDay = max(1, min(28, (int) ($setting?->generate_day ?? 1)));
        $target = $today->copy()->startOfMonth();

        if ($today->day < $generateDay) {
            $target->subMonth();
        }

        return $target->startOfMonth();
    }

    private function scheduledDueDay(): int
    {
        $setting = AutoBillSetting::first();

        return max(1, min(28, (int) ($setting?->due_day ?? 10)));
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

    private function monthNumber(string $month): int
    {
        $normalized = mb_strtolower($month);
        $map = [
            'januari' => 1, 'jan' => 1, 'january' => 1,
            'februari' => 2, 'feb' => 2, 'february' => 2,
            'maret' => 3, 'mar' => 3, 'march' => 3,
            'april' => 4, 'apr' => 4,
            'mei' => 5, 'may' => 5,
            'juni' => 6, 'jun' => 6, 'june' => 6,
            'juli' => 7, 'jul' => 7, 'july' => 7,
            'agustus' => 8, 'agu' => 8, 'aug' => 8, 'august' => 8,
            'september' => 9, 'sep' => 9,
            'oktober' => 10, 'okt' => 10, 'oct' => 10, 'october' => 10,
            'november' => 11, 'nov' => 11,
            'desember' => 12, 'des' => 12, 'dec' => 12, 'december' => 12,
        ];

        return $map[$normalized] ?? (is_numeric($month) ? (int) $month : 1);
    }
}
