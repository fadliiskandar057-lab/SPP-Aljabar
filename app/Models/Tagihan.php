<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Tagihan extends Model
{
    protected $table = 'tagihan';
    protected $fillable = ['siswa_id', 'tahun_ajaran_id', 'bulan', 'tahun', 'nominal', 'jatuh_tempo', 'status', 'kelas_nama', 'kelas_id'];

    public function scopeLatestPeriod(Builder $query): Builder
    {
        return $query->orderByDesc('tahun')
            ->orderByRaw("FIELD(bulan, 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember') DESC")
            ->orderByDesc('id');
    }

    protected static function booted(): void
    {
        static::creating(function (Tagihan $bill) {
            if ($bill->kelas_nama === null) {
                $student = $bill->siswa;
                $bill->kelas_nama = $student?->kelas?->nama_kelas;
            }
            $bill->kelas_id ??= $bill->siswa?->kelas_id;
        });
    }

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
