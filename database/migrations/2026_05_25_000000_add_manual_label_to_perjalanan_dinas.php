<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perjalanan_dinas', function (Blueprint $table) {
            // Label pendek untuk entri dinas manual (tanpa kode kegiatan BOK).
            // NULL untuk record berbasis kegiatan; terisi untuk entri manual.
            $table->string('manual_label', 30)->nullable()->after('keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('perjalanan_dinas', function (Blueprint $table) {
            $table->dropColumn('manual_label');
        });
    }
};
