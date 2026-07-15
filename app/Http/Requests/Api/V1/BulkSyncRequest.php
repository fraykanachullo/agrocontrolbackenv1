<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class BulkSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'registros' => 'required|array|min:1|max:500',
            'registros.*.uuid_local' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'registros.required' => 'El array de registros es obligatorio.',
            'registros.min' => 'Debe enviar al menos 1 registro.',
            'registros.max' => 'No puede enviar más de 500 registros por lote.',
            'registros.*.uuid_local.required' => 'Cada registro debe tener un uuid_local.',
        ];
    }
}
