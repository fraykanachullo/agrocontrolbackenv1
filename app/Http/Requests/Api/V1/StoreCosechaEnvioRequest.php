<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCosechaEnvioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_venta' => 'required|in:parcela,mercado',
            'cantidad_unidades' => 'required|numeric|min:0.01',
            'conductor' => 'nullable|string|max:100',
            'mercado_destino' => 'nullable|string|max:100',
            'fecha_envio' => 'required|date',
            'precio_unitario' => 'nullable|numeric|min:0',
            'descuentos_logistica' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_venta.required' => 'El tipo de venta es obligatorio.',
            'tipo_venta.in' => 'El tipo de venta debe ser "parcela" o "mercado".',
            'cantidad_unidades.required' => 'La cantidad de unidades es obligatoria.',
            'cantidad_unidades.min' => 'La cantidad debe ser mayor a 0.',
            'fecha_envio.required' => 'La fecha de envío es obligatoria.',
        ];
    }
}
