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
            //el FE manda directamente el id del estado seleccionado
            'status_id' => ['required', 'integer', 'exists:session_statuses,id'],
        ];
    }
}
