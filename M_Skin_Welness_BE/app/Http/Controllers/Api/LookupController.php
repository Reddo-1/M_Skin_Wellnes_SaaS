<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbsenceType;
use App\Models\PaymentMethod;
use App\Models\SaleStatus;
use App\Models\SessionStatus;
use App\Models\SkinType;
use App\Models\StockMovementType;
use App\Models\Variation;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class LookupController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'session_statuses' => SessionStatus::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'absence_types' => AbsenceType::query()->orderBy('name')->get(['id', 'name']),
            'payment_methods' => PaymentMethod::query()->orderBy('name')->get(['id', 'name']),
            'sale_statuses' => SaleStatus::query()->orderBy('name')->get(['id', 'name']),
            'stock_movement_types' => StockMovementType::query()->orderBy('name')->get(['id', 'name']),
            'skin_types' => SkinType::query()->orderBy('name')->get(['id', 'name']),
            'variations' => Variation::query()->orderBy('name')->get(['id', 'name']),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
