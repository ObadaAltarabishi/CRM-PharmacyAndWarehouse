<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        $admin = $this->user();

        return $admin && $admin->role === 'super_admin';
    }

    public function rules(): array
    {
        return [];
    }
}
