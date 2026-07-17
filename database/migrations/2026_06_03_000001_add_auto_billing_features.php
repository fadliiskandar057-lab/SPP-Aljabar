<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE tagihan MODIFY status ENUM('belum_lunas', 'menunggu_konfirmasi', 'lunas', 'gagal', 'gratis') DEFAULT 'belum_lunas'");

        Schema::create('auto_bill_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->unsignedTinyInteger('generate_day')->default(1);
            $table->unsignedTinyInteger('due_day')->default(10);
            $table->timestamps();
        });

        Schema::create('tagihan_exemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();
            $table->string('bulan');
            $table->year('tahun');
            $table->enum('scope_type', ['all', 'kelas', 'siswa'])->default('all');
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('siswa_id')->nullable()->constrained('siswa')->cascadeOnDelete();
            $table->enum('benefit_type', ['free', 'nominal', 'percent'])->default('free');
            $table->unsignedInteger('amount')->nullable();
            $table->string('alasan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tagihan_exemptions');
        Schema::dropIfExists('auto_bill_settings');

        DB::statement("ALTER TABLE tagihan MODIFY status ENUM('belum_lunas', 'menunggu_konfirmasi', 'lunas', 'gagal') DEFAULT 'belum_lunas'");
    }
};
