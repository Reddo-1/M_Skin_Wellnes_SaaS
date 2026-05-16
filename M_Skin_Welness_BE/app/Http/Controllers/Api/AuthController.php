<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
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

            return $user;
        });

        $token = $user->createToken($request->userAgent() ?? 'api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => UserResource::make($user),
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
