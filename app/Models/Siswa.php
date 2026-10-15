<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    protected $table = 'siswa';
    protected $fillable = ['nis', 'nama', 'email', 'kelas_id', 'nama_orang_tua', 'no_hp_orang_tua', 'alamat', 'status', 'angkatan', 'tahun_ajaran_id', 'inactive_since'];

    protected function casts(): array
    {
        return ['angkatan' => 'integer', 'inactive_since' => 'date'];
    }

    public function progressions() { return $this->hasMany(StudentProgression::class); }
    public function feeRates() { return $this->hasMany(StudentFeeRate::class); }
    public function tahunAjaran() { return $this->belongsTo(TahunAjaran::class); }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function tagihan()
    {
        return $this->hasMany(Tagihan::class);
    }

    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class);
    }
}
