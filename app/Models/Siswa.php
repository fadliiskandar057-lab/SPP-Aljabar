<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    protected $table = 'siswa';
    protected $fillable = ['nis', 'nama', 'email', 'kelas_id', 'nama_orang_tua', 'no_hp_orang_tua', 'alamat', 'status'];

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
