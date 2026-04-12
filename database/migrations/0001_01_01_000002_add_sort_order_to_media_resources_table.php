<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('media_resources', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('meta');
            $table->index('sort_order');
        });

        // Initialize existing records with sequential sort_order values, oldest first
        $ids = DB::table('media_resources')
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('id');

        foreach ($ids as $i => $id) {
            DB::table('media_resources')
                ->where('id', $id)
                ->update(['sort_order' => $i + 1]);
        }
    }

    public function down(): void
    {
        Schema::table('media_resources', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
