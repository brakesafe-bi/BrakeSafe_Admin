<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('accident_prone_areas', 'emergency_hit_count')) {
            Schema::table('accident_prone_areas', function (Blueprint $table): void {
                $table->unsignedInteger('emergency_hit_count')->default(0);
            });
        }
        if (!Schema::hasColumn('accident_prone_areas', 'is_auto_generated')) {
            Schema::table('accident_prone_areas', function (Blueprint $table): void {
                $table->boolean('is_auto_generated')->default(false);
            });
        }

        if (!Schema::hasColumn('brake_events', 'apa_processed_at')) {
            Schema::table('brake_events', function (Blueprint $table): void {
                $table->timestamp('apa_processed_at')->nullable();
            });
        }

        if (!Schema::hasTable('emergency_brake_apa_clusters')) {
            Schema::create('emergency_brake_apa_clusters', function (Blueprint $table): void {
                $table->id();
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->unsignedInteger('hit_count')->default(0);
                $table->foreignId('accident_prone_area_id')
                    ->nullable()
                    ->constrained('accident_prone_areas', 'id', 'ebac_area_fk')
                    ->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasIndex('emergency_brake_apa_clusters', 'ebac_area_hits_idx')) {
            Schema::table('emergency_brake_apa_clusters', function (Blueprint $table): void {
                $table->index(['accident_prone_area_id', 'hit_count'], 'ebac_area_hits_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_brake_apa_clusters');

        Schema::table('brake_events', function (Blueprint $table): void {
            $table->dropColumn('apa_processed_at');
        });

        Schema::table('accident_prone_areas', function (Blueprint $table): void {
            $table->dropColumn(['emergency_hit_count', 'is_auto_generated']);
        });
    }
};
