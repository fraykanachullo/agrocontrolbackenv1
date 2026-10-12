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
            'precio_unitario' => 'required_without:detalles|numeric|min:0',
            'descuentos_logistica' => 'nullable|numeric|min:0',
            'fecha_liquidacion' => 'required|date',
            'detalles' => 'nullable|array',
            'detalles.*.id' => 'nullable|integer',
            'detalles.*.categoria' => 'nullable|string|max:60',
            'detalles.*.precio_venta' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'precio_unitario.required_without' => 'Registre el precio al menos a nivel global o por detalle.',
            'precio_unitario.min' => 'El precio unitario debe ser mayor a 0.',
            'detalles.*.precio_venta.required' => 'Cada detalle debe tener su precio de venta.',
            'detalles.*.precio_venta.min' => 'El precio de venta debe ser mayor o igual a 0.',
            'fecha_liquidacion.required' => 'La fecha de liquidación es obligatoria.',
        ];
    }
}