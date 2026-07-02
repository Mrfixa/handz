<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records when a driver has arrived at the pickup point. This is a sub-signal:
 * current_status stays 'accepted' until the ride actually starts ('ongoing'),
 * so no state-machine consumer needs to change.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('trip_status') && !Schema::hasColumn('trip_status', 'arrived')) {
            Schema::table('trip_status', function (Blueprint $table) {
                $table->timestamp('arrived')->nullable()->after('accepted');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('trip_status') && Schema::hasColumn('trip_status', 'arrived')) {
            Schema::table('trip_status', function (Blueprint $table) {
                $table->dropColumn('arrived');
            });
        }
    }
};
