<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanPembayaranExport implements FromArray, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly Collection $reportRows,
        private readonly array $summary,
        private readonly array $filters,
    ) {}

    public function array(): array
    {
        $rows = [
            ['Laporan Pembayaran SPP'],
            ['Dicetak', now()->format('d/m/Y H:i')],
            [],
            ['Filter'],
            ['Periode', $this->filters['bulan'] ?? 'Semua periode'],
            ['Kelas', $this->filters['kelas'] ?? 'Semua kelas'],
            ['Status', $this->filters['status'] ?? 'Semua status'],
            ['Kata Kunci', $this->filters['keyword'] ?? '-'],
            [],
            ['Ringkasan'],
            ['Total Pemasukan', $this->summary['pemasukan'] ?? 0],
            ['Total Tunggakan', $this->summary['tunggakan'] ?? 0],
            ['Tagihan Lunas', $this->summary['lunas'] ?? 0],
            ['Belum Lunas / Menunggu', ($this->summary['belum_lunas'] ?? 0) + ($this->summary['menunggu'] ?? 0)],
            ['Total Data', $this->summary['total'] ?? 0],
            [],
            ['Ringkasan Per Siswa'],
            ['NIS', 'Nama', 'Kelas', 'Status', 'Rentang Bulan', 'Jumlah Bulan', 'Total Nominal'],
        ];

        foreach ($this->reportRows as $row) {
            $rows[] = [
                $row['siswa']->nis ?? '-',
                $row['siswa']->nama ?? '-',
                $row['siswa']->kelas->nama_kelas ?? '-',
                $row['status_label'],
                $row['bulan_awal'].' - '.$row['bulan_akhir'],
                $row['jumlah_bulan'],
                $row['total'],
            ];
        }

        $rows[] = [];
        $rows[] = ['Detail Pembayaran'];
        $rows[] = ['NIS', 'Nama', 'Kelas', 'Status Grup', 'Bulan', 'Nominal', 'Status Tagihan', 'Tanggal Bayar', 'Invoice'];

        foreach ($this->reportRows as $row) {
            foreach ($row['details'] as $detail) {
                $rows[] = [
                    $row['siswa']->nis ?? '-',
                    $row['siswa']->nama ?? '-',
                    $row['siswa']->kelas->nama_kelas ?? '-',
                    $row['status_label'],
                    $detail['bulan'],
                    $detail['nominal'],
                    str_replace('_', ' ', $detail['status']),
                    $detail['paid_at'] ? $detail['paid_at']->format('d/m/Y H:i') : '-',
                    $detail['invoice'] ?: '-',
                ];
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $summaryHeaderRow = 10;
        $groupTitleRow = 17;
        $groupHeaderRow = 18;
        $detailTitleRow = 20 + $this->reportRows->count();
        $detailHeaderRow = 21 + $this->reportRows->count();

        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        return [
            $summaryHeaderRow => ['font' => ['bold' => true]],
            $groupTitleRow => ['font' => ['bold' => true]],
            $groupHeaderRow => ['font' => ['bold' => true]],
            $detailTitleRow => ['font' => ['bold' => true]],
            $detailHeaderRow => ['font' => ['bold' => true]],
        ];
    }
}
