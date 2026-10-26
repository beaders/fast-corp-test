<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules['name'] = ['required', 'string', 'max:255'];
        $rules['email'][] = Rule::unique(User::class);
        $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];

        return $rules;
    }
}
