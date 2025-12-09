<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('incidents')->onDelete('cascade');
            $table->enum('action_type', ['EMAIL', 'SLACK', 'DISABLE_ACCOUNT', 'DISABLE_TRADING']);
            $table->enum('status', ['PENDING', 'EXECUTED', 'FAILED'])->default('PENDING');
            $table->text('details')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index('incident_id');
            $table->index('status');
            $table->index('action_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
