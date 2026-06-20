<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Guarded: an earlier migration (2026_05_26_000003_vito_create_otps_table)
        // may already have created this identical table. Without this check a real
        // `php artisan migrate` fails with "table already exists".
        if (Schema::hasTable('vito_otps')) {
            return;
        }

        Schema::create('vito_otps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('phone', 30)->index();
            $table->string('otp_hash');
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->tinyInteger('attempts')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vito_otps');
    }
};
