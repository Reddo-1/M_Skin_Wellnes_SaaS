<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeUserPasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\SyncUserRolesRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    //inyecta el service de usuarios
    public function __construct(private readonly UserService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $centerId = (int) $request->attributes->get('center_id');

        $query = User::query()
            //solo usuarios del centro actual
            ->forCenter($centerId)
            //filtro opcional por activos/inactivos
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            //filtro opcional por rol concreto (ej: ?role=cliente)
            ->when($request->filled('role'), fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $request->string('role'))))
            //busqueda libre por nombre o email
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($qq) use ($term) {
                    $qq->where('name', 'ilike', $term)
                       ->orWhere('email', 'ilike', $term);
                });
            })
            ->orderBy('name');

        return UserResource::collection($query->paginate(50));
    }

    public function store(StoreUserRequest $request): UserResource
    {
        $user = $this->service->create(
            centerId: (int) $request->attributes->get('center_id'),
            data: $request->validated(),
        );

        return UserResource::make($user);
    }

    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return UserResource::make($user);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $user = $this->service->update($user, $request->validated());

        return UserResource::make($user);
    }

    //baja lógica: deja el usuario inactivo pero conserva el histórico
    public function destroy(User $user): UserResource
    {
        $this->authorize('deactivate', $user);

        return UserResource::make($this->service->deactivate($user));
    }

    //reactiva un usuario previamente desactivado
    public function activate(User $user): UserResource
    {
        $this->authorize('activate', $user);

        return UserResource::make($this->service->activate($user));
    }

    //cambia la contraseña; el propio usuario puede cambiar la suya y el staff con permiso la de otros
    public function changePassword(ChangeUserPasswordRequest $request, User $user): JsonResponse
    {
        $this->service->changePassword($user, (string) $request->validated('password'));

        return response()->json(status: 204);
    }

    //reemplaza la lista de roles del usuario (un usuario puede tener varios a la vez)
    public function syncRoles(SyncUserRolesRequest $request, User $user): UserResource
    {
        return UserResource::make(
            $this->service->syncRoles($user, $request->validated('role_ids'))
        );
    }
}
