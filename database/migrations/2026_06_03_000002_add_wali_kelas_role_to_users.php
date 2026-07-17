<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('siswa', 'admin_tu', 'kepala_sekolah', 'wali_kelas') NOT NULL");

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('kelas_id')->nullable()->after('siswa_id')->constrained('kelas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kelas_id');
        });

        DB::statement("ALTER TABLE users MODIFY role ENUM('siswa', 'admin_tu', 'kepala_sekolah') NOT NULL");
    }
};
