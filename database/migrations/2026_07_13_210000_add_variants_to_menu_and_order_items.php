<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->json('variants')->nullable()->after('image_url');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('variant')->nullable()->after('name_snapshot');
        });

        DB::table('menu_items')
            ->whereIn('menu_category_id', function ($query) {
                $query->select('id')
                    ->from('menu_categories')
                    ->whereIn('name', ['Coffee', 'Non Coffee']);
            })
            ->update(['variants' => json_encode(['hot', 'ice'])]);
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('variant');
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn('variants');
        });
    }
};
