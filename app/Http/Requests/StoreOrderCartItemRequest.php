<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouse,id'],
            'barcode' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
