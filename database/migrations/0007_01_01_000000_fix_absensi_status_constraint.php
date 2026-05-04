<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite doesn't support ALTER COLUMN, so we need to recreate the table
        // First, backup existing data
        $existingData = DB::table('absensi')->get();

        // Drop the old table
        Schema::dropIfExists('absensi');

        // Recreate with expanded status
        Schema::create('absensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('tanggal');
            $table->string('slot'); // pagi, sore
            $table->string('status')->default('alfa'); // hadir, izin, sakit, cuti, cuti_bersalin, cuti_tahunan, dinas_luar, ijin_belajar, alfa
            $table->time('jam')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tanggal', 'slot']);
        });

        // Restore data
        foreach ($existingData as $row) {
            DB::table('absensi')->insert([
                'id' => $row->id,
                'user_id' => $row->user_id,
                'tanggal' => $row->tanggal,
                'slot' => $row->slot,
                'status' => $row->status,
                'jam' => $row->jam,
                'keterangan' => $row->keterangan ?? null,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        // Revert to original enum-based table
        $existingData = DB::table('absensi')->get();

        Schema::dropIfExists('absensi');

        Schema::create('absensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('tanggal');
            $table->enum('slot', ['pagi', 'sore']);
            $table->enum('status', ['hadir', 'izin', 'sakit', 'cuti', 'alfa'])->default('alfa');
            $table->time('jam')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tanggal', 'slot']);
        });

        foreach ($existingData as $row) {
            // Only restore rows with valid old statuses
            if (in_array($row->status, ['hadir', 'izin', 'sakit', 'cuti', 'alfa'])) {
                DB::table('absensi')->insert([
                    'id' => $row->id,
                    'user_id' => $row->user_id,
                    'tanggal' => $row->tanggal,
                    'slot' => $row->slot,
                    'status' => $row->status,
                    'jam' => $row->jam,
                    'keterangan' => $row->keterangan ?? null,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        }
    }
};
