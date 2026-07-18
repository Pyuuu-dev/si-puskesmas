<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('changelogs', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('versi', 20)->nullable();
            $table->enum('tipe', ['tambah', 'update', 'fix', 'hapus', 'lainnya'])->default('update');
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('changelogs');
    }
};
