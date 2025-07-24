<?php

namespace Database\Seeders;

use App\Models\Buyer;
use Illuminate\Database\Seeder;

class BuyerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    { 
       
        
        //11 FAL
        Buyer::create([
            'division_id' => 2,
            'division_name' => 'FAL-Factory',
            'company_id' => 3,
            'company_name' => 'FAL',
            'name' => 'Tex-EBO'
        ]);

    }
}
