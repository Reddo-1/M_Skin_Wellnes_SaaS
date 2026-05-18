<?php

namespace App\Http\Requests;

use App\Models\CenterFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCenterFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(CenterFile::TYPES)],
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'El tipo de imagen no es válido.',
            'file.image' => 'El archivo debe ser una imagen.',
            'file.max' => 'La imagen no puede pesar más de 5 MB.',
        ];
    }
}
