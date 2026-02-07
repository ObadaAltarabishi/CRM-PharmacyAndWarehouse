<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $admin = $this->user();

        return $admin && in_array($admin->role, ['admin', 'super_admin'], true);
    }

    public function rules(): array
    {
        return [
            'warehouse_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_phone' => ['required', 'string', 'max:50', 'unique:warehouse,owner_phone'],
            'owner_email' => ['required', 'email', 'max:255', 'unique:warehouse,owner_email'],
            'password' => ['required', 'string', 'min:6'],
            'region_id' => ['required', 'integer', 'exists:regions,id'],
        ];
    }
}
