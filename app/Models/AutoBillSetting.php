<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutoBillSetting extends Model
{
    protected $fillable = ['is_enabled', 'generate_day', 'due_day'];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'generate_day' => 'integer',
            'due_day' => 'integer',
        ];
    }
}
