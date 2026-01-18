<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('media_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();

            $table->index('slug');
        });

        Schema::create('media_resource_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_resource_id')->constrained('media_resources')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('media_tags')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['media_resource_id', 'tag_id']);
            $table->index('media_resource_id');
            $table->index('tag_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_tags');
        Schema::dropIfExists('media_resource_tag');
    }
};
