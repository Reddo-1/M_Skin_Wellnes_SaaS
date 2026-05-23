<?php

namespace App\Services;

use App\Models\{AbsenceType, PaymentMethod, SaleStatus, SessionStatus, SkinType, StockMovementType, Variation};
use Illuminate\Database\Eloquent\{Collection, Model};
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\{Rule, ValidationException};

class AdminLookupService
{
    public function catalogs(): array
    {
        return [
            'session_statuses' => ['label' => 'Estados de sesión', 'model' => SessionStatus::class],
            'absence_types' => ['label' => 'Tipos de ausencia', 'model' => AbsenceType::class],
            'payment_methods' => ['label' => 'Métodos de pago', 'model' => PaymentMethod::class],
            'sale_statuses' => ['label' => 'Estados de venta', 'model' => SaleStatus::class],
            'stock_movement_types' => ['label' => 'Tipos de movimiento de stock', 'model' => StockMovementType::class],
            'skin_types' => ['label' => 'Tipos de piel', 'model' => SkinType::class],
            'variations' => ['label' => 'Variaciones', 'model' => Variation::class],
        ];
    }

    public function resolve(string $catalog): array
    {
        $catalogs = $this->catalogs();

        if (! isset($catalogs[$catalog])) {
            throw ValidationException::withMessages([
                'catalog' => ['El catálogo solicitado no existe.'],
            ]);
        }

        $modelClass = $catalogs[$catalog]['model'];
        $instance = new $modelClass;

        return [
            'key' => $catalog,
            'label' => $catalogs[$catalog]['label'],
            'model' => $modelClass,
            'table' => $instance->getTable(),
            'has_sort_order' => Schema::hasColumn($instance->getTable(), 'sort_order'),
        ];
    }

    public function items(string $catalog): Collection
    {
        $info = $this->resolve($catalog);
        $query = $info['model']::query();

        if ($info['has_sort_order']) {
            $query->orderBy('sort_order')->orderBy('id');
        } else {
            $query->orderBy('id');
        }

        return $query->get();
    }

    public function rules(string $catalog, ?int $ignoreId = null): array
    {
        $info = $this->resolve($catalog);

        $rules = [
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique($info['table'], 'name')->ignore($ignoreId),
            ],
        ];

        if ($info['has_sort_order']) {
            $rules['sort_order'] = ['nullable', 'integer', 'min:0'];
        }

        return $rules;
    }

    public function create(string $catalog, array $data): Model
    {
        $info = $this->resolve($catalog);
        return $info['model']::create($data);
    }

    public function update(string $catalog, int $id, array $data): Model
    {
        $info = $this->resolve($catalog);
        $item = $info['model']::findOrFail($id);
        $item->fill($data)->save();
        return $item;
    }

    public function delete(string $catalog, int $id): void
    {
        $info = $this->resolve($catalog);
        $item = $info['model']::findOrFail($id);

        try {
            $item->delete();
        } catch (QueryException $e) {
            throw ValidationException::withMessages([
                'delete' => ['No puedes borrar este registro porque hay datos del sistema que lo están usando.'],
            ]);
        }
    }
}
