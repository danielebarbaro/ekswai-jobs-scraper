<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\DTOs\JobPostingDTO;
use App\Domain\Company\Company;
use App\Domain\JobFilter\JobFilter;
use App\Domain\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use PlinCode\IstatForeignCountries\Models\ForeignCountries\Country;

class JobFilterService
{
    public function getEffectiveFilter(User $user, Company $company): ?JobFilter
    {
        $companyFilter = JobFilter::query()
            ->where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->first();

        if ($companyFilter instanceof JobFilter) {
            return $companyFilter;
        }

        $globalFilter = JobFilter::query()
            ->where('user_id', $user->id)
            ->whereNull('company_id')
            ->first();

        return $globalFilter instanceof JobFilter ? $globalFilter : null;
    }

    /**
     * @param  Collection<int, JobPostingDTO>  $jobs
     * @return Collection<int, JobPostingDTO>
     */
    public function apply(Collection $jobs, ?JobFilter $filter): Collection
    {
        if ($filter === null) {
            return $jobs;
        }

        $filtered = $jobs;

        if (! empty($filter->title_include)) {
            $keywords = $filter->title_include;
            $filtered = $filtered->filter(
                fn (JobPostingDTO $job) => $this->matchesAnyKeyword($job->title, $keywords)
            );
        }

        if (! empty($filter->title_exclude)) {
            $keywords = $filter->title_exclude;
            $filtered = $filtered->reject(
                fn (JobPostingDTO $job) => $this->matchesAnyKeyword($job->title, $keywords)
            );
        }

        if (! empty($filter->country_ids)) {
            $countryPatterns = $this->resolveCountryPatterns($filter->country_ids);
            $filtered = $filtered->filter(
                fn (JobPostingDTO $job) => $this->matchesCountry($job, $countryPatterns)
            );
        }

        if ($filter->remote_only) {
            $filtered = $filtered->filter(
                fn (JobPostingDTO $job) => $this->isRemote($job)
            );
        }

        if (! empty($filter->department_include)) {
            $departments = array_map('mb_strtolower', $filter->department_include);
            $filtered = $filtered->filter(
                fn (JobPostingDTO $job) => $job->department === null
                    || in_array(mb_strtolower($job->department), $departments, true)
            );
        }

        return $filtered->values();
    }

    /**
     * Uses LOWER() + LIKE for cross-database compatibility (PostgreSQL + SQLite).
     */
    public function applyToQuery(Builder $query, ?JobFilter $filter): Builder
    {
        if ($filter === null) {
            return $query;
        }

        if (! empty($filter->title_include)) {
            $query->where(function (Builder $q) use ($filter) {
                foreach ($filter->title_include as $keyword) {
                    $q->orWhereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower($keyword).'%']);
                }
            });
        }

        if (! empty($filter->title_exclude)) {
            foreach ($filter->title_exclude as $keyword) {
                $query->whereRaw('LOWER(title) NOT LIKE ?', ['%'.mb_strtolower($keyword).'%']);
            }
        }

        if (! empty($filter->country_ids)) {
            $countryPatterns = $this->resolveCountryPatterns($filter->country_ids);
            $query->where(function (Builder $q) use ($countryPatterns) {
                $q->whereNull('location');
                foreach ($countryPatterns as $pattern) {
                    $q->orWhereRaw('LOWER(location) LIKE ?', ['%'.mb_strtolower($pattern).'%']);
                }
            });
        }

        if ($filter->remote_only) {
            $query->where(function (Builder $q) {
                $q->whereRaw('LOWER(location) LIKE ?', ['%remote%'])
                    ->orWhere('raw_payload->isRemote', true)
                    ->orWhere('raw_payload->is_remote', true);
            });
        }

        if (! empty($filter->department_include)) {
            $query->where(function (Builder $q) use ($filter) {
                $q->whereNull('department');
                foreach ($filter->department_include as $dept) {
                    $q->orWhereRaw('LOWER(department) = ?', [mb_strtolower($dept)]);
                }
            });
        }

        return $query;
    }

    private function matchesAnyKeyword(string $text, array $keywords): bool
    {
        $lowerText = mb_strtolower($text);

        foreach ($keywords as $keyword) {
            if (str_contains($lowerText, mb_strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string>  $countryIds
     * @return array<string>
     */
    private function resolveCountryPatterns(array $countryIds): array
    {
        return Cache::remember(
            'job_filter_countries_'.md5(implode(',', $countryIds)),
            now()->addHour(),
            function () use ($countryIds) {
                $countries = Country::whereIn('id', $countryIds)->get();
                $patterns = [];

                foreach ($countries as $country) {
                    $patterns[] = $country->name;
                    $patterns[] = $country->iso_alpha2;
                }

                return $patterns;
            }
        );
    }

    private function matchesCountry(JobPostingDTO $job, array $countryPatterns): bool
    {
        if ($job->location === null) {
            return true;
        }

        $lowerLocation = mb_strtolower($job->location);

        foreach ($countryPatterns as $pattern) {
            $lowerPattern = mb_strtolower($pattern);

            if (preg_match('/\b'.preg_quote($lowerPattern, '/').'\b/i', $lowerLocation)) {
                return true;
            }
        }

        $rawCountry = $job->rawPayload['country'] ?? null;
        if ($rawCountry !== null) {
            foreach ($countryPatterns as $pattern) {
                if (mb_strtolower($rawCountry) === mb_strtolower($pattern)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isRemote(JobPostingDTO $job): bool
    {
        if ($job->location !== null && str_contains(mb_strtolower($job->location), 'remote')) {
            return true;
        }

        return ($job->rawPayload['isRemote'] ?? false) === true
            || ($job->rawPayload['is_remote'] ?? false) === true;
    }
}
