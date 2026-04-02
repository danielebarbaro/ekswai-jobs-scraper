<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\DTOs\JobPostingDTO;
use App\Domain\Company\Company;
use App\Domain\JobFilter\JobFilter;
use App\Domain\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use PlinCode\IstatForeignCountries\Models\ForeignCountries\Country;

class JobFilterService
{
    public function getEffectiveFilter(User $user, Company $company): ?JobFilter
    {
        return JobFilter::query()
            ->where('user_id', $user->id)
            ->where(fn (Builder $q) => $q
                ->where('company_id', $company->id)
                ->orWhereNull('company_id')
            )
            ->orderByRaw('company_id IS NULL ASC')
            ->first();
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
                fn (JobPostingDTO $job): bool => $this->matchesAnyKeyword($job->title, $keywords)
            );
        }

        if (! empty($filter->title_exclude)) {
            $keywords = $filter->title_exclude;
            $filtered = $filtered->reject(
                fn (JobPostingDTO $job): bool => $this->matchesAnyKeyword($job->title, $keywords)
            );
        }

        if (! empty($filter->country_ids)) {
            $countryPatterns = $this->resolveCountryPatterns($filter->country_ids);
            $filtered = $filtered->filter(
                fn (JobPostingDTO $job): bool => $this->matchesCountry($job, $countryPatterns)
            );
        }

        if ($filter->remote_only) {
            $filtered = $filtered->filter(
                fn (JobPostingDTO $job): bool => $this->isRemote($job)
            );
        }

        if (! empty($filter->department_include)) {
            $departments = array_map(mb_strtolower(...), $filter->department_include);
            $filtered = $filtered->filter(
                fn (JobPostingDTO $job): bool => $job->department === null
                    || in_array(mb_strtolower($job->department), $departments, true)
            );
        }

        return $filtered->values();
    }

    /**
     * Uses LOWER() + LIKE for cross-database compatibility (PostgreSQL + SQLite).
     */
    public function applyToQuery(Builder|BelongsToMany $query, ?JobFilter $filter): Builder|BelongsToMany
    {
        if ($filter === null) {
            return $query;
        }

        if (! empty($filter->title_include)) {
            $query->where(function (Builder $q) use ($filter): void {
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
            $query->where(function (Builder $q) use ($countryPatterns): void {
                $q->whereNull('location');
                foreach ($countryPatterns as $pattern) {
                    $q->orWhereRaw('LOWER(location) LIKE ?', ['%'.mb_strtolower($pattern).'%']);
                }
            });
        }

        if ($filter->remote_only) {
            $query->where(function (Builder $q): void {
                $q->whereRaw('LOWER(location) LIKE ?', ['%remote%'])
                    ->orWhere('raw_payload->isRemote', true)
                    ->orWhere('raw_payload->is_remote', true);
            });
        }

        if (! empty($filter->department_include)) {
            $query->where(function (Builder $q) use ($filter): void {
                $q->whereNull('department');
                foreach ($filter->department_include as $dept) {
                    $q->orWhereRaw('LOWER(department) = ?', [mb_strtolower($dept)]);
                }
            });
        }

        return $query;
    }

    /**
     * @param  array<string>  $keywords
     */
    private function matchesAnyKeyword(string $text, array $keywords): bool
    {
        $lowerText = mb_strtolower($text);

        return array_any($keywords, fn ($keyword): bool => str_contains($lowerText, mb_strtolower((string) $keyword)));
    }

    /**
     * @param  array<string>  $countryIds
     * @return array<string>
     */
    private function resolveCountryPatterns(array $countryIds): array
    {
        $sorted = $countryIds;
        sort($sorted);

        return Cache::remember(
            'job_filter_countries_'.md5(implode(',', $sorted)),
            now()->addHour(),
            function () use ($sorted): array {
                $countries = Country::query()->whereIn('id', $sorted)->get();
                $patterns = [];

                foreach ($countries as $country) {
                    $patterns[] = $country->name;
                    $patterns[] = $country->iso_alpha2;
                }

                return $patterns;
            }
        );
    }

    /**
     * @param  array<string>  $countryPatterns
     */
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
