<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_details', function (Blueprint $table) {
            $table->string('car_photo')->nullable()->after('idle_time');
            $table->boolean('car_photo_approved')->default(false)->after('car_photo');
        });
    }

    public function down(): void
    {
        Schema::table('driver_details', function (Blueprint $table) {
            $table->dropColumn(['car_photo', 'car_photo_approved']);
        });
    }
};
