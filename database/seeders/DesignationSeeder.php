<?php

namespace Database\Seeders;

use App\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{

    public function run()
    {
        // Corporate Division =1
        Designation::create([
            'division_id' => 1,
            'division_name' => 'Corporate',
            'name' => 'Executive Director'
        ]);
        Designation::create([
            'division_id' => 1,
            'division_name' => 'Corporate',
            'name' => 'Senior General Manager'
        ]);
        Designation::create([
            'division_id' => 1,
            'division_name' => 'Corporate',
            'name' => 'Deputy General Manager'
        ]);
        Designation::create([
            'division_id' => 1,
            'division_name' => 'Corporate',
            'name' => 'Assistant General Manager'
        ]);
        Designation::create([
            'division_id' => 1,
            'division_name' => 'Corporate',
            'name' => 'Senior Manager'
        ]);
        Designation::create([
            'division_id' => 1,
            'division_name' => 'Corporate',
            'name' => 'Manager'
        ]);
        Designation::create([
            'division_id' => 1,
            'division_name' => 'Corporate',
            'name' => 'Deputy Manager'
        ]);
        Designation::create([
            'division_id' => 1,
            'division_name' => 'Corporate',
            'name' => 'Assistant  Manager'
        ]);
        Designation::create([
            'division_id' => 1,
            'division_name' => 'Corporate',
            'name' => 'Senior Executive'
        ]);
        Designation::create([
            'division_id' => 1,
            'division_name' => 'Corporate',
            'name' => 'Executive'
        ]);
        Designation::create([
            'division_id' => 1,
            'division_name' => 'Corporate',
            'name' => 'Junior Executive'
        ]);
        Designation::create([
            'division_id' => 1,
            'division_name' => 'Corporate',
            'name' => 'Management Trainee'
        ]);

        // Factory Division =2

        Designation::create([
            'division_id' => 2,
            'division_name' => 'Factory',
            'name' => 'Executive Director'
        ]);
        Designation::create([
            'division_id' => 2,
            'division_name' => 'Factory',
            'name' => 'Senior General Manager'
        ]);
        Designation::create([
            'division_id' => 2,
            'division_name' => 'Factory',
            'name' => 'Deputy General Manager'
        ]);
        Designation::create([
            'division_id' => 2,
            'division_name' => 'Factory',
            'name' => 'Assistant General Manager'
        ]);
        Designation::create([
            'division_id' => 2,
            'division_name' => 'Factory',
            'name' => 'Senior Manager'
        ]);
        Designation::create([
            'division_id' => 2,
            'division_name' => 'Factory',
            'name' => 'Manager'
        ]);
        Designation::create([
            'division_id' => 2,
            'division_name' => 'Factory',
            'name' => 'Deputy Manager'
        ]);
        Designation::create([
            'division_id' => 2,
            'division_name' => 'Factory',
            'name' => 'Assistant  Manager'
        ]);
        Designation::create([
            'division_id' => 2,
            'division_name' => 'Factory',
            'name' => 'Senior Executive'
        ]);
        Designation::create([
            'division_id' => 2,
            'division_name' => 'Factory',
            'name' => 'Production Officer'
        ]);
        Designation::create([
            'division_id' => 2,
            'division_name' => 'Factory',
            'name' => 'Executive'
        ]);
        Designation::create([
            'division_id' => 2,
            'division_name' => 'Factory',
            'name' => 'APO'
        ]);
        Designation::create([
            'division_id' => 2,
            'division_name' => 'Factory',
            'name' => 'Junior Executive'
        ]);
        Designation::create([
            'division_id' => 2,
            'division_name' => 'Factory',
            'name' => 'Management Trainee'
        ]);

        // Fabric Division =3

        Designation::create([
            'division_id' => 3,
            'division_name' => 'Fabric',
            'name' => 'Executive Director'
        ]);
        Designation::create([
            'division_id' => 3,
            'division_name' => 'Fabric',
            'name' => 'Senior General Manager'
        ]);
        Designation::create([
            'division_id' => 3,
            'division_name' => 'Fabric',
            'name' => 'Deputy General Manager'
        ]);
        Designation::create([
            'division_id' => 3,
            'division_name' => 'Fabric',
            'name' => 'Assistant General Manager'
        ]);
        Designation::create([
            'division_id' => 3,
            'division_name' => 'Fabric',
            'name' => 'Senior Manager'
        ]);
        Designation::create([
            'division_id' => 3,
            'division_name' => 'Fabric',
            'name' => 'Manager'
        ]);
        Designation::create([
            'division_id' => 3,
            'division_name' => 'Fabric',
            'name' => 'Deputy Manager'
        ]);
        Designation::create([
            'division_id' => 3,
            'division_name' => 'Fabric',
            'name' => 'Assistant  Manager'
        ]);
        Designation::create([
            'division_id' => 3,
            'division_name' => 'Fabric',
            'name' => 'Senior Executive'
        ]);
        Designation::create([
            'division_id' => 3,
            'division_name' => 'Fabric',
            'name' => 'Production Officer'
        ]);
        Designation::create([
            'division_id' => 3,
            'division_name' => 'Fabric',
            'name' => 'Executive'
        ]);
        Designation::create([
            'division_id' => 3,
            'division_name' => 'Fabric',
            'name' => 'APO'
        ]);
        Designation::create([
            'division_id' => 3,
            'division_name' => 'Fabric',
            'name' => 'Junior Executive'
        ]);
        Designation::create([
            'division_id' => 3,
            'division_name' => 'Fabric',
            'name' => 'Management Trainee'
        ]);
    }
}
