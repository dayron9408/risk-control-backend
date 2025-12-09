<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rule_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('risk_rules')->onDelete('cascade');
            $table->enum('action_type', ['EMAIL', 'SLACK', 'DISABLE_ACCOUNT', 'DISABLE_TRADING']);
            $table->json('config')->nullable(); // Para configuraciones futuras
            $table->integer('order')->default(0); // Orden de ejecución
            $table->timestamps();

            $table->index(['rule_id', 'action_type']);
            $table->index('action_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rule_actions');
    }
};
