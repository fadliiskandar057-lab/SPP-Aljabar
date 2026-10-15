<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->unsignedBigInteger('tagihan_id')->nullable()->change();
            $table->json('future_plan')->nullable();
        });
    }

    public function down(): void
    {
        // A pending future invoice has no bill yet; preserve it on rollback.
        if (\Illuminate\Support\Facades\DB::table('pembayaran')->whereNull('tagihan_id')->exists()) {
            throw new RuntimeException('Resolve pending future invoices before rolling back.');
        }
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropColumn('future_plan');
            $table->unsignedBigInteger('tagihan_id')->nullable(false)->change();
        });
    }
};
