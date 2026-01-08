<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('discount_audits', function (Blueprint $table) {
            $table->id();
            if (app()->runningUnitTests()) {
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('discount_id')->nullable();
                $table->index(['user_id']);
                $table->index(['discount_id']);
            } else {
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('discount_id')->nullable()->constrained()->nullOnDelete();
            }
            $table->enum('action', ['assigned','revoked','applied']);
            $table->decimal('amount', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_audits');
    }
};
