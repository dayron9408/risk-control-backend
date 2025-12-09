<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->enum('type', ['BUY', 'SELL']);
            $table->decimal('volume', 10, 2);
            $table->dateTime('open_time');
            $table->dateTime('close_time')->nullable();
            $table->decimal('open_price', 15, 5);
            $table->decimal('close_price', 15, 5)->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();

            $table->index('account_id');
            $table->index('status');
            $table->index('open_time');
            $table->index(['account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
