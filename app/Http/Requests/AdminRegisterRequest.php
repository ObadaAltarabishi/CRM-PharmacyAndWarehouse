<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'phone' => ['required', 'string', 'max:50', 'unique:admins,phone'],
            'password' => ['required', 'string', 'min:6'],
            'region_id' => ['required', 'integer', 'exists:regions,id'],

            // ما بدنا نسمح يجي role من الفرونت/بوستمان بهالمرحلة
            // إذا بدك تسمح فقط للسوبر أدمن لاحقًا، منعملها ب endpoint منفصل
        ];
    }
}
