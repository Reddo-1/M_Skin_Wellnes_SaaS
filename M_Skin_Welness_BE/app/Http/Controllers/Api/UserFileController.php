<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserFileRequest;
use App\Http\Resources\UserFileResource;
use App\Models\UserFile;
use App\Services\UserFileService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserFileController extends Controller
{
    //inyecta el service de archivos
    public function __construct(private readonly UserFileService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', UserFile::class);

        $centerId = (int) $request->attributes->get('center_id');

        $query = UserFile::query()
            //solo archivos del centro actual
            ->forCenter($centerId)
            //filtros opcionales
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('skin_evaluation_id'), fn ($q) => $q->where('skin_evaluation_id', $request->integer('skin_evaluation_id')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->orderByDesc('id');

        return UserFileResource::collection($query->paginate(50));
    }

    public function store(StoreUserFileRequest $request): UserFileResource
    {
        $file = $this->service->upload(
            centerId: (int) $request->attributes->get('center_id'),
            data: $request->validated(),
            file: $request->file('file'),
        );

        return UserFileResource::make($file);
    }

    public function show(UserFile $userFile): UserFileResource
    {
        $this->authorize('view', $userFile);

        return UserFileResource::make($userFile);
    }

    public function destroy(UserFile $userFile): JsonResponse
    {
        $this->authorize('delete', $userFile);

        $this->service->delete($userFile);

        return response()->json(status: 204);
    }

    public function file(UserFile $userFile): BinaryFileResponse
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($userFile->path)) {
            throw new NotFoundHttpException('El archivo ya no está disponible.');
        }

        return response()->file($disk->path($userFile->path));
    }
}
