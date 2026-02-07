<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListPharmaciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $admin = $this->user();

        return $admin && in_array($admin->role, ['admin', 'super_admin'], true);
    }

    public function rules(): array
    {
        return [];
    }
}
