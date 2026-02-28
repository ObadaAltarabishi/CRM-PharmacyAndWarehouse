<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalesInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'paid_total' => ['nullable', 'numeric', 'min:0'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
