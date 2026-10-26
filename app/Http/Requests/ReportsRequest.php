<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReportsRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', Rule::when($this->filled('from'), ['after_or_equal:from'])],
        ];
    }

    /**
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $filters = $validator->validated();

                if (! empty($filters['from']) && ! empty($filters['to'])
                    && CarbonImmutable::parse($filters['from'])->diffInDays(CarbonImmutable::parse($filters['to'])) > 365) {
                    $validator->errors()->add('to', 'Choose a date range of up to 366 days.');
                }
            },
        ];
    }
}
