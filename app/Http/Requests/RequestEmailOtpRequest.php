<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestEmailOtpRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array {
        return [
            'new_email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ];
    }
}
