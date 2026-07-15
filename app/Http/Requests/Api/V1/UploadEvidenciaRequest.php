<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UploadEvidenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'archivo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'relacion_id' => 'required|integer',
            'relacion_tipo' => 'required|in:transaccion,actividad',
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'El archivo es obligatorio.',
            'archivo.image' => 'El archivo debe ser una imagen.',
            'archivo.mimes' => 'El formato debe ser jpeg, png, jpg, gif o webp.',
            'archivo.max' => 'La imagen no debe superar 10MB.',
            'relacion_id.required' => 'El ID de relación es obligatorio.',
            'relacion_tipo.required' => 'El tipo de relación es obligatorio.',
            'relacion_tipo.in' => 'El tipo debe ser "transaccion" o "actividad".',
        ];
    }
}
