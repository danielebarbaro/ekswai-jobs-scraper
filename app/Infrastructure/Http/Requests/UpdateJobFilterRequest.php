<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use App\Domain\JobFilter\JobFilter;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJobFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $jobFilter = $this->route('jobFilter');

        return $jobFilter instanceof JobFilter && $jobFilter->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
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
