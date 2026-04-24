<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VariationSeeder extends Seeder
{
    public function run(): void
    {
        $variations = [
            ['name' => 'acné'],
            ['name' => 'piel sensible'],
            ['name' => 'dermatitis'],
            ['name' => 'rosácea'],
            ['name' => 'psoriasis'],
            ['name' => 'deshidratación profunda'],
        ];

        DB::table('variations')->upsert($variations, ['name']);
    }
}
