<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function show(Pembayaran $pembayaran)
    {
        $this->authorizePayment($pembayaran);
        return view('invoice.show', ['payment' => $pembayaran->load('siswa.kelas', 'tagihan')]);
    }

    public function download(Pembayaran $pembayaran)
    {
        $this->authorizePayment($pembayaran);
        $pdf = Pdf::loadView('invoice.pdf', ['payment' => $pembayaran->load('siswa.kelas', 'tagihan', 'verifier')]);
        return $pdf->download($pembayaran->kode_invoice.'.pdf');
    }

    private function authorizePayment(Pembayaran $payment): void
    {
        if (auth()->user()->role === 'siswa') {
            abort_unless($payment->siswa_id === auth()->user()->siswa_id, 403);
        }

        if (auth()->user()->role === 'wali_kelas') {
            abort_unless($payment->siswa?->kelas_id === auth()->user()->kelas_id, 403);
        }
    }
}
