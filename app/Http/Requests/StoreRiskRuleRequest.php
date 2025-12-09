<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRiskRuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:DURATION,VOLUME,OPEN_TRADES',
            'severity' => 'required|in:HARD,SOFT',
            'is_active' => 'boolean',

            // Parámetros condicionales según el tipo
            'min_duration_seconds' => 'required_if:type,DURATION|integer|min:1',
            'min_factor' => 'required_if:type,VOLUME|numeric|min:0',
            'max_factor' => 'required_if:type,VOLUME|numeric|min:0|gt:min_factor',
            'lookback_trades' => 'required_if:type,VOLUME|integer|min:1',
            'time_window_minutes' => 'required_if:type,OPEN_TRADES|integer|min:1',
            'min_open_trades' => 'nullable|integer|min:0',
            'max_open_trades' => 'nullable|integer|min:1|gt:min_open_trades',
            'incidents_before_action' => 'required_if:severity,SOFT|integer|min:1',
        ];
    }

    /**
     * Opcional: Mensajes de error personalizados
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la regla es obligatorio',
            'type.required' => 'El tipo de regla es obligatorio',
            'type.in' => 'El tipo de regla debe ser DURATION, VOLUME o OPEN_TRADES',
            'severity.required' => 'La severidad es obligatoria',
            'severity.in' => 'La severidad debe ser HARD o SOFT',
            'min_duration_seconds.required_if' => 'La duración mínima es obligatoria para reglas de tipo DURATION',
            'min_factor.required_if' => 'El factor mínimo es obligatorio para reglas de tipo VOLUME',
            'max_factor.required_if' => 'El factor máximo es obligatorio para reglas de tipo VOLUME',
            'max_factor.gt' => 'El factor máximo debe ser mayor al factor mínimo',
            'lookback_trades.required_if' => 'El número de trades históricos es obligatorio para reglas de tipo VOLUME',
            'time_window_minutes.required_if' => 'La ventana de tiempo es obligatoria para reglas de tipo OPEN_TRADES',
            'max_open_trades.gt' => 'El máximo de trades debe ser mayor al mínimo',
            'incidents_before_action.required_if' => 'El número de incidentes antes de acción es obligatorio para reglas de severidad SOFT',
        ];
    }
}
