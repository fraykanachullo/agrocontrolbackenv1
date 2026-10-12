<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransaccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => 'required|in:ingreso,egreso',
            'categoria' => 'nullable|string|max:60',
            'etapa' => 'nullable|string|max:30',
            'monto' => 'required|numeric|min:0.01',
            'descripcion' => 'nullable|string|max:255',
            'fecha' => 'required|date',
            'estado_pago' => 'required|in:pagado,por_cobrar,por_pagar',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'El tipo de transacción es obligatorio.',
            'tipo.in' => 'El tipo debe ser "ingreso" o "egreso".',
            'monto.required' => 'El monto es obligatorio.',
            'monto.min' => 'El monto debe ser mayor a 0.',
            'fecha.required' => 'La fecha es obligatoria.',
            'estado_pago.required' => 'El estado de pago es obligatorio.',
            'estado_pago.in' => 'El estado de pago debe ser "pagado", "por_cobrar" o "por_pagar".',
        ];
    }
}
