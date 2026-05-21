<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('superadmin') ?? false;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'current_password' => ['nullable', 'required_with:password', 'current_password:web'],
            'password' => ['nullable', 'confirmed', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Indica un correo válido.',
            'email.unique' => 'Ese correo ya está en uso por otro usuario.',
            'current_password.required_with' => 'Para cambiar la contraseña necesitas indicar la actual.',
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.confirmed' => 'La nueva contraseña y su confirmación no coinciden.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
        ];
    }
}
