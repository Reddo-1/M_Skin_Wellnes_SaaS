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
            ]
        );
        $superadmin->syncRoles(['superadmin']);

        $centerId = Center::query()->where('slug', 'demo')->value('id');

        if ($centerId === null) {
            return;
        }

        $staff = [
            ['admin@demo.test',      'Admin Demo',          'administrador'],
            ['recepcion@demo.test',  'Lucía Recepción',     'recepcionista'],
            ['rrhh@demo.test',       'Marta RRHH',          'rrhh'],
            ['diagno@demo.test',     'Pablo Diagnóstico',   'diagnosticador'],
            ['dermo@demo.test',      'Carla Dermo',         'dermo_esteticien'],
            ['fisio@demo.test',      'Iván Fisio',          'fisioterapeuta'],
            ['mani@demo.test',       'Sofía Manicura',      'manicurista'],
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
                ]
            );
            $user->syncRoles([$role]);
        }

        $clients = [
            ['cliente1@demo.test', 'Ana López',     '+34611111111', '1990-04-12'],
            ['cliente2@demo.test', 'Carlos Pérez',  '+34622222222', '1985-08-21'],
            ['cliente3@demo.test', 'Beatriz Ruiz',  '+34633333333', '1995-01-30'],
            ['cliente4@demo.test', 'David Castro',  '+34644444444', '1978-11-09'],
            ['cliente5@demo.test', 'Elena Vidal',   '+34655555555', '2000-06-15'],
            ['cliente6@demo.test', 'Fernando Gil',  '+34666666660', '1992-03-25'],
            ['cliente7@demo.test', 'Gema Soto',     '+34677777777', '1988-09-04'],
            ['cliente8@demo.test', 'Hugo Marín',    '+34688888888', '1997-12-18'],
        ];

        foreach ($clients as [$email, $name, $phone, $birthDate]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'center_id' => $centerId,
                    'name' => $name,
                    'phone' => $phone,
                    'birth_date' => $birthDate,
                    'password' => '1234',
                    'registration_source' => 'staff',
                    'is_active' => true,
                ]
            );
            $user->syncRoles(['cliente']);
        }
    }
}
