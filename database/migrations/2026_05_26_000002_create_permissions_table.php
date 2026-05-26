<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();        // contoh: pegawai.view
            $table->string('menu');                  // contoh: pegawai
            $table->string('action');                // view|create|update|delete|<custom>
            $table->string('label');                 // contoh: Lihat Pegawai
            $table->string('group')->nullable();     // grouping di UI: Master, Absensi, dll
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['menu', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
