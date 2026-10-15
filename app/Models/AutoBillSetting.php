<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutoBillSetting extends Model
{
    protected $fillable = ['is_enabled', 'generate_day', 'due_day', 'starts_on', 'last_generated_period',
        'last_checked_at', 'last_generated_at', 'last_created_count', 'last_missing_fee_count'];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'generate_day' => 'integer',
            'due_day' => 'integer',
            'starts_on' => 'date',
            'last_generated_period' => 'date',
            'last_checked_at' => 'datetime',
            'last_generated_at' => 'datetime',
        ];
    }
}
