<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampanaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:100',
            'cultivo' => 'required|string|max:60',
            'extension' => 'required|numeric|min:0.01',
            'tipo_extension' => 'required|in:topo,hectarea',
            'fecha_inicio' => 'required|date',
            'latitud' => 'nullable|numeric|between:-90,90',
            'longitud' => 'nullable|numeric|between:-180,180',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la campaña es obligatorio.',
            'cultivo.required' => 'El cultivo es obligatorio.',
            'extension.required' => 'La extensión es obligatoria.',
            'extension.min' => 'La extensión debe ser mayor a 0.',
            'tipo_extension.in' => 'El tipo de extensión debe ser "topo" o "hectarea".',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
        ];
    }
}
