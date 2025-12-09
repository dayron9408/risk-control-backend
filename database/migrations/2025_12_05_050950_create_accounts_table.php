<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('login')->unique();
            $table->enum('trading_status', ['enable', 'disable'])->default('enable');
            $table->enum('status', ['enable', 'disable'])->default('enable');
            $table->timestamps();
            $table->softDeletes();

            $table->index('login');
            $table->index('trading_status');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
