<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->enum('type', ['DURATION', 'VOLUME', 'OPEN_TRADES']);
            $table->enum('severity', ['HARD', 'SOFT'])->default('SOFT');

            $table->integer('min_duration_seconds')->nullable();
            $table->decimal('min_factor', 5, 2)->nullable();
            $table->decimal('max_factor', 5, 2)->nullable();
            $table->integer('lookback_trades')->nullable();
            $table->integer('time_window_minutes')->nullable();
            $table->integer('min_open_trades')->nullable();
            $table->integer('max_open_trades')->nullable();


            $table->integer('incidents_before_action')->nullable()->default(1);


            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('severity');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_rules');
    }
};
