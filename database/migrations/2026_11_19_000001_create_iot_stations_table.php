<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_stations', function (Blueprint $table): void {
            $table->id();
            $table->string('station_code', 48)->unique();
            $table->string('name', 120);
            $table->string('neighborhood', 120);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('sensor_type', 32);
            $table->string('metric_name', 64);
            $table->string('metric_unit', 16);
            $table->decimal('warning_threshold', 10, 2)->nullable();
            $table->decimal('critical_threshold', 10, 2)->nullable();
            $table->decimal('reading_value', 10, 2)->nullable();
            $table->string('reading_status', 20)->default('offline');
            $table->timestampTz('read_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->index(['is_active', 'reading_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_stations');
    }
};
