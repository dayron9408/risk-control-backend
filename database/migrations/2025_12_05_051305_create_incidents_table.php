<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('risk_rules')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->foreignId('trade_id')->nullable()->constrained('trades')->onDelete('set null');
            $table->enum('severity', ['HARD', 'SOFT']);
            $table->text('description');
            $table->timestamps();

            $table->index('account_id');
            $table->index('rule_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
