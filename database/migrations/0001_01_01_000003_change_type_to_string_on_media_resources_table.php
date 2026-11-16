<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The media type is validated by the application (MediaType enum + config),
 * so the column is a plain string: new types (like 'other') need no schema change.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('media_resources', function (Blueprint $table) {
            $table->string('type', 32)->change();
        });
    }

    public function down(): void
    {
        Schema::table('media_resources', function (Blueprint $table) {
            $table->enum('type', ['image', 'video', 'audio', 'document'])->change();
        });
    }
};
