<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AbsenceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'justificada'],
            ['name' => 'remunerada'],
            ['name' => 'injustificada'],
        ];

        DB::table('absence_types')->upsert($types, ['name']);
    }
}
