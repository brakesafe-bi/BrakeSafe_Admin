<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accident_prone_areas', function (Blueprint $table): void {
            $table->id();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('barangay');
            $table->string('municipality');
            $table->string('province')->default('Cebu');
            $table->string('country')->default('Philippines');
            $table->string('place');
            $table->string('reason')->default('Accident Prone Area');
            $table->timestamps();
            $table->index(['municipality', 'barangay']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accident_prone_areas');
    }
};
