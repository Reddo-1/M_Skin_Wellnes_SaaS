<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = User::updateOrCreate(
            ['email' => 'ricardo.renones@gmail.com'],
            [
                'center_id' => null,
                'name' => 'Superusuario',
                'phone' => null,
                'birth_date' => null,
                'password' => '1234',
                'registration_source' => null,
                'is_active' => true,
            ]
        );

        $superadmin->syncRoles(['superadmin']);
    }
}
