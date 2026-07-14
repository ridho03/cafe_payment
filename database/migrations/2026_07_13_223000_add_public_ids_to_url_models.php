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
        foreach (['cafes', 'users', 'menu_items', 'cafe_tables', 'orders'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'public_id')) {
                    $table->uuid('public_id')->nullable()->after('id')->unique();
                }
            });

            DB::table($tableName)
                ->whereNull('public_id')
                ->orderBy('id')
                ->select('id')
                ->chunkById(200, function ($rows) use ($tableName) {
                    foreach ($rows as $row) {
                        DB::table($tableName)
                            ->where('id', $row->id)
                            ->update(['public_id' => (string) Str::uuid()]);
                    }
                });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['orders', 'cafe_tables', 'menu_items', 'users', 'cafes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'public_id')) {
                    $table->dropUnique($tableName.'_public_id_unique');
                    $table->dropColumn('public_id');
                }
            });
        }
    }
};
