<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StylesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $styles = [
            'FIRE 5243/5144',
            'FIRE 5232/4939',
            'FIRE 5201/4926',
            'FIRE 5206/4917',
            'LNSD_6044',
            'FIRE 5230/4934',
            'EVR-243-X015',
            'LNSD_6047',
            'HOTT 2119/2081',
            'HOTT 2108/2089',
            'EVER-243-X016',
            'FIRE 5231/4938',
            'EVR-243-F035',
            'FIRE 4917JB'
        ];

        foreach ($styles as $style) {
            DB::table('styles')->insert([
                'name' => $style,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
