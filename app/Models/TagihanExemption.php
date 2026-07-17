<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TagihanExemption extends Model
{
    protected $fillable = [
        'tahun_ajaran_id', 'bulan', 'tahun', 'scope_type', 'kelas_id', 'siswa_id',
        'benefit_type', 'amount', 'alasan',
    ];

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }
}
