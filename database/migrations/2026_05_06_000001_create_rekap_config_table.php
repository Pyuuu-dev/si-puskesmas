<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekap_config', function (Blueprint $table) {
            $table->id();
            $table->string('tipe'); // TL or PSW
            $table->integer('level'); // 1, 2, 3, 4...
            $table->integer('menit_min');
            $table->integer('menit_max')->nullable(); // null means unlimited (>120)
            $table->string('label');
            $table->timestamps();
        });

        // Seed default data
        $configs = [
            ['tipe' => 'TL', 'level' => 1, 'menit_min' => 30, 'menit_max' => 60, 'label' => 'TL 1 (30-60 menit)'],
            ['tipe' => 'TL', 'level' => 2, 'menit_min' => 61, 'menit_max' => 90, 'label' => 'TL 2 (61-90 menit)'],
            ['tipe' => 'TL', 'level' => 3, 'menit_min' => 91, 'menit_max' => 120, 'label' => 'TL 3 (91-120 menit)'],
            ['tipe' => 'TL', 'level' => 4, 'menit_min' => 121, 'menit_max' => null, 'label' => 'TL 4 (>120 menit)'],
            ['tipe' => 'PSW', 'level' => 1, 'menit_min' => 30, 'menit_max' => 60, 'label' => 'PSW 1 (30-60 menit)'],
            ['tipe' => 'PSW', 'level' => 2, 'menit_min' => 61, 'menit_max' => 90, 'label' => 'PSW 2 (61-90 menit)'],
            ['tipe' => 'PSW', 'level' => 3, 'menit_min' => 91, 'menit_max' => 120, 'label' => 'PSW 3 (91-120 menit)'],
            ['tipe' => 'PSW', 'level' => 4, 'menit_min' => 121, 'menit_max' => null, 'label' => 'PSW 4 (>120 menit)'],
        ];

        foreach ($configs as $config) {
            DB::table('rekap_config')->insert(array_merge($config, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_config');
    }
};
