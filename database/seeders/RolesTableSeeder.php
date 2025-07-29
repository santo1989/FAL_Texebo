<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //before running this seeder, make sure to off the gate key  in AuthServiceProvider.php

        //1
        Role::create([
            'name' => 'Admin'
        ]);

        //2
        Role::create([
            'name' => 'General'
        ]);

        //3
        Role::create([
            'name' => 'Cutting'
        ]);

        //4
        Role::create([
            'name' => 'Print Send'
        ]);

        //5
        Role::create([
            'name' => 'Print Receive'
        ]);

        //6
        Role::create([
            'name' => 'Input'
        ]);

        //7
        Role::create([
            'name' => 'Output'
        ]);


        //8
        Role::create([
            'name' => 'Packing'
        ]);


        //9
        Role::create([
            'name' => 'Shipment'
        ]);

        //10
        Role::create([
            'name' => 'HR'
        ]);
 

        
    }
}
