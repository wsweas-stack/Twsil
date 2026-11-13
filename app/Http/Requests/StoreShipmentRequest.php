<?php

namespace App\Http\Requests;

use App\Enums\UserRoles;
use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRoles::Customer->value) ?? false;
    }

    public function rules(): array
    {
        return [
            'sender_name' => ['required', 'string', 'max:120'],
            'sender_phone' => ['required', 'string', 'max:32'],
            'sender_city' => ['required', 'string', 'max:100'],
            'sender_address' => ['required', 'string', 'max:2000'],
            'sender_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'sender_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'receiver_name' => ['required', 'string', 'max:120'],
            'receiver_phone' => ['required', 'string', 'max:32'],
            'receiver_email' => ['nullable', 'email', 'max:255'],
            'receiver_city' => ['required', 'string', 'max:100'],
            'receiver_address' => ['required', 'string', 'max:2000'],
            'receiver_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'receiver_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'description' => ['required', 'string', 'max:255'],
            'weight_kg' => ['nullable', 'numeric', 'gt:0', 'max:9999'],
            'invoice' => ['nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:10240'],
        ];
    }
}