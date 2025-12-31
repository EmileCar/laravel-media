<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('media_resources', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['image', 'video', 'audio', 'document'])->index();
            $table->enum('source', ['local', 'external'])->default('local')->index();
            $table->string('path')->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->date('date')->nullable()->index();
            $table->json('meta')->nullable();

            $table->string('thumbnail_path')->nullable();
            $table->string('thumbnail_url', 2048)->nullable();

            $table->timestamps();

            $table->unique(['path'], 'unique_path');

            $table->index(['source', 'type'], 'source_type_index');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_resources');
    }
};
