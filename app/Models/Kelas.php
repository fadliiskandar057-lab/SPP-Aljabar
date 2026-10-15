<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    protected $table = 'kelas';
    protected $fillable = ['nama_kelas', 'jenjang', 'tingkat', 'jurusan'];

    protected function casts(): array
    {
        return ['tingkat' => 'integer'];
    }

    public function siswa()
    {
        return $this->hasMany(Siswa::class);
    }
}
