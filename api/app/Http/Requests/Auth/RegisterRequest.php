<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'organization_name'     => ['required', 'string', 'max:255'],
            'organization_currency' => ['nullable', 'string', 'size:3'],
            'organization_timezone' => ['nullable', 'string', 'max:100'],
            'device_name'           => ['nullable', 'string', 'max:255'],
        ];
    }
}
