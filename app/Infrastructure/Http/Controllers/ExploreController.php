<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Services\JobFilterService;
use App\Domain\Company\Company;
use App\Domain\JobFilter\JobFilter;
use App\Domain\JobPosting\JobPosting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PlinCode\IstatForeignCountries\Models\ForeignCountries\Continent;
use PlinCode\IstatForeignCountries\Models\ForeignCountries\Country;

class ExploreController extends Controller
{
    public function __construct(
        private readonly JobFilterService $jobFilterService,
    ) {}

    public function index(): Response
    {
        $departments = JobPosting::query()
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $countries = Continent::with(['countries' => fn ($q) => $q->where('type', 'S')->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->map(fn (Continent $continent): array => [
                'name' => $continent->name,
                'countries' => $continent->countries->map(fn (Country $country): array => [
                    'id' => $country->id,
                    'name' => $country->name,
                ]),
            ]);

        return Inertia::render('explore', [
            'departments' => $departments,
            'countries' => $countries,
        ]);
    }

    public function companies(Request $request): JsonResponse
    {
        $user = $request->user();
        $followedIds = $user->subscribedCompanies()->pluck('companies.id')->toArray();

        $titleInclude = $request->input('title_include', []);
        $titleExclude = $request->input('title_exclude', []);
        $countryIds = $request->input('country_ids', $request->input('countries', []));
        $remoteOnly = filter_var($request->input('remote_only', false), FILTER_VALIDATE_BOOLEAN);
        $departmentInclude = $request->input('department_include', $request->input('departments', []));

        $filter = new JobFilter;
        $filter->title_include = empty($titleInclude) ? null : $titleInclude;
        $filter->title_exclude = empty($titleExclude) ? null : $titleExclude;
        $filter->country_ids = empty($countryIds) ? null : $countryIds;
        $filter->remote_only = $remoteOnly;
        $filter->department_include = empty($departmentInclude) ? null : $departmentInclude;

        $companies = Company::query()
            ->where('is_active', true)
            ->withCount(['jobPostings as matched_jobs_count' => function (Builder|BelongsToMany $query) use ($filter): void {
                $this->jobFilterService->applyToQuery($query, $filter);
            }])
            ->get()
            ->filter(fn (Company $company): bool => (int) $company->matched_jobs_count > 0)
            ->sortByDesc('matched_jobs_count')
            ->values()
            ->map(fn (Company $company): array => [
                'id' => $company->id,
                'name' => $company->name,
                'description' => $company->description,
                'provider' => $company->provider->value,
                'provider_slug' => $company->provider_slug,
                'matched_jobs_count' => (int) $company->matched_jobs_count,
                'is_already_followed' => in_array($company->id, $followedIds, true),
            ]);

        return response()->json(['data' => $companies]);
    }

    public function followMany(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_ids' => ['required', 'array', 'min:1'],
            'company_ids.*' => ['required', 'string', 'exists:companies,id'],
            'filters' => ['required', 'array'],
            'filters.title_include' => ['nullable', 'array'],
            'filters.title_exclude' => ['nullable', 'array'],
            'filters.countries' => ['nullable', 'array'],
            'filters.remote_only' => ['required', 'boolean'],
            'filters.departments' => ['nullable', 'array'],
        ]);

        $user = $request->user();
        $filters = $validated['filters'];
        $alreadyFollowed = $user->subscribedCompanies()->pluck('companies.id')->toArray();

        $newCompanyIds = array_diff($validated['company_ids'], $alreadyFollowed);

        foreach ($newCompanyIds as $companyId) {
            $user->subscribedCompanies()->attach($companyId, ['email_notifications' => true]);

            $jobIds = JobPosting::query()
                ->where('company_id', $companyId)
                ->pluck('id');

            if ($jobIds->isNotEmpty()) {
                $pivotData = $jobIds->mapWithKeys(fn ($id): array => [
                    $id => ['status' => 'new'],
                ])->toArray();

                $user->jobPostingStatuses()->syncWithoutDetaching($pivotData);
            }
        }

        $globalFilter = $user->globalJobFilter;
        $filterData = [
            'title_include' => $filters['title_include'] ?? null,
            'title_exclude' => $filters['title_exclude'] ?? null,
            'country_ids' => $filters['countries'] ?? null,
            'remote_only' => $filters['remote_only'],
            'department_include' => $filters['departments'] ?? null,
        ];

        if ($globalFilter) {
            $globalFilter->update($filterData);
        } else {
            JobFilter::query()->create([
                'user_id' => $user->id,
                'company_id' => null,
                ...$filterData,
            ]);
        }

        $user->update(['has_completed_onboarding' => true]);

        return response()->json(['ok' => true]);
    }

    public function skipOnboarding(Request $request): JsonResponse
    {
        $request->user()->update(['has_completed_onboarding' => true]);

        return response()->json(['ok' => true]);
    }
}
