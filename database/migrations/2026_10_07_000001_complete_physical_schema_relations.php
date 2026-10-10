<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('price_settings', 'holiday_id')) {
            Schema::table('price_settings', function (Blueprint $table): void {
                $table->foreignId('holiday_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('holidays')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }

        if (! Schema::hasTable('price_setting_room_types')) {
            Schema::create('price_setting_room_types', function (Blueprint $table): void {
                $table->foreignId('price_setting_id')->constrained('price_settings')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('room_type_id')->constrained('room_types')->cascadeOnDelete()->cascadeOnUpdate();
                $table->primary(['price_setting_id', 'room_type_id'], 'price_setting_room_types_primary');
            });
        }

        if (! Schema::hasTable('user_permissions')) {
            Schema::create('user_permissions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
                $table->string('permission_key', 100);
                $table->timestamps();
                $table->unique(['user_id', 'permission_key'], 'user_permissions_user_key_unique');
            });
        }

        if (DB::getDriverName() === 'mysql') {
            $hasRatingCheck = DB::table('information_schema.TABLE_CONSTRAINTS')
                ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'reviews')
                ->where('CONSTRAINT_NAME', 'reviews_rating_range_check')
                ->exists();

            if (! $hasRatingCheck) {
                DB::statement('ALTER TABLE reviews ADD CONSTRAINT reviews_rating_range_check CHECK (rating BETWEEN 1 AND 5)');
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $hasRatingCheck = DB::table('information_schema.TABLE_CONSTRAINTS')
                ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'reviews')
                ->where('CONSTRAINT_NAME', 'reviews_rating_range_check')
                ->exists();

            if ($hasRatingCheck) {
                $version = strtolower((string) DB::selectOne('SELECT VERSION() AS version')->version);
                $dropClause = str_contains($version, 'mariadb') ? 'DROP CONSTRAINT' : 'DROP CHECK';
                DB::statement("ALTER TABLE reviews {$dropClause} reviews_rating_range_check");
            }
        }

        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('price_setting_room_types');

        if (Schema::hasColumn('price_settings', 'holiday_id')) {
            Schema::table('price_settings', function (Blueprint $table): void {
                $table->dropForeign(['holiday_id']);
                $table->dropColumn('holiday_id');
            });
        }
    }
};
