<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// GoMart parity: richer product catalog — sale price, unit label, featured/popular
// merchandising flags, and a sold_count for popularity sorting.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mart_products', function (Blueprint $table) {
            $table->decimal('discount_price', 10, 2)->nullable()->after('price');
            $table->string('unit')->nullable()->after('discount_price');
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->boolean('is_popular')->default(false)->after('is_featured');
            $table->unsignedInteger('sold_count')->default(0)->after('is_popular');
        });
    }

    public function down(): void
    {
        Schema::table('mart_products', function (Blueprint $table) {
            $table->dropColumn(['discount_price', 'unit', 'is_featured', 'is_popular', 'sold_count']);
        });
    }
};
