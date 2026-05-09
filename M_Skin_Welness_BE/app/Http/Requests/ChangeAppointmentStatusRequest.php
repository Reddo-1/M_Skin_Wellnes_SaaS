<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeAppointmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('changeStatus', $this->route('appointment'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'exists:session_statuses,name'],
        ];
    }
}
