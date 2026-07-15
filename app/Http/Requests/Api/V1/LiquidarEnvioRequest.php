<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class LiquidarEnvioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'precio_unitario' => 'required|numeric|min:0.01',
            'descuentos_logistica' => 'nullable|numeric|min:0',
            'fecha_liquidacion' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'precio_unitario.required' => 'El precio unitario es obligatorio.',
            'precio_unitario.min' => 'El precio unitario debe ser mayor a 0.',
            'fecha_liquidacion.required' => 'La fecha de liquidación es obligatoria.',
        ];
    }
}
