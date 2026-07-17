<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebNotification extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'message', 'url', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
