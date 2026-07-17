<?php

namespace Database\Seeders;

use App\Models\BiayaSpp;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $tahun = TahunAjaran::create(['nama' => '2025/2026', 'is_active' => true]);
        $kelas = collect(['X-A', 'X-B', 'X-C', 'XI-A', 'XI-B', 'XII-A', 'XII-B'])->map(fn ($nama) => Kelas::create(['nama_kelas' => $nama]));

        User::create(['name' => 'Admin TU', 'username' => 'admin', 'password' => Hash::make('password'), 'role' => 'admin_tu']);
        User::create(['name' => 'Kepala Sekolah', 'username' => 'kepala', 'password' => Hash::make('password'), 'role' => 'kepala_sekolah']);
        User::create(['name' => 'Wali Kelas X-A', 'username' => 'wali', 'password' => Hash::make('password'), 'role' => 'wali_kelas', 'kelas_id' => $kelas->first()->id]);

        foreach ($kelas as $class) {
            BiayaSpp::create(['tahun_ajaran_id' => $tahun->id, 'kelas_id' => $class->id, 'nominal' => 250000 + ($class->id * 10000)]);
        }

        $names = ['Mutiara Azzahra', 'Ahmad Rizki', 'Nabila Putri', 'Fajar Maulana', 'Siti Nurhaliza', 'Dimas Pratama', 'Alya Safitri', 'Rangga Saputra', 'Tiara Amelia', 'Farhan Hakim'];
        foreach ($names as $index => $name) {
            $class = $kelas[$index % $kelas->count()];
            $siswa = Siswa::create([
                'nis' => '2626'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'nama' => $name,
                'kelas_id' => $class->id,
                'nama_orang_tua' => 'Orang Tua '.$name,
                'no_hp_orang_tua' => '62812'.random_int(10000000, 99999999),
                'alamat' => 'Jl. Pendidikan No. '.($index + 1),
                'status' => 'aktif',
            ]);
            User::create(['name' => $name, 'username' => $siswa->nis, 'password' => Hash::make('password'), 'role' => 'siswa', 'siswa_id' => $siswa->id]);
            foreach (['Juli', 'Agustus', 'September'] as $monthIndex => $bulan) {
                Tagihan::create([
                    'siswa_id' => $siswa->id,
                    'tahun_ajaran_id' => $tahun->id,
                    'bulan' => $bulan,
                    'tahun' => 2025,
                    'nominal' => 250000 + ($class->id * 10000),
                    'jatuh_tempo' => now()->setDate(2025, 7 + $monthIndex, 10),
                    'status' => $monthIndex === 0 && $index % 3 === 0 ? 'lunas' : 'belum_lunas',
                ]);
            }
        }
    }
}
