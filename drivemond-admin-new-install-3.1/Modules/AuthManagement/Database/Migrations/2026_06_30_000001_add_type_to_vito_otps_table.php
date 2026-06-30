<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * AUTH-SEC-04: Add type column to vito_otps to distinguish between
     * different OTP purposes (login, pin_recovery, etc.)
     */
    public function up(): void
    {
        Schema::table('vito_otps', function (Blueprint $table) {
            if (!Schema::hasColumn('vito_otps', 'type')) {
                $table->string('type', 30)->default('login')->after('otp_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vito_otps', function (Blueprint $table) {
            if (Schema::hasColumn('vito_otps', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
