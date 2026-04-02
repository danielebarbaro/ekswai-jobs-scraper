<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'uuid', Rule::exists('companies', 'id')],
            'title_include' => ['nullable', 'array'],
            'title_include.*' => ['string', 'max:100'],
            'title_exclude' => ['nullable', 'array'],
            'title_exclude.*' => ['string', 'max:100'],
            'country_ids' => ['nullable', 'array'],
            'country_ids.*' => ['uuid'],
            'remote_only' => ['boolean'],
            'department_include' => ['nullable', 'array'],
            'department_include.*' => ['string', 'max:100'],
        ];
    }
}
