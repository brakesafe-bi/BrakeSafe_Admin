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
        Schema::create('brake_events', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_id');
            $table->string('device_id');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed_kmh', 6, 2);
            $table->decimal('deceleration_force', 6, 2);
            $table->enum('brake_classification', ['soft', 'medium', 'hard', 'emergency']);
            $table->text('classification_reason')->nullable();
            $table->decimal('brake_score', 5, 2)->nullable();
            $table->enum('municipality', ['Bantayan', 'Madridejos', 'Santa Fe']);
            $table->string('barangay')->nullable();
            $table->string('road_segment')->nullable();
            $table->enum('direction', ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'])->nullable();
            $table->dateTime('timestamp');
            $table->timestamps();
            $table->index('brake_classification');
            $table->index('municipality');
            $table->index('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brake_events');
    }
};
