<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiayaSpp extends Model
{
    protected $table = 'biaya_spp';
    protected $fillable = ['tahun_ajaran_id', 'kelas_id', 'nominal'];

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }
}
