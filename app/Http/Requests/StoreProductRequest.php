<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'barcode' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'strength' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'form' => ['nullable', 'string', 'max:255'],
        ];
    }
}
