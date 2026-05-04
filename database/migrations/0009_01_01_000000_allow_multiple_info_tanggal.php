<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite: recreate table without unique constraint
        $existingData = DB::table('info_tanggal')->get();

        Schema::dropIfExists('info_tanggal');

        Schema::create('info_tanggal', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('lokasi')->nullable();
            $table->string('catatan')->nullable();
            $table->timestamps();
            // No unique constraint - allow multiple per date
        });

        foreach ($existingData as $row) {
            DB::table('info_tanggal')->insert([
                'id' => $row->id,
                'tanggal' => $row->tanggal,
                'lokasi' => $row->lokasi,
                'catatan' => $row->catatan,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        // revert
    }
};
