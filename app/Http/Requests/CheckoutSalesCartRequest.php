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
            'items' => ['required', 'array', 'min:1'],
            'items.*.barcode' => ['required', 'string', 'max:100'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
