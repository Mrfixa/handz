<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Seeds default, ready-to-use accounts so the apps and admin panel are usable
 * out of the box:
 *   - Customer:  username "customer"  PIN "123456"
 *   - Driver:    username "driver"    PIN "123456"  (verified)
 *   - Admin:     admin@admin.com      password "12345678"  (see AdminUserSeeder)
 *
 * Idempotent: safe to run repeatedly (uses updateOrInsert / existence checks).
 * SECURITY: change these credentials before going to production.
 */
class DefaultUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Demo customer/driver use guessable PINs (123456) and must never exist in
        // production. Skip them there unless explicitly opted in (SEED_DEMO_USERS=true
        // for a staging/demo box). User levels are harmless and always seeded.
        if (app()->environment('production') && !filter_var(env('SEED_DEMO_USERS', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->command?->warn('DefaultUsersSeeder: skipping demo customer/driver accounts in production (set SEED_DEMO_USERS=true to override).');
            $this->ensureUserLevel('customer');
            $this->ensureUserLevel('driver');
            return;
        }

        $customerLevelId = $this->ensureUserLevel('customer');
        $driverLevelId = $this->ensureUserLevel('driver');

        $customerId = $this->ensureUser('customer', 'customer', '123456', $customerLevelId, 'Demo', 'Customer');
        $this->ensureUserAccount($customerId);

        $driverId = $this->ensureUser('driver', 'driver', '123456', $driverLevelId, 'Demo', 'Driver');
        $this->ensureUserAccount($driverId);
        $this->ensureDriverDetails($driverId);
    }

    private function ensureUserLevel(string $userType): ?string
    {
        if (!Schema::hasTable('user_levels')) {
            return null;
        }

        $existing = DB::table('user_levels')->where('user_type', $userType)->orderBy('sequence')->first();
        if ($existing) {
            return $existing->id;
        }

        $id = (string) Str::uuid();
        DB::table('user_levels')->insert([
            'id' => $id,
            'sequence' => 1,
            'name' => 'Level 1',
            'reward_type' => 'no_reward',
            'reward_amount' => 0,
            'targeted_ride' => 0,
            'targeted_ride_point' => 0,
            'targeted_amount' => 0,
            'targeted_amount_point' => 0,
            'targeted_cancel' => 0,
            'targeted_cancel_point' => 0,
            'targeted_review' => 0,
            'targeted_review_point' => 0,
            'user_type' => $userType,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function ensureUser(string $username, string $type, string $pin, ?string $levelId, string $first, string $last): string
    {
        $existing = DB::table('users')->where('username', $username)->first();

        $data = [
            'first_name' => $first,
            'last_name' => $last,
            'username' => $username,
            'user_type' => $type,
            'is_active' => 1,
            'pin_hash' => Hash::make($pin),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('users', 'user_level_id') && $levelId) {
            $data['user_level_id'] = $levelId;
        }
        if (Schema::hasColumn('users', 'pin_attempts')) {
            $data['pin_attempts'] = 0;
        }
        if (Schema::hasColumn('users', 'ref_code')) {
            $data['ref_code'] = strtoupper(Str::random(8));
        }

        if ($existing) {
            DB::table('users')->where('id', $existing->id)->update($data);
            return $existing->id;
        }

        $id = (string) Str::uuid();
        DB::table('users')->insert(array_merge($data, [
            'id' => $id,
            'created_at' => now(),
        ]));

        return $id;
    }

    private function ensureUserAccount(string $userId): void
    {
        if (!Schema::hasTable('user_accounts')) {
            return;
        }
        if (DB::table('user_accounts')->where('user_id', $userId)->exists()) {
            return;
        }
        DB::table('user_accounts')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'payable_balance' => 0,
            'receivable_balance' => 0,
            'received_balance' => 0,
            'pending_balance' => 0,
            'wallet_balance' => 0,
            'total_withdrawn' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureDriverDetails(string $userId): void
    {
        if (!Schema::hasTable('driver_details')) {
            return;
        }

        $data = [
            'is_online' => 0,
            'availability_status' => 'available',
            'updated_at' => now(),
        ];
        // Mark as approved/verified using whichever columns the schema has.
        if (Schema::hasColumn('driver_details', 'is_verified')) {
            $data['is_verified'] = 1;
        }
        if (Schema::hasColumn('driver_details', 'is_approved')) {
            $data['is_approved'] = 1;
        }
        if (Schema::hasColumn('driver_details', 'car_photo_approved')) {
            $data['car_photo_approved'] = 1;
        }
        if (Schema::hasColumn('driver_details', 'is_suspended')) {
            $data['is_suspended'] = 0;
        }

        if (DB::table('driver_details')->where('user_id', $userId)->exists()) {
            DB::table('driver_details')->where('user_id', $userId)->update($data);
            return;
        }
        DB::table('driver_details')->insert(array_merge($data, [
            'user_id' => $userId,
            'created_at' => now(),
        ]));
    }
}
