<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tagihan extends Model
{
    protected $table = 'tagihan';
    protected $fillable = ['siswa_id', 'tahun_ajaran_id', 'bulan', 'tahun', 'nominal', 'jatuh_tempo', 'status'];

    protected function casts(): array
    {
        return ['jatuh_tempo' => 'date'];
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class);
    }
}
