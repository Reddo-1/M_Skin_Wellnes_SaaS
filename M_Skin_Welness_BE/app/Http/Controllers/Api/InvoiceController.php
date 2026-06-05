<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InvoiceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Invoice::class);

        $centerId = (int) $request->attributes->get('center_id');

        $query = Invoice::query()
            ->forCenter($centerId)
            ->with(['client', 'issuer'])
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->integer('client_id')))
            ->when($request->filled('sale_id'), fn ($q) => $q->where('sale_id', $request->integer('sale_id')))
            ->when($request->filled('from'), fn ($q) => $q->where('issued_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('issued_date', '<=', $request->date('to')))
            ->orderByDesc('id');

        return InvoiceResource::collection($query->paginate(10));
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        $this->authorize('view', $invoice);

        return InvoiceResource::make($invoice->load(['client', 'issuer']));
    }
}
