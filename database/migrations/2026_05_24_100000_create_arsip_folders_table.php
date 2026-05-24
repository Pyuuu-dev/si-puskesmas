<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arsip_folders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name', 150);
            $table->string('slug', 180);
            $table->string('icon', 50)->nullable();
            $table->string('color', 20)->nullable();
            $table->string('description', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('parent_id')
                ->references('id')->on('arsip_folders')
                ->nullOnDelete();

            $table->index(['parent_id', 'sort_order']);
            $table->index('slug');
            $table->unique(['parent_id', 'slug'], 'arsip_folders_parent_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arsip_folders');
    }
};
