<?php

namespace Database\Seeders;

use App\Models\Center;
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
                'email_verified_at' => now(),
            ]
        );
        $superadmin->syncRoles(['superadmin']);

        $centerId = Center::query()->where('slug', 'demo')->value('id');

        if ($centerId === null) {
            return;
        }

        $staff = [
            ['admin@gmail.com',          'Admin del Centro',  'administrador'],
            ['recepcionista@gmail.com',  'Diego',  'recepcionista'],
            ['rrhh@gmail.com',           'David',        'rrhh'],
            ['diagnosticador@gmail.com', 'Marc',         'diagnosticador'],
            ['dermo@gmail.com',          'Raquel',      'dermo_esteticien'],
            ['fisio@gmail.com',          'Iván',        'fisioterapeuta'],
            ['manicura@gmail.com',       'Mari',     'manicurista'],
        ];

        foreach ($staff as [$email, $name, $role]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'center_id' => $centerId,
                    'name' => $name,
                    'phone' => null,
                    'birth_date' => null,
                    'password' => '1234',
                    'registration_source' => 'staff',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles([$role]);
        }

        //10 clientes, todos con acceso online (email + contraseña)
        $names = [
            'Carlos Pérez', 'Beatriz Ruiz', 'David Castro', 'Elena Vidal', 'Fernando Gil',
            'Gema Soto', 'Hugo Marín', 'Inés Lozano', 'Javier Núñez', 'Lucía Ramos',
        ];

        foreach ($names as $index => $name) {
            $number = $index + 1;
            $year = 1975 + ($index % 25);
            $month = str_pad((string) (($index % 12) + 1), 2, '0', STR_PAD_LEFT);
            $day = str_pad((string) (($index % 27) + 1), 2, '0', STR_PAD_LEFT);

            $user = User::updateOrCreate(
                ['center_id' => $centerId, 'name' => $name],
                [
                    'email' => "cliente{$number}@demo.test",
                    'phone' => '+3466600'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                    'birth_date' => "{$year}-{$month}-{$day}",
                    'password' => '1234',
                    'registration_source' => 'staff',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles(['cliente']);
        }
    }
}
