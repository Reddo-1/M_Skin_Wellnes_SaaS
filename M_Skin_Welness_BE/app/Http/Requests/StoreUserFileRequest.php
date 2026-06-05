<?php

namespace App\Http\Requests;

use App\Models\UserFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\{Rule, Validator};

class StoreUserFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', UserFile::class);
    }

    public function rules(): array
    {
        $centerId = (int) $this->attributes->get('center_id');
        $allCategories = array_merge(UserFile::CATEGORIES_CLINICAL, [UserFile::CATEGORY_AVATAR]);

        return [
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where('center_id', $centerId),
            ],
            'category' => ['required', Rule::in($allCategories)],
            //skin_evaluation_id obligatorio para fotos clinicas, prohibido para foto_perfil
            'skin_evaluation_id' => [
                'nullable',
                Rule::exists('skin_evaluations', 'id')->where('center_id', $centerId),
            ],
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $category = $this->input('category');
            $evalId = $this->input('skin_evaluation_id');
            $targetUserId = (int) $this->input('user_id');
            $actor = $this->user();

            //avatar va sin evaluacion; las fotos clinicas la exigen
            if ($category === UserFile::CATEGORY_AVATAR) {
                if ($evalId !== null) {
                    $v->errors()->add('skin_evaluation_id', 'La foto de perfil no se vincula a una evaluación.');
                }

                //subir avatar de otro usuario requiere permiso clinico
                if ($targetUserId !== $actor->id && !$actor->can('user_files.upload')) {
                    $v->errors()->add('user_id', 'Solo puedes subir tu propia foto de perfil.');
                }

                return;
            }

            if ($evalId === null) {
                $v->errors()->add('skin_evaluation_id', 'Las fotos clínicas deben ir vinculadas a una evaluación.');
            }

            if (!$actor->can('user_files.upload')) {
                $v->errors()->add('category', 'No tienes permiso para subir fotos clínicas.');
            }
        });
    }
}
