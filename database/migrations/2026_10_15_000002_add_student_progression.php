<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->string('jenjang', 10)->nullable();
            $table->unsignedTinyInteger('tingkat')->nullable();
            $table->string('jurusan', 100)->nullable();
        });
        Schema::table('siswa', function (Blueprint $table) {
            $table->unsignedSmallInteger('angkatan')->nullable();
            $table->foreignId('tahun_ajaran_id')->nullable()->constrained('tahun_ajaran')->nullOnDelete();
            $table->date('inactive_since')->nullable();
        });
        Schema::table('tagihan', function (Blueprint $table) {
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->nullOnDelete();
            $table->string('kelas_nama')->nullable();
        });
        foreach (DB::table('kelas')->get() as $class) {
            DB::table('tagihan')->whereIn('siswa_id', DB::table('siswa')->where('kelas_id', $class->id)->select('id'))
                ->update(['kelas_nama' => $class->nama_kelas, 'kelas_id' => $class->id]);
        }
        Schema::create('student_progressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('from_year_id')->nullable()->constrained('tahun_ajaran')->nullOnDelete();
            $table->foreignId('to_year_id')->constrained('tahun_ajaran')->restrictOnDelete();
            $table->foreignId('from_class_id')->constrained('kelas')->restrictOnDelete();
            $table->foreignId('to_class_id')->nullable()->constrained('kelas')->restrictOnDelete();
            $table->string('from_class_name');
            $table->string('to_class_name')->nullable();
            $table->string('action', 15);
            $table->date('effective_from');
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['siswa_id', 'to_year_id']);
        });
        Schema::create('student_fee_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->restrictOnDelete();
            $table->string('semester', 10);
            $table->date('effective_from');
            $table->unsignedInteger('nominal');
            $table->foreignId('set_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['siswa_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fee_rates');
        Schema::dropIfExists('student_progressions');
        Schema::table('tagihan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kelas_id');
            $table->dropColumn('kelas_nama');
        });
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tahun_ajaran_id');
            $table->dropColumn(['angkatan', 'inactive_since']);
        });
        Schema::table('kelas', fn (Blueprint $table) => $table->dropColumn(['jenjang', 'tingkat', 'jurusan']));
    }
};
