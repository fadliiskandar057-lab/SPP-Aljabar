<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kelas');
            $table->timestamps();
        });

        Schema::create('tahun_ajaran', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('siswa', function (Blueprint $table) {
            $table->id();
            $table->string('nis')->unique();
            $table->string('nama');
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->string('nama_orang_tua');
            $table->string('no_hp_orang_tua');
            $table->text('alamat')->nullable();
            $table->enum('status', ['aktif', 'lulus', 'keluar'])->default('aktif');
            $table->timestamps();
        });

        Schema::create('biaya_spp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->nullOnDelete();
            $table->unsignedInteger('nominal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biaya_spp');
        Schema::dropIfExists('siswa');
        Schema::dropIfExists('tahun_ajaran');
        Schema::dropIfExists('kelas');
    }
};
