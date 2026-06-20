<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Idempotent: only create the super-admin if it does not already exist,
        // so re-seeding never duplicates the row or mutates its id (FK-safe).
        if (DB::table('users')->where('email', 'admin@admin.com')->exists()) {
            return;
        }

        DB::table('users')->insert([
            'id' => Uuid::uuid4(),
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => bcrypt(12345678),
            'user_type' => 'super-admin',
            'is_active' => true
        ]);
    }
}
