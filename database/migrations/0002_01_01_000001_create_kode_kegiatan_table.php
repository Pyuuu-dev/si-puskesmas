<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kode_kegiatan', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique(); // PTM, UKS, TB, dll
            $table->string('nama'); // Nama lengkap kegiatan
            $table->string('warna', 7)->default('#6B7280'); // Hex color
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kode_kegiatan');
    }
};
