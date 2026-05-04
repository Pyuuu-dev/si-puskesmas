<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('tanggal');
            $table->enum('slot', ['pagi', 'sore']);
            $table->enum('status', ['hadir', 'izin', 'sakit', 'cuti', 'alfa'])->default('alfa');
            $table->time('jam')->nullable(); // jam absen (untuk yang hadir)
            $table->string('keterangan')->nullable();
            $table->timestamps();

            // Satu pegawai hanya boleh punya 1 record per slot per hari
            $table->unique(['user_id', 'tanggal', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};
