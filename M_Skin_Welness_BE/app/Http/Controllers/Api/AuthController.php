<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\{ForgotPasswordRequest, LoginRequest, RegisterRequest, ResetPasswordRequest};
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Hash, Password};
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly UserService $users)
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

        $user->load('latestAvatar');

        return response()->json([
            'token' => $token,
            'user' => UserResource::make($user),
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->users->registerSelfSignup($request->validated());

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
        $user = $request->user()->load(['center.plan', 'latestAvatar']);

        return UserResource::make($user);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        //respuesta generica a proposito: no revelamos si el email existe en BD
        return response()->json([
            'message' => 'Si la cuenta existe, recibirás un correo con instrucciones para recuperar la contraseña.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $payload = ['password' => $password];

                //quien recibe el correo y completa el reset prueba que el email es alcanzable: lo damos por verificado
                if ($user->email_verified_at === null) {
                    $payload['email_verified_at'] = now();
                }

                $user->forceFill($payload)->save();

                //por seguridad, revocamos todos los tokens activos tras un reset
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [trans($status)],
            ]);
        }

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }
}
