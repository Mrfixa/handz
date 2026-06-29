<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// M3: optional config-driven delivery fee + tax recorded per order. Both default
// to 0 so existing pricing is unchanged until the business configures rates
// (mart_delivery_fee / mart_tax_percent business settings).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mart_orders', function (Blueprint $table) {
            $table->decimal('delivery_fee', 10, 2)->default(0)->after('discount_amount');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('delivery_fee');
        });
    }

    public function down(): void
    {
        Schema::table('mart_orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_fee', 'tax_amount']);
        });
    }
};
