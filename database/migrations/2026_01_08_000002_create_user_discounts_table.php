<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_discounts', function (Blueprint $table) {
            $table->id();
            if (app()->runningUnitTests()) {
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('discount_id');
                $table->index(['user_id']);
                $table->index(['discount_id']);
            } else {
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('discount_id')->constrained()->cascadeOnDelete();
            }
            $table->integer('used_count')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'discount_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_discounts');
    }
};