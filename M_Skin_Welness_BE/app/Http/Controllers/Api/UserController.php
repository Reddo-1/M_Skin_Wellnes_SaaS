<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\{ActivateOnlineAccessRequest, ChangeUserPasswordRequest, StoreUserRequest, SyncUserRolesRequest, UpdateUserRequest};
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(private readonly UserService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $centerId = (int) $request->attributes->get('center_id');
        $actor = $request->user();
        //quien solo tiene clients.view (profesionales) no puede listar staff: forzamos role=cliente ignorando el query param
        $restrictToClient = !$actor->can('users.view') && $actor->can('clients.view');

        $query = User::query()
            ->forCenter($centerId)
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when(
                $restrictToClient,
                fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', 'cliente')),
                fn ($q) => $q->when($request->filled('role'), fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $request->string('role')))),
            )
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($qq) use ($term) {
                    $qq->where('name', 'ilike', $term)
                       ->orWhere('email', 'ilike', $term);
                });
            })
            ->orderBy('name');

        return UserResource::collection($query->paginate(10));
    }

    public function store(StoreUserRequest $request): UserResource
    {
        $user = $this->service->create(
            centerId: (int) $request->attributes->get('center_id'),
            actorUserId: (int) $request->user()->id,
            data: $request->validated(),
        );

        return UserResource::make($user);
    }

    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return UserResource::make($user->load('latestAvatar'));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $user = $this->service->update($user, $request->validated());

        return UserResource::make($user);
    }

    //baja lógica: deja el usuario inactivo pero conserva el histórico
    public function destroy(Request $request, User $user): UserResource
    {
        $this->authorize('deactivate', $user);

        return UserResource::make($this->service->deactivate($user, (int) $request->user()->id));
    }

    public function activate(Request $request, User $user): UserResource
    {
        $this->authorize('activate', $user);

        return UserResource::make($this->service->activate($user, (int) $request->user()->id));
    }

    //el propio usuario puede cambiar la suya y el staff con permiso la de otros
    public function changePassword(ChangeUserPasswordRequest $request, User $user): JsonResponse
    {
        $this->service->changePassword($user, (string) $request->validated('password'));

        return response()->json(status: 204);
    }

    //reemplaza la lista de roles: un usuario puede tener varios a la vez
    public function syncRoles(SyncUserRolesRequest $request, User $user): UserResource
    {
        return UserResource::make(
            $this->service->syncRoles($user, $request->validated('role_ids'))
        );
    }

    //manda correo al cliente para que fije contrasena y, al hacerlo, quede como verificado
    public function activateOnlineAccess(ActivateOnlineAccessRequest $request, User $user): UserResource
    {
        return UserResource::make(
            $this->service->activateOnlineAccess($user, $request->validated('email'))
        );
    }
}
