<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRoles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(10)],
            'password_confirmation' => ['required', 'string', 'same:password'],
            'role' => ['required', Rule::in([
                UserRoles::Customer->value,
                UserRoles::Driver->value,
                UserRoles::Employee->value,
            ])],
            'phone' => ['required', 'string', 'max:32'],
            'national_id' => ['required_if:role,driver,employee', 'nullable', 'string', 'max:64'],
            'city' => ['required_if:role,driver,employee', 'nullable', 'string', 'max:100'],
            'vehicle_type' => ['required_if:role,driver', 'nullable', 'string', 'max:64'],
            'license_number' => ['required_if:role,driver', 'nullable', 'string', 'max:64'],
        ];
    }
}