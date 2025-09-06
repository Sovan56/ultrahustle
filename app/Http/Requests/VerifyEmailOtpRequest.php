<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailOtpRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array {
        return [
            'new_email' => ['required', 'email'],
            'code'      => ['required', 'digits:6'],
        ];
    }
}
