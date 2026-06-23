<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mart_orders', function (Blueprint $table) {
            $table->decimal('driver_lat', 10, 7)->nullable()->after('delivery_lng');
            $table->decimal('driver_lng', 10, 7)->nullable()->after('driver_lat');
            $table->index(['driver_lat', 'driver_lng'], 'mart_orders_driver_location_idx');
        });
    }

    public function down(): void
    {
        Schema::table('mart_orders', function (Blueprint $table) {
            $table->dropIndex('mart_orders_driver_location_idx');
            $table->dropColumn(['driver_lat', 'driver_lng']);
        });
    }
};
