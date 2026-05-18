<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCenterFileRequest;
use App\Http\Resources\CenterFileResource;
use App\Models\CenterFile;
use App\Services\CenterFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CenterFileController extends Controller
{
    public function __construct(private readonly CenterFileService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CenterFile::class);

        $centerId = (int) $request->attributes->get('center_id');

        $query = CenterFile::query()
            ->forCenter($centerId)
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->orderBy('type');

        return CenterFileResource::collection($query->get());
    }

    public function show(CenterFile $centerFile): CenterFileResource
    {
        $this->authorize('view', $centerFile);

        return CenterFileResource::make($centerFile);
    }

    public function store(StoreCenterFileRequest $request): CenterFileResource
    {
        $this->authorize('create', CenterFile::class);

        $centerId = (int) $request->attributes->get('center_id');

        $file = $this->service->upload(
            centerId: $centerId,
            type: $request->validated('type'),
            file: $request->file('file'),
        );

        return CenterFileResource::make($file);
    }

    public function destroy(CenterFile $centerFile): JsonResponse
    {
        $this->authorize('delete', $centerFile);

        $this->service->delete($centerFile);

        return response()->json(status: 204);
    }
}
