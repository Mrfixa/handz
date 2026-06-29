<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\BusinessManagement\Database\Seeders\BusinessManagementDatabaseSeeder;
use Modules\VehicleManagement\Database\Seeders\VehicleManagementDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Order matters: seed the accounts + vehicle catalogue first so a failure in the
        // (heavier, settings-heavy) BusinessManagementDatabaseSeeder can never block the
        // test users or the driver vehicle-registration dropdowns.
        $this->call(AdminUserSeeder::class);
        $this->call(AdminUserWalletSeeder::class);
        $this->call(VehicleManagementDatabaseSeeder::class);
        $this->call(DefaultUsersSeeder::class);
        $this->call(BusinessManagementDatabaseSeeder::class);
    }
}
