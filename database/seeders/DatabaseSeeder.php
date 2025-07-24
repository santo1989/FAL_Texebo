<?php

namespace Database\Seeders;

use App\Models\SewingProcessList;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        $this->call([

            RolesTableSeeder::class,
            UsersTableSeeder::class,
            DivisionSeeder::class,
            CompanySeeder::class,
            DepartmentSeeder::class,
            DesignationSeeder::class,
            BuyerSeeder::class,
            StylesTableSeeder::class,
            ColorsTableSeeder::class,
            SizesTableSeeder::class,
        ]);
    }
}
