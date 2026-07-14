<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('menu_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('menu_categories', 'public_id')) {
                $table->uuid('public_id')->nullable()->after('id')->unique();
            }
        });

        DB::table('menu_categories')
            ->whereNull('public_id')
            ->orderBy('id')
            ->select('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('menu_categories')
                        ->where('id', $row->id)
                        ->update(['public_id' => (string) Str::uuid()]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_categories', function (Blueprint $table) {
            if (Schema::hasColumn('menu_categories', 'public_id')) {
                $table->dropUnique('menu_categories_public_id_unique');
                $table->dropColumn('public_id');
            }
        });
    }
};
