<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['authentication', 'view-page', 'button-click']);
            $table->enum('action', [
                'login', 'logout', 'registration',
                'page-a', 'page-b', 'page-stats', 'page-reports',
                'buy-cow', 'download',
            ]);
            $table->dateTime('occurred_at', 6);

            $table->index('occurred_at');
            $table->index(['user_id', 'occurred_at']);
            $table->index(['type', 'occurred_at']);
            $table->index(['action', 'occurred_at']);
            $table->index(['type', 'action', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_events');
    }
};
