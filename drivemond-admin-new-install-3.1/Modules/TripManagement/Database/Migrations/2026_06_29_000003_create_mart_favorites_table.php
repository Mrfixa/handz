<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// GoMart parity: per-customer product favorites / wishlist.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mart_favorites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id');
            $table->uuid('product_id');
            $table->timestamps();
            $table->unique(['customer_id', 'product_id']);
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mart_favorites');
    }
};
