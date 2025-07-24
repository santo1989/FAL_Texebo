<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ColorsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $colors = [
            'Black',
            'White',
            'Navy',
            'Grey Marl',
            'Grey',
            'Black',
            'White',
            'Navy Skull',
            'Dark Lightning',
            'Black',
            'Charcoal Mar',
            'Light Heather',
            'Khaki',
            'White',
            'Charcoal Mar',
            'Black',
            'Navy',
            'Charcoal Mar',
            'Black',
            'Black Charcoal Logo',
            'White Charcoal Logo',
            'Black',
            'Charcoal Mar',
            'Black',
            'White',
            'Charcoal',
            'Ligthening'
        ];

        // Ensure uniqueness
        $colors = array_unique(array_map('ucwords', $colors));

        foreach ($colors as $color) {
            DB::table('colors')->insert([
                'name' => $color,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}