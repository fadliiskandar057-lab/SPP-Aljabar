<?php

namespace App\Exports;

use App\Models\Siswa;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SiswaExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'nis',
            'nama',
            'email',
            'kelas',
            'nama_orang_tua',
            'no_hp_orang_tua',
            'alamat',
            'status',
        ];
    }

    public function array(): array
    {
        return Siswa::with('kelas')
            ->orderBy('nama')
            ->get()
            ->map(fn (Siswa $siswa) => [
                $siswa->nis,
                $siswa->nama,
                $siswa->email,
                $siswa->kelas->nama_kelas ?? '',
                $siswa->nama_orang_tua,
                $siswa->no_hp_orang_tua,
                $siswa->alamat,
                $siswa->status,
            ])
            ->all();
    }
}
