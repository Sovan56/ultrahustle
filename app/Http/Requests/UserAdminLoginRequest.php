<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class UserAdminLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
        }
        // Keep route-specific session values fresh so modals have what they need
        if ($this->routeIs('password.otp.verify')) {
            session(['emailForReset' => $this->input('email')]);
        }
        if ($this->routeIs('password.reset')) {
            session([
                'emailForReset' => $this->input('email'),
                'otpForReset'   => $this->input('code'),
            ]);
        }
    }

    public function rules(): array
    {
        // Pick rules by route name
        if ($this->routeIs('auth.register')) {
            return [
                'first_name'      => ['required', 'string', 'max:100'],
                'last_name'       => ['required', 'string', 'max:100'],
                'phone_number'    => ['nullable', 'string', 'max:25'],
                'email'           => ['required', 'email', 'max:255', 'unique:users,email'],
                'password'        => ['required', 'string', 'min:8', 'confirmed'],
                'password_confirmation' => ['required', 'same:password'],
            ];
        }

        if ($this->routeIs('auth.login')) {
            return [
                'email'    => ['required', 'email'],
                'password' => ['required', 'string'],
            ];
        }

        if ($this->routeIs('password.forgot.send')) {
            return [
                'email' => ['required', 'email', 'exists:users,email'],
            ];
        }

        if ($this->routeIs('password.otp.verify')) {
            return [
                'email' => ['required', 'email'],
                'code'  => ['required', 'digits:6'],
            ];
        }

        if ($this->routeIs('password.reset')) {
            return [
                'email'                  => ['required', 'email', 'exists:users,email'],
                'code'                   => ['required', 'digits:6'],
                'password'               => ['required', 'string', 'min:8', 'confirmed'],
                'password_confirmation'  => ['required', 'same:password'],
            ];
        }

        return [];
    }

    public function messages(): array
    {
        return [
            'email.exists'   => 'We couldn’t find an account with that email.',
            'code.digits'    => 'Your 6-digit code must be exactly 6 numbers.',
            'password.min'   => 'Your new password must be at least :min characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }

    /**
     * Ensure the correct modal opens again when validation fails.
     */
    protected function failedValidation(Validator $validator)
    {
        // default modal
        $openModal = null;

        if ($this->routeIs('auth.register')) {
            $openModal = 'register';
        } elseif ($this->routeIs('auth.login')) {
            $openModal = 'login';
        } elseif ($this->routeIs('password.forgot.send')) {
            $openModal = 'forgotPassword';
        } elseif ($this->routeIs('password.otp.verify')) {
            $openModal = 'verifyOtp';
        } elseif ($this->routeIs('password.reset')) {
            $openModal = 'resetPassword';
        }

        if ($openModal) {
            session()->flash('openModal', $openModal);
        }

        // Also keep these around so the modals have data
        if ($this->routeIs('password.otp.verify')) {
            session()->flash('emailForReset', $this->input('email'));
        }
        if ($this->routeIs('password.reset')) {
            session()->flash('emailForReset', $this->input('email'));
            session()->flash('otpForReset', $this->input('code'));
        }

        throw (new ValidationException($validator))
            ->redirectTo(url()->previous());
    }
}
