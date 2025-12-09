<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => [
                'required',
                'integer',
                'unique:accounts,login',
                'min:1000000',
                'max:99999999'
            ],
            'trading_status' => [
                'required',
                Rule::in(['enable', 'disable'])
            ],
            'status' => [
                'required',
                Rule::in(['enable', 'disable'])
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'login.required' => 'El login es obligatorio',
            'login.unique' => 'El login ya existe',
            'login.min' => 'El login debe tener al menos 7 dígitos',
            'login.max' => 'El login no puede tener más de 8 dígitos',
        ];
    }
}
