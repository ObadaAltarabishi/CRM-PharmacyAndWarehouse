<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DeletePharmacyRequest extends FormRequest
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

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Only super admins can delete pharmacies.',
        ], 403));
    }
}
