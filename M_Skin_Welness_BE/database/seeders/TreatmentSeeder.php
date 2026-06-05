<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Machine;
use App\Models\Treatment;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class TreatmentSeeder extends Seeder
{
    public function run(): void
    {
        $centerId = Center::query()->where('slug', 'demo')->value('id');

        if ($centerId === null) {
            return;
        }

        $indibaMachineId = Machine::query()->where('center_id', $centerId)->where('name', 'Indiba')->value('id');
        $laserMachineId  = Machine::query()->where('center_id', $centerId)->where('name', 'Láser')->value('id');

        $dermoRoleId = Role::query()->where('name', 'dermo_esteticien')->value('id');
        $fisioRoleId = Role::query()->where('name', 'fisioterapeuta')->value('id');

        $treatments = [
            [
                'name' => 'Indiba facial',
                'duration_minutes' => 60,
                'price' => 65.00,
                'machine_ids' => [$indibaMachineId],
                'role_ids' => [$dermoRoleId],
            ],
            [
                'name' => 'Masaje relajante',
                'duration_minutes' => 50,
                'price' => 40.00,
                'machine_ids' => [],
                'role_ids' => [$fisioRoleId],
            ],
            [
                'name' => 'Depilación láser',
                'duration_minutes' => 45,
                'price' => 70.00,
                'machine_ids' => [$laserMachineId],
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
