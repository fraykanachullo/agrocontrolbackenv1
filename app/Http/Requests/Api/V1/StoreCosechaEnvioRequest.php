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
            'modo_cosecha' => 'nullable|in:matricial_filas,por_categorias,peso_directo',
            'tipo_calculo_venta' => 'nullable|in:por_kilo,por_tonelada,por_unidad',
            'unidad_empaque' => 'nullable|string|max:50',
            'total_bultos' => 'nullable|integer|min:0',
            'peso_total_kg' => 'nullable|numeric|min:0',
            'conductor' => 'nullable|string|max:100',
            'mercado_destino' => 'nullable|string|max:100',
            'fecha_envio' => 'required|date',
            'precio_unitario' => 'nullable|numeric|min:0',
            'descuentos_logistica' => 'nullable|numeric|min:0',
            'detalles' => 'nullable|array',
            'detalles.*.categoria' => 'nullable|string|max:60',
            'detalles.*.simbolo' => 'nullable|string|max:20',
            'detalles.*.posicion_fila' => 'nullable|integer|min:1|max:99',
            'detalles.*.cantidad_bultos' => 'nullable|integer|min:0',
            'detalles.*.peso_promedio_bulto_kg' => 'nullable|numeric|min:0',
            'detalles.*.peso_kg' => 'nullable|numeric|min:0',
            'detalles.*.uuid_local' => 'nullable|string|max:36',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_venta.required' => 'El tipo de venta es obligatorio.',
            'tipo_venta.in' => 'El tipo de venta debe ser "parcela" o "mercado".',
            'cantidad_unidades.required' => 'La cantidad de unidades es obligatoria.',
            'cantidad_unidades.min' => 'La cantidad debe ser mayor a 0.',
            'modo_cosecha.in' => 'El modo de cosecha no es válido.',
            'tipo_calculo_venta.in' => 'El tipo de cálculo de venta no es válido.',
            'fecha_envio.required' => 'La fecha de envío es obligatoria.',
        ];
    }
}