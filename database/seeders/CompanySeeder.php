<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // division 1 = Corporate
        // division 2 = Factory
        // division 3 = Fabric 
        //1
        Company::create([
            'division_id' => 1,
            'division_name' => 'Corporate',
            'name' => 'Common Office'
        ]);
        //2
        Company::create([
            'division_id' => 2,
            'division_name' => 'Factory',
            'name' => 'TIL - Factory'
        ]);

        //3
        Company::create([
            'division_id' => 2,
            'division_name' => 'Factory',
            'name' => 'FAL - Factory'
        ]);
        //4
        Company::create([
            'division_id' => 2,
            'division_name' => 'Factory',
            'name' => 'NCL - Factory'
        ]);
        //5
        Company::create([
            'division_id' => 3,
            'division_name' => 'Fabric',
            'name' => 'TIL - Fabric'
        ]);
        //6
        Company::create([
            'division_id' => 3,
            'division_name' => 'Fabric',
            'name' => 'NCL - Fabric'
        ]);
    }
}
