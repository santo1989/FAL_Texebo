<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //1
        Division::create([
            'name' => 'Corporate'
        ]);
        //2
        Division::create([
            'name' => 'Factory'
        ]);
        //3
        Division::create([
            'name' => 'Fabric'
        ]);
    }
}

