<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('penempatan')->default('induk')->after('unit_kerja'); // 'induk' or 'desa'
            $table->boolean('is_user')->default(true)->after('role'); // not all pegawai are login users
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['penempatan', 'is_user']);
        });
    }
};
