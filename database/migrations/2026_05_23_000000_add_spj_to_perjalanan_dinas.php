<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perjalanan_dinas', function (Blueprint $table) {
            if (!Schema::hasColumn('perjalanan_dinas', 'spj_checked')) {
                $table->boolean('spj_checked')->default(false)->after('keterangan');
            }
            if (!Schema::hasColumn('perjalanan_dinas', 'spj_catatan')) {
                $table->string('spj_catatan')->nullable()->after('spj_checked');
            }
            if (!Schema::hasColumn('perjalanan_dinas', 'spj_checked_by')) {
                $table->unsignedBigInteger('spj_checked_by')->nullable()->after('spj_catatan');
            }
            if (!Schema::hasColumn('perjalanan_dinas', 'spj_checked_at')) {
                $table->timestamp('spj_checked_at')->nullable()->after('spj_checked_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('perjalanan_dinas', function (Blueprint $table) {
            $cols = [];
            foreach (['spj_checked', 'spj_catatan', 'spj_checked_by', 'spj_checked_at'] as $c) {
                if (Schema::hasColumn('perjalanan_dinas', $c)) $cols[] = $c;
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
