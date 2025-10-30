<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceOrderRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'partner_id'     => ['required','integer','exists:users,id','different:auth_id'],
            'subcategory_id' => ['required','integer'],
            'product_id'     => ['nullable','integer','exists:products,id'],
            'terms'          => ['nullable','string','max:20000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['auth_id' => auth()->id()]);
    }
}
