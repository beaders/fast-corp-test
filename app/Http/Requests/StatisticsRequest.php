<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StatisticsRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d'],
            'user' => ['nullable', Rule::when($this->input('user') !== 'guest', ['integer', Rule::exists('users', 'id')])],
            'action' => ['nullable', Rule::in(['login', 'logout', 'registration', 'view-page', 'button-click'])],
            'snapshot' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
