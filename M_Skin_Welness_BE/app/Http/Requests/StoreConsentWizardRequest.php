<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsentWizardRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();

        //el superadmin impersonando no firma consents: su center_id es null y el reviewer del consent quedaria huerfano
        if ($actor->hasRole('superadmin')) {
            return false;
        }

        return $actor->can('client_consents.create')
            && $actor->can('treatment_consents.create');
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');

        return [
            //destinatario del consent: el cliente debe pertenecer al centro
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('center_id', $centerId),
            ],

            'rgpd' => ['required', 'array'],
            'rgpd.clinical_photos_consent' => ['required', 'boolean'],
            'rgpd.marketing_data_consent' => ['required', 'boolean'],
            'rgpd.commercial_images_consent' => ['required', 'boolean'],

            'treatments' => ['required', 'array', 'min:1'],
            'treatments.*.treatment_id' => [
                'required',
                'integer',
                Rule::exists('treatments', 'id')->where('center_id', $centerId),
            ],
            'treatments.*.is_suitable' => ['required', 'boolean'],
            'treatments.*.unsuitability_reason' => ['nullable', 'string', 'max:150'],
            'treatments.*.treatment_consent' => ['required', 'boolean'],
            'treatments.*.notes' => ['nullable', 'string', 'max:2000'],

            //la firma viene como dataURL base64 PNG generado por angular-signature-pad
            'signature_base64' => ['required', 'string'],

            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
