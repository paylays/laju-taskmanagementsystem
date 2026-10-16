<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Each board lane runs "WHERE status = ? ORDER BY created_at DESC, id DESC LIMIT 25";
    // this lets MySQL read the first page straight off the index instead of sorting the lane.
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
        });
    }
};
