<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('cart_key', 40);
            $table->foreignId('gown_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'cart_key', 'gown_id']);
            $table->index(['user_id', 'cart_key', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_cart_items');
    }
};
