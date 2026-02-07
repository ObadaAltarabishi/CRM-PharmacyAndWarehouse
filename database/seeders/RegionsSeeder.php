<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Region;

class RegionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = [
            ['name' => 'مالكي'],
            ['name' => 'مهاجرين'],
            ['name' => 'شعلان'],
            ['name' => 'ركن الدين'],
            ['name' => 'شارع بغداد'],
            ['name' => 'برزة'],
            ['name' => 'جسر الابيض'],
            ['name' => 'مشروع دمر'],
            ['name' => 'ضاحية قدسيا'],
            ['name' => 'برامكة'],
            ['name' => 'اخرى'],
         
        ];

        DB::table('regions')->insert($regions, ['name']);
    }
}
