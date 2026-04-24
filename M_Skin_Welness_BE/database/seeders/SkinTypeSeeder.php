<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SkinTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'normal'],
            ['name' => 'oily'],
            ['name' => 'dry'],
            ['name' => 'combination'],
        ];

        DB::table('skin_types')->upsert($types, ['name']);
    }
}
