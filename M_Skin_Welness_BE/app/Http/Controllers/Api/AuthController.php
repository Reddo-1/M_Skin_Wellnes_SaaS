<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\{ForgotPasswordRequest, LoginRequest, RegisterRequest, ResetPasswordRequest};
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{DB, Hash, Password};
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogs)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::query()->where('email', $data['email'])->first();

        if ($user === null || !Hash::check($data['password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['La cuenta no existe o se encuentra desactivada.'],
            ]);
        }

        if (!$user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['Verifica tu correo para poder acceder.'],
            ]);
        }

        $token = $user->createToken($request->userAgent() ?? 'api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => UserResource::make($user),
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'center_id' => $data['center_id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'password' => $data['password'],
                'registration_source' => 'online',
                'is_active' => true,
            ]);

            $user->assignRole('cliente');

            $this->auditLogs->record(
                action: 'user.created',
                centerId: $user->center_id,
                metadata: ['user_id' => $user->id, 'role' => 'cliente', 'source' => 'self_registration'],
            );

            return $user;
        });

        event(new Registered($user));

        return response()->json([
            'message' => 'Cuenta creada. Revisa tu correo para verificarla antes de iniciar sesión.',
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(status: 204);
    }

    public function me(Request $request): UserResource
    {
        $user = $request->user()->load('center.plan');

        return UserResource::make($user);
    }

    //envia el correo de recuperacion con un link que apunta al FE
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        //respuesta generica a proposito: no revelamos si el email existe en BD
        return response()->json([
            'message' => 'Si la cuenta existe, recibiras un correo con instrucciones para recuperar la contrasena.',
        ]);
    }

    //cambia la contrasena usando el token recibido por correo
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                ])->save();

                //por seguridad, revocamos todos los tokens activos tras un reset
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [trans($status)],
            ]);
        }

        return response()->json(['message' => 'Contrasena actualizada correctamente.']);
    }
}
