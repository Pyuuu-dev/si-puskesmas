<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arsip_links', function (Blueprint $table) {
            $table->string('icon_preset', 30)->nullable()->after('thumbnail');
            $table->index('icon_preset');
        });
    }

    public function down(): void
    {
        Schema::table('arsip_links', function (Blueprint $table) {
            $table->dropIndex(['icon_preset']);
            $table->dropColumn('icon_preset');
        });
    }
};
