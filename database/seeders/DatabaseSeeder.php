<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Core system seeders
            PermissionSeeder::class,
            RoleSeeder::class,
            OrganizationSeeder::class,
            UserSeeder::class,

            // Work module seeders
            WorkModuleSeeder::class,
        ]);
    }
}
