<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel Menu (kategori besar)
        Schema::create('menu_kegiatan', function (Blueprint $table) {
            $table->id();
            $table->string('nama'); // "Peningkatan Layanan Kesehatan Masyarakat Sesuai Siklus Hidup"
            $table->string('warna', 7)->default('#6B7280');
            $table->boolean('aktif')->default(true);
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });

        // Tabel Rincian Menu (sub-kategori, berisi kegiatan-kegiatan)
        Schema::create('rincian_menu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_kegiatan_id')->constrained('menu_kegiatan')->onDelete('cascade');
            $table->string('nama'); // "Pelacakan dan pengawasan minum obat untuk ODGJ berat"
            $table->string('pemegang_program')->nullable();
            $table->string('kode', 30)->nullable(); // Kode singkat untuk tampil di matriks (misal: ODGJ, KIA, dll)
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        // Update tabel perjalanan_dinas agar reference ke rincian_menu
        Schema::table('perjalanan_dinas', function (Blueprint $table) {
            $table->foreignId('rincian_menu_id')->nullable()->after('kode_kegiatan_id');
        });
    }

    public function down(): void
    {
        Schema::table('perjalanan_dinas', function (Blueprint $table) {
            $table->dropColumn('rincian_menu_id');
        });
        Schema::dropIfExists('rincian_menu');
        Schema::dropIfExists('menu_kegiatan');
    }
};
