<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // app/Http/Requests/ChangePasswordRequest.php
  // app/Http/Requests/ChangePasswordRequest.php
public function rules(): array {
  return [
    'old_password'     => ['required', 'current_password'], // uses default 'web' guard
    'new_password'     => ['required', 'string', 'min:8', 'regex:/\d/', 'different:old_password'],
    'confirm_password' => ['required', 'same:new_password'],
  ];
}



    public function messages(): array
    {
        return [
            'new_password.regex' => 'Password must contain at least one number.',
        ];
    }
}
