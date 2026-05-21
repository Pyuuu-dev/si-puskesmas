<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekap_manual', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('bulan'); // 1-12
            $table->unsignedSmallInteger('tahun');
            $table->string('judul')->nullable();
            $table->string('nama_file_asli');
            $table->string('path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('ukuran'); // bytes
            $table->string('extension', 10);
            $table->text('keterangan')->nullable();
            $table->foreignId('uploaded_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();

            // 1 file per bulan-tahun
            $table->unique(['bulan', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_manual');
    }
};
