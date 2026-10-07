<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('siswa', 'admin_tu', 'bendahara', 'kepala_sekolah', 'wali_kelas') NOT NULL");

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('username');
            $table->string('nip', 50)->nullable()->after('email');
            $table->boolean('is_report_signer')->default(false)->after('nip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn(['email', 'nip', 'is_report_signer']);
        });

        DB::statement("ALTER TABLE users MODIFY role ENUM('siswa', 'admin_tu', 'kepala_sekolah', 'wali_kelas') NOT NULL");
    }
};
