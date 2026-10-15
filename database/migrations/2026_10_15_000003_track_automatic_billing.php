<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auto_bill_settings', function (Blueprint $table) {
            $table->date('starts_on')->nullable();
            $table->date('last_generated_period')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_generated_at')->nullable();
            $table->unsignedInteger('last_created_count')->default(0);
            $table->unsignedInteger('last_missing_fee_count')->default(0);
        });
        DB::table('auto_bill_settings')->where('is_enabled', true)
            ->update(['starts_on' => now()->startOfMonth()->toDateString()]);
    }

    public function down(): void
    {
        Schema::table('auto_bill_settings', fn (Blueprint $table) => $table->dropColumn([
            'starts_on', 'last_generated_period', 'last_checked_at', 'last_generated_at', 'last_created_count', 'last_missing_fee_count',
        ]));
    }
};
