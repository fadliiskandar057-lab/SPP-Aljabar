<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tagihan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();
            $table->string('bulan');
            $table->year('tahun');
            $table->unsignedInteger('nominal');
            $table->date('jatuh_tempo');
            $table->enum('status', ['belum_lunas', 'menunggu_konfirmasi', 'lunas', 'gagal'])->default('belum_lunas');
            $table->timestamps();
            $table->unique(['siswa_id', 'tahun_ajaran_id', 'bulan', 'tahun']);
        });

        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->string('kode_invoice')->unique();
            $table->enum('metode', ['midtrans', 'tunai', 'manual']);
            $table->unsignedInteger('nominal');
            $table->enum('status', ['pending', 'settlement', 'success', 'failed', 'expired', 'cancelled'])->default('pending');
            $table->string('midtrans_order_id')->nullable()->unique();
            $table->string('midtrans_transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
        Schema::dropIfExists('tagihan');
    }
};
