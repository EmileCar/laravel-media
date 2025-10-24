<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('media_resources', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['image', 'video', 'audio', 'document']);
            $table->enum('source', ['external', 'local'])->default('local');
            $table->string('file_name')->nullable();
            $table->string('path')->nullable();
            $table->string('url')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('date')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_resources');
    }
};
