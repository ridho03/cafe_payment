<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('menu_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('menu_categories', 'cafe_id')) {
                $table->foreignId('cafe_id')->nullable()->after('id')->constrained('cafes')->nullOnDelete();
            }
        });

        $defaultCafeId = DB::table('cafes')->orderBy('id')->value('id');

        if ($defaultCafeId) {
            DB::table('menu_categories')
                ->whereNull('cafe_id')
                ->update(['cafe_id' => $defaultCafeId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_categories', function (Blueprint $table) {
            if (Schema::hasColumn('menu_categories', 'cafe_id')) {
                $table->dropConstrainedForeignId('cafe_id');
            }
        });
    }
};
