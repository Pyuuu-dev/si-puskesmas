<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;

return new class extends Migration
{
    public function up(): void
    {
        // Expand absensi status enum to include more options
        // SQLite doesn't support ALTER COLUMN, so we handle this differently
        // For MySQL/PostgreSQL:
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE absensi MODIFY COLUMN status ENUM('hadir','izin','sakit','cuti','dinas_luar','ijin_belajar','cuti_bersalin','cuti_tahunan','alfa') DEFAULT 'alfa'");
        }

        // Add keterangan column to absensi if not exists
        if (!Schema::hasColumn('absensi', 'keterangan')) {
            Schema::table('absensi', function (Blueprint $table) {
                $table->string('keterangan')->nullable()->after('jam');
            });
        }

        // Create jam_kerja table for dynamic work hours per day
        Schema::create('jam_kerja', function (Blueprint $table) {
            $table->id();
            $table->string('hari'); // senin, selasa, rabu, kamis, jumat, sabtu
            $table->time('jam_masuk')->default('07:50'); // apel pagi
            $table->time('jam_pulang'); // apel siang
            $table->integer('konversi_induk_masuk')->default(0); // menit dikurang dari jam masuk
            $table->integer('konversi_desa_masuk')->default(0); // menit dikurang dari jam masuk
            $table->integer('konversi_induk_pulang')->default(0); // menit ditambah ke jam pulang
            $table->integer('konversi_desa_pulang')->default(0); // menit ditambah ke jam pulang
            $table->timestamps();

            $table->unique('hari');
        });

        // Seed default jam kerja
        $days = [
            ['hari' => 'senin', 'jam_masuk' => '07:50', 'jam_pulang' => '14:30', 'konversi_induk_masuk' => 20, 'konversi_desa_masuk' => 30, 'konversi_induk_pulang' => 10, 'konversi_desa_pulang' => 120],
            ['hari' => 'selasa', 'jam_masuk' => '07:50', 'jam_pulang' => '14:30', 'konversi_induk_masuk' => 20, 'konversi_desa_masuk' => 30, 'konversi_induk_pulang' => 10, 'konversi_desa_pulang' => 120],
            ['hari' => 'rabu', 'jam_masuk' => '07:50', 'jam_pulang' => '14:30', 'konversi_induk_masuk' => 20, 'konversi_desa_masuk' => 30, 'konversi_induk_pulang' => 10, 'konversi_desa_pulang' => 120],
            ['hari' => 'kamis', 'jam_masuk' => '07:50', 'jam_pulang' => '14:30', 'konversi_induk_masuk' => 20, 'konversi_desa_masuk' => 30, 'konversi_induk_pulang' => 10, 'konversi_desa_pulang' => 120],
            ['hari' => 'jumat', 'jam_masuk' => '07:50', 'jam_pulang' => '11:00', 'konversi_induk_masuk' => 20, 'konversi_desa_masuk' => 30, 'konversi_induk_pulang' => 0, 'konversi_desa_pulang' => 0],
            ['hari' => 'sabtu', 'jam_masuk' => '07:50', 'jam_pulang' => '13:00', 'konversi_induk_masuk' => 20, 'konversi_desa_masuk' => 30, 'konversi_induk_pulang' => 30, 'konversi_desa_pulang' => 90],
        ];

        foreach ($days as $day) {
            DB::table('jam_kerja')->insert(array_merge($day, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('jam_kerja');

        if (Schema::hasColumn('absensi', 'keterangan')) {
            Schema::table('absensi', function (Blueprint $table) {
                $table->dropColumn('keterangan');
            });
        }
    }
};
