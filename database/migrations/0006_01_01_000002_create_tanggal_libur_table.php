<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table for dynamic holiday/date configuration
        Schema::create('tanggal_libur', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->boolean('is_libur')->default(true);
            $table->string('keterangan')->nullable(); // e.g. "Hari Raya Idul Fitri"
            $table->string('catatan')->nullable(); // primary note/catatan
            $table->timestamps();
        });

        // Table for posyandu location info per date (dynamic)
        Schema::create('info_tanggal', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('lokasi')->nullable(); // e.g. "Posyandu Bina Atmaja 1"
            $table->string('catatan')->nullable(); // additional note
            $table->timestamps();

            $table->unique('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('info_tanggal');
        Schema::dropIfExists('tanggal_libur');
    }
};
