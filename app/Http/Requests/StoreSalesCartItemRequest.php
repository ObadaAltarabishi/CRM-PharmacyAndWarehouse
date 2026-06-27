<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'barcode' => ['required_without:product_id', 'prohibits:product_id', 'string', 'max:100'],
            'product_id' => ['required_without:barcode', 'prohibits:barcode', 'integer', 'exists:products,id'],
        ];
    }
}
