<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

class StoreMilestoneSubmissionRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'note' => ['nullable','string','max:2000'],
            'file' => ['required','file','max:5120'], // 5MB
            'url'  => ['nullable','url','max:512'],
        ];
    }
}
