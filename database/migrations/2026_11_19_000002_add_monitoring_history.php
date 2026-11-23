<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_readings', function (Blueprint $table): void {
            $table->foreignId('station_id')->constrained('iot_stations')->cascadeOnDelete();
            $table->timestampTz('read_at');
            $table->decimal('value', 10, 2);
            $table->unique(['station_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_readings');
    }
};
