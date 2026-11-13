<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    /**
     * On validation failure, return the visitor to the page the form was
     * submitted from (e.g. home#contact or the standalone contact page)
     * so the error alert and old input appear next to the form.
     */
    protected function getRedirectUrl(): string
    {
        $previous = preg_replace('/#.*$/', '', (string) $this->headers->get('referer')) ?: route('contact');

        return $previous . '#contact';
    }
}
