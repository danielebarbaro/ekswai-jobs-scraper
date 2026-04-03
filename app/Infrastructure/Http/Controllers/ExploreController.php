<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Services\JobFilterService;
use App\Domain\Company\Company;
use App\Domain\JobFilter\JobFilter;
use App\Domain\JobPosting\JobPosting;
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
        $countryIds = $request->input('country_ids', []);
        $remoteOnly = filter_var($request->input('remote_only', false), FILTER_VALIDATE_BOOLEAN);
        $departmentInclude = $request->input('department_include', []);

        $filter = new JobFilter;
        $filter->title_include = ! empty($titleInclude) ? $titleInclude : null;
        $filter->title_exclude = ! empty($titleExclude) ? $titleExclude : null;
        $filter->country_ids = ! empty($countryIds) ? $countryIds : null;
        $filter->remote_only = $remoteOnly;
        $filter->department_include = ! empty($departmentInclude) ? $departmentInclude : null;

        $companies = Company::query()
            ->where('is_active', true)
            ->withCount(['jobPostings as matched_jobs_count' => function ($query) use ($filter): void {
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
            'filters.country_ids' => ['nullable', 'array'],
            'filters.remote_only' => ['required', 'boolean'],
            'filters.department_include' => ['nullable', 'array'],
        ]);

        $user = $request->user();
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

        $filters = $validated['filters'];
        $globalFilter = $user->globalJobFilter;

        if ($globalFilter) {
            $globalFilter->update([
                'title_include' => $filters['title_include'] ?? null,
                'title_exclude' => $filters['title_exclude'] ?? null,
                'country_ids' => $filters['country_ids'] ?? null,
                'remote_only' => $filters['remote_only'],
                'department_include' => $filters['department_include'] ?? null,
            ]);
        } else {
            JobFilter::query()->create([
                'user_id' => $user->id,
                'company_id' => null,
                'title_include' => $filters['title_include'] ?? null,
                'title_exclude' => $filters['title_exclude'] ?? null,
                'country_ids' => $filters['country_ids'] ?? null,
                'remote_only' => $filters['remote_only'],
                'department_include' => $filters['department_include'] ?? null,
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
