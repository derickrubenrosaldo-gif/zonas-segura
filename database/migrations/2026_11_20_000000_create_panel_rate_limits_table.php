<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE panel_rate_limits (
            "key" varchar(255) PRIMARY KEY,
            "value" text NOT NULL,
            expiration integer NOT NULL
        )');
    }

    public function down(): void
    {
        Schema::dropIfExists('panel_rate_limits');
    }
};
