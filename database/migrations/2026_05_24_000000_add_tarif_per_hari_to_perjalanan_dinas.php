<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perjalanan_dinas', function (Blueprint $table) {
            if (!Schema::hasColumn('perjalanan_dinas', 'tarif_per_hari')) {
                $table->decimal('tarif_per_hari', 15, 0)->default(0)->after('keterangan');
            }
        });

        // Backfill existing rows to default tarif Rp 80.000
        DB::table('perjalanan_dinas')
            ->where('tarif_per_hari', 0)
            ->update(['tarif_per_hari' => 80000]);

        // Seed default setting if not present
        $exists = DB::table('settings')->where('key', 'tarif_perjalanan_dinas')->exists();
        if (!$exists) {
            DB::table('settings')->insert([
                'key' => 'tarif_perjalanan_dinas',
                'value' => '80000',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('perjalanan_dinas', function (Blueprint $table) {
            if (Schema::hasColumn('perjalanan_dinas', 'tarif_per_hari')) {
                $table->dropColumn('tarif_per_hari');
            }
        });
    }
};
