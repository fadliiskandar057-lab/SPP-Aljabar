<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentProgression extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['effective_from' => 'date'];
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function targetYear()
    {
        return $this->belongsTo(TahunAjaran::class, 'to_year_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
