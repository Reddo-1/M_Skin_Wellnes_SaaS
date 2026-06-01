<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Machine;
use App\Models\Treatment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class TreatmentSeeder extends Seeder
{
    public function run(): void
    {
        $centerId = Center::query()->where('slug', 'demo')->value('id');

        if ($centerId === null) {
            return;
        }

        $rfMachineId       = Machine::query()->where('center_id', $centerId)->where('name', 'Radiofrecuencia RF-100')->value('id');
        $laserMachineId    = Machine::query()->where('center_id', $centerId)->where('name', 'Láser diodo LD-200')->value('id');
        $pressureMachineId = Machine::query()->where('center_id', $centerId)->where('name', 'Presoterapia móvil')->value('id');

        $dermoRoleId  = Role::query()->where('name', 'dermo_esteticien')->value('id');
        $fisioRoleId  = Role::query()->where('name', 'fisioterapeuta')->value('id');
        $maniRoleId   = Role::query()->where('name', 'manicurista')->value('id');

        $treatments = [
            [
                'name' => 'Limpieza facial profunda',
                'duration_minutes' => 60,
                'price' => 45.00,
                'machine_ids' => [],
                'role_ids' => [$dermoRoleId],
            ],
            [
                'name' => 'Tratamiento antiedad',
                'duration_minutes' => 75,
                'price' => 65.00,
                'machine_ids' => [],
                'role_ids' => [$dermoRoleId],
            ],
            [
                'name' => 'Radiofrecuencia corporal',
                'duration_minutes' => 60,
                'price' => 80.00,
                'machine_ids' => [$rfMachineId],
                'role_ids' => [$fisioRoleId],
            ],
            [
                'name' => 'Depilación láser piernas',
                'duration_minutes' => 45,
                'price' => 70.00,
                'machine_ids' => [$laserMachineId],
                'role_ids' => [$fisioRoleId],
            ],
            [
                'name' => 'Manicura completa',
                'duration_minutes' => 45,
                'price' => 25.00,
                'machine_ids' => [],
                'role_ids' => [$maniRoleId],
            ],
            [
                'name' => 'Drenaje linfático con presoterapia',
                'duration_minutes' => 50,
                'price' => 55.00,
                'machine_ids' => [$pressureMachineId],
                'role_ids' => [$fisioRoleId],
            ],
        ];

        foreach ($treatments as $t) {
            $treatment = Treatment::updateOrCreate(
                ['center_id' => $centerId, 'name' => $t['name']],
                [
                    'duration_minutes' => $t['duration_minutes'],
                    'price' => $t['price'],
                    'is_active' => true,
                ]
            );

            $machinesPivot = [];
            foreach (array_filter($t['machine_ids']) as $mId) {
                $machinesPivot[$mId] = ['center_id' => $centerId];
            }
            $treatment->machines()->sync($machinesPivot);

            $rolesPivot = [];
            foreach (array_filter($t['role_ids']) as $rId) {
                $rolesPivot[$rId] = ['center_id' => $centerId];
            }
            $treatment->authorizedRoles()->sync($rolesPivot);
        }
    }
}
