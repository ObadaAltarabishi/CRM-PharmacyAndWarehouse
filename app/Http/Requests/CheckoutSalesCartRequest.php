<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutSalesCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'paid_total' => ['required', 'numeric', 'min:0'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
