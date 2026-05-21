<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_izin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('kategori', 50); // izin, sakit, cuti_bersalin, cuti_tahunan, dinas_luar, ijin_belajar
            $table->string('judul')->nullable();
            $table->string('nama_file_asli');
            $table->string('path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('ukuran');
            $table->string('extension', 10);
            $table->text('keterangan')->nullable();
            $table->foreignId('uploaded_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'tanggal']);
            $table->index('tanggal');
            $table->index('kategori');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_izin');
    }
};
