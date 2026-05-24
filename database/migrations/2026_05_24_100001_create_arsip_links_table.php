<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arsip_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->nullable()
                ->constrained('arsip_folders')->nullOnDelete();
            $table->string('title', 255);
            $table->text('url');
            $table->char('url_hash', 40);
            $table->string('domain', 150)->nullable();
            $table->string('favicon', 500)->nullable();
            $table->string('thumbnail', 500)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->unsignedInteger('open_count')->default(0);
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamp('meta_fetched_at')->nullable();
            $table->string('meta_status', 20)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['folder_id', 'sort_order']);
            $table->index('is_favorite');
            $table->index('is_pinned');
            $table->index('last_opened_at');
            $table->index('url_hash');
            $table->index('domain');
        });

        // FULLTEXT index hanya untuk MySQL/MariaDB.
        // SQLite (dev) fallback otomatis ke LIKE di scope search.
        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            try {
                DB::statement('ALTER TABLE arsip_links ADD FULLTEXT arsip_links_ft (title, notes, domain)');
            } catch (\Throwable $e) {
                // Engine MyISAM/old MySQL bisa fail — biarkan, scope search fallback ke LIKE.
                report($e);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('arsip_links');
    }
};
