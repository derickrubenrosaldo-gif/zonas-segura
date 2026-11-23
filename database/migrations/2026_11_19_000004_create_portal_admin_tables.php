<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_bulletins', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 160);
            $table->string('area', 120);
            $table->string('url', 512);
            $table->timestampTz('issued_at');
            $table->timestampTz('expires_at');
            $table->boolean('published')->default(false);
            $table->index(['published', 'expires_at']);
        });
        Schema::create('portal_settings', function (Blueprint $table): void {
            $table->string('key', 64)->primary();
            $table->text('value');
        });
        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('portal_settings');
        Schema::dropIfExists('portal_bulletins');
    }
};
