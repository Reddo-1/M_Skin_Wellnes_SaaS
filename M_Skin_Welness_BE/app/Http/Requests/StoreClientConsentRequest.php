<?php

namespace App\Http\Requests;

use App\Models\ClientConsent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ClientConsent::class);
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');

        return [
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where('center_id', $centerId),
            ],
            'clinical_photos_consent' => ['required', 'boolean'],
            'marketing_data_consent' => ['required', 'boolean'],
            'commercial_images_consent' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'signature_file' => ['required', 'file', 'image', 'max:5120'],
        ];
    }
}
