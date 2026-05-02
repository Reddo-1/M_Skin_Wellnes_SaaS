<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VariationSeeder extends Seeder
{
    public function run(): void
    {
        $variations = [
            ['name' => 'acne'],
            ['name' => 'rosacea'],
            ['name' => 'manchas'],
            ['name' => 'arrugas'],
            ['name' => 'deshidratacion'],
            ['name' => 'cuperosis'],
            ['name' => 'cicatrices'],
            ['name' => 'poros_dilatados'],
            ['name' => 'flacidez'],
            ['name' => 'hiperpigmentacion'],
        ];

        DB::table('variations')->upsert($variations, ['name']);
    }
}
