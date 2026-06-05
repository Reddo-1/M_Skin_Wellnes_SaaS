<?php

namespace App\Http\Requests;

use App\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'admin.name' => ['required', 'string', 'max:120'],
            'admin.email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')],
            'admin.password' => ['required', 'string', 'min:8', 'max:120', 'confirmed'],
            'admin.password_confirmation' => ['required', 'string'],

            'center.name' => ['required', 'string', 'max:120'],
            'center.slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/', Rule::unique('centers', 'slug')],

            'plan_code' => [
                'required',
                'string',
                Rule::exists('plans', 'code')->where(function ($query) {
                    $query->where('is_active', true)->whereNotNull('stripe_price_id');
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'admin.email.unique' => 'Ya existe una cuenta con ese correo.',
            'center.slug.regex' => 'El identificador solo puede contener letras minúsculas, números y guiones.',
            'center.slug.unique' => 'Ese identificador de centro ya está en uso.',
            'plan_code.exists' => 'El plan elegido no está disponible.',
        ];
    }

    public function plan(): Plan
    {
        return Plan::query()->where('code', $this->input('plan_code'))->firstOrFail();
    }
}
