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
            ['recepcionista@gmail.com',  'Sheila Recepción',  'recepcionista'],
            ['rrhh@gmail.com',           'María RRHH',        'rrhh'],
            ['diagnosticador@gmail.com', 'Marc Díaz',         'diagnosticador'],
            ['dermo@gmail.com',          'Raquel Soler',      'dermo_esteticien'],
            ['fisio@gmail.com',          'Iván Bravo',        'fisioterapeuta'],
            ['manicura@gmail.com',       'Mari Manicura',     'manicurista'],
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

        //20 clientes: los 16 primeros con acceso online (email + contraseña), los 4 ultimos walk-in (sin email)
        $names = [
            'Carlos Pérez', 'Beatriz Ruiz', 'David Castro', 'Elena Vidal', 'Fernando Gil',
            'Gema Soto', 'Hugo Marín', 'Inés Lozano', 'Javier Núñez', 'Lucía Ramos',
            'Manuel Ortega', 'Nuria Cano', 'Óscar Prieto', 'Paula Serrano', 'Rosa Ibáñez',
            'Sergio Vega', 'Teresa Lara', 'Víctor Peña', 'Yolanda Cruz', 'Andrés Bello',
        ];

        foreach ($names as $index => $name) {
            $number = $index + 1;
            $isWalkIn = $index >= 16;
            $year = 1975 + ($index % 25);
            $month = str_pad((string) (($index % 12) + 1), 2, '0', STR_PAD_LEFT);
            $day = str_pad((string) (($index % 27) + 1), 2, '0', STR_PAD_LEFT);

            //se clava por (center_id, name): los walk-in no tienen email y varios null no podrian distinguirse
            $user = User::updateOrCreate(
                ['center_id' => $centerId, 'name' => $name],
                [
                    'email' => $isWalkIn ? null : "cliente{$number}@demo.test",
                    'phone' => '+3466600'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                    'birth_date' => "{$year}-{$month}-{$day}",
                    'password' => $isWalkIn ? null : '1234',
                    'registration_source' => 'staff',
                    'is_active' => true,
                    'email_verified_at' => $isWalkIn ? null : now(),
                ]
            );
            $user->syncRoles(['cliente']);
        }
    }
}
