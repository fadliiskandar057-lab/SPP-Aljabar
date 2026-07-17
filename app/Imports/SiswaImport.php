<?php

namespace App\Imports;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SiswaImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public array $errors = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $nis = $this->stringValue($this->cell($row, ['nis', 'nomor_induk', 'nomor_induk_siswa']));
            $nama = $this->stringValue($this->cell($row, ['nama', 'nama_siswa']));
            $email = $this->stringValue($this->cell($row, ['email', 'email_siswa']));
            $kelasName = $this->stringValue($this->cell($row, ['kelas', 'nama_kelas']));
            $parentName = $this->stringValue($this->cell($row, ['nama_orang_tua', 'orang_tua', 'nama_wali', 'wali']));
            $parentPhone = $this->stringValue($this->cell($row, ['no_hp_orang_tua', 'hp_orang_tua', 'nomor_hp_orang_tua', 'no_hp', 'telepon']));

            if ($nis === '' || $nama === '' || $kelasName === '' || $parentName === '' || $parentPhone === '') {
                $this->skipped++;
                $this->errors[] = "Baris {$rowNumber} dilewati: NIS, nama, kelas, orang tua, dan no HP wajib diisi.";
                continue;
            }

            $kelas = Kelas::firstOrCreate(['nama_kelas' => $kelasName]);
            $status = strtolower($this->stringValue($this->cell($row, ['status']))) ?: 'aktif';

            if (! in_array($status, ['aktif', 'lulus', 'keluar'], true)) {
                $status = 'aktif';
            }

            $siswa = Siswa::updateOrCreate(
                ['nis' => $nis],
                [
                    'nama' => $nama,
                    'email' => $email ?: null,
                    'kelas_id' => $kelas->id,
                    'nama_orang_tua' => $parentName,
                    'no_hp_orang_tua' => $parentPhone,
                    'alamat' => $this->stringValue($this->cell($row, ['alamat', 'address'])) ?: null,
                    'status' => $status,
                ]
            );

            if ($siswa->wasRecentlyCreated) {
                $this->created++;
            } else {
                $this->updated++;
            }

            $user = User::where('siswa_id', $siswa->id)
                ->orWhere('username', $siswa->nis)
                ->first();

            if ($user) {
                $user->update([
                    'name' => $siswa->nama,
                    'username' => $siswa->nis,
                    'role' => 'siswa',
                    'siswa_id' => $siswa->id,
                ]);
            } else {
                User::create([
                    'name' => $siswa->nama,
                    'username' => $siswa->nis,
                    'password' => Hash::make('password'),
                    'role' => 'siswa',
                    'siswa_id' => $siswa->id,
                ]);
            }
        }
    }

    private function cell(Collection $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if ($row->has($key) && $row->get($key) !== null) {
                return $row->get($key);
            }
        }

        return null;
    }

    private function stringValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_float($value) && floor($value) === $value) {
            return (string) (int) $value;
        }

        return trim((string) $value);
    }
}
