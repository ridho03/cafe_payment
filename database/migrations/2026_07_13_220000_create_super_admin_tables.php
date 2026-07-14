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
        Schema::create('cafes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_path')->nullable();
            $table->text('address')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('domain')->nullable();
            $table->string('subdomain')->nullable();
            $table->string('status')->default('active')->index();
            $table->date('active_from')->nullable();
            $table->date('active_until')->nullable();
            $table->timestamps();
        });

        Schema::create('cafe_midtrans_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->unique()->constrained('cafes')->cascadeOnDelete();
            $table->string('mode')->default('sandbox');
            $table->string('merchant_id')->nullable();
            $table->text('client_key')->nullable();
            $table->text('server_key')->nullable();
            $table->boolean('is_integrated')->default(false);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cafe_id')->nullable()->constrained('cafes')->nullOnDelete();
            $table->string('action');
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'cafe_id')) {
                $table->foreignId('cafe_id')->nullable()->after('role')->constrained('cafes')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('cafe_id');
            }
        });

        $defaultCafeId = DB::table('cafes')->insertGetId([
            'name' => config('app.name', 'Payment Cafe'),
            'slug' => Str::slug(config('app.name', 'payment-cafe')) ?: 'payment-cafe',
            'status' => 'active',
            'active_from' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')
            ->whereIn('role', ['admin', 'cashier', 'kitchen'])
            ->whereNull('cafe_id')
            ->update(['cafe_id' => $defaultCafeId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'cafe_id')) {
                $table->dropConstrainedForeignId('cafe_id');
            }

            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });

        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('cafe_midtrans_settings');
        Schema::dropIfExists('cafes');
    }
};
