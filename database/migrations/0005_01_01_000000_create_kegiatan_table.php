<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rincian_menu_id')->constrained('rincian_menu')->onDelete('cascade');
            $table->string('nama'); // Nama kegiatan lengkap
            $table->string('kode', 30)->nullable(); // Kode singkat untuk tampil di matriks
            $table->string('pemegang_program')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        // Update perjalanan_dinas agar reference ke kegiatan
        Schema::table('perjalanan_dinas', function (Blueprint $table) {
            $table->foreignId('kegiatan_id')->nullable()->after('rincian_menu_id');
        });
    }

    public function down(): void
    {
        Schema::table('perjalanan_dinas', function (Blueprint $table) {
            $table->dropColumn('kegiatan_id');
        });
        Schema::dropIfExists('kegiatan');
    }
};
