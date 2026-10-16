<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'member'])->default('member')->index();
            $table->timestamps();
        });

        // jti of tokens invalidated by logout; rows can be pruned once expires_at passes.
        Schema::create('revoked_tokens', function (Blueprint $table) {
            $table->string('jti', 64)->primary();
            $table->timestamp('expires_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revoked_tokens');
        Schema::dropIfExists('users');
    }
};
