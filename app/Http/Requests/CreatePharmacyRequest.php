<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePharmacyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $admin = $this->user();

        return $admin && in_array($admin->role, ['admin', 'super_admin'], true);
    }

    public function rules(): array
    {
        return [
            'pharmacy_name' => ['required', 'string', 'max:255'],
            'doctor_name' => ['required', 'string', 'max:255'],
            'doctor_phone' => ['required', 'string', 'max:50', 'unique:pharmacies,doctor_phone'],
            'doctor_email' => ['required', 'email', 'max:255', 'unique:pharmacies,doctor_email'],
            'password' => ['required', 'string', 'min:6'],
            'region_id' => ['required', 'integer', 'exists:regions,id'],
        ];
    }
}
