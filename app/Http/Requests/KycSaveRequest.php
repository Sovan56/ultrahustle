<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KycSaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize inputs before validation so users don’t get
     * spurious errors (spaces, casing, etc).
     */
    protected function prepareForValidation(): void
    {
        $legal = trim((string) $this->input('legal_name'));
        $addr  = trim((string) $this->input('address'));
        $type  = trim((string) $this->input('id_type'));

        $idNum = (string) $this->input('id_number');
        // Strip spaces/dashes, uppercase for PAN/Passport
        $idNum_norm = preg_replace('/[\s-]+/', '', $idNum);
        if (strcasecmp($type, 'PAN') === 0 || strcasecmp($type, 'Passport') === 0) {
            $idNum_norm = strtoupper($idNum_norm);
        }

        // Aadhaar often pasted with spaces — remove them
        if (strcasecmp($type, 'Aadhaar') === 0) {
            $idNum_norm = preg_replace('/\s+/', '', $idNum_norm);
        }

        // Coerce date to Y-m-d if browser sends something odd
        $dob = $this->input('dob');
        if (is_string($dob)) {
            $dob = trim($dob);
        }

        $this->merge([
            'legal_name' => $legal,
            'address'    => $addr,
            'id_type'    => $type,
            'id_number'  => $idNum_norm,
            'dob'        => $dob,
        ]);
    }

    public function rules(): array
    {
        $idType = Rule::in(['Aadhaar', 'PAN', 'Passport', 'Other']);

        return [
            'legal_name' => ['bail','required','string','max:255'],
            'dob'        => ['bail','required','date','before:today'],
            'address'    => ['bail','required','string','max:2000'],
            'id_type'    => ['bail','required',$idType],

            // Flexible number check based on type
            'id_number'  => [
                'bail','required','string','max:50',
                function ($attr, $value, $fail) {
                    $type = (string) $this->input('id_type');
                    if ($type === 'Aadhaar' && !preg_match('/^\d{12}$/', $value)) {
                        return $fail('Aadhaar must be exactly 12 digits.');
                    }
                    if ($type === 'PAN' && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $value)) {
                        return $fail('PAN format is invalid (e.g., ABCDE1234F).');
                    }
                    if ($type === 'Passport' && !preg_match('/^[A-Z0-9]{7,9}$/', $value)) {
                        return $fail('Passport number seems invalid (7–9 alphanumeric).');
                    }
                }
            ],

            // Accept scans as PDF for ID docs, but selfie must be an image
            'id_front'   => ['bail','required','file','mimes:jpg,jpeg,png,webp,pdf','max:4096'],
            'id_back'    => ['bail','required','file','mimes:jpg,jpeg,png,webp,pdf','max:4096'],
            'selfie'     => ['bail','required','image','mimes:jpg,jpeg,png,webp','max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'dob.before'           => 'DOB must be a past date.',
            'id_front.required'    => 'Please upload the front of your ID.',
            'id_back.required'     => 'Please upload the back of your ID.',
            'selfie.required'      => 'Please upload a selfie.',
            'id_front.mimes'       => 'ID front must be JPG, PNG, WEBP, or PDF.',
            'id_back.mimes'        => 'ID back must be JPG, PNG, WEBP, or PDF.',
            'selfie.mimes'         => 'Selfie must be JPG, PNG, or WEBP.',
            'id_front.max'         => 'ID front must be ≤ 4MB.',
            'id_back.max'          => 'ID back must be ≤ 4MB.',
            'selfie.max'           => 'Selfie must be ≤ 4MB.',
        ];
    }
}
