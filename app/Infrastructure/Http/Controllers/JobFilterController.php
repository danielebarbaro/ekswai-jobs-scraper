<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Domain\Company\Company;
use App\Domain\JobFilter\JobFilter;
use App\Domain\JobPosting\JobPosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use PlinCode\IstatForeignCountries\Models\ForeignCountries\Continent;
use PlinCode\IstatForeignCountries\Models\ForeignCountries\Country;

class JobFilterController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $globalFilter = JobFilter::query()
            ->where('user_id', $user->id)
            ->global()
            ->first();

        $companyFilters = $user->jobFilters()
            ->whereNotNull('company_id')
            ->with('company')
            ->get();

        $companies = $user->subscribedCompanies()
            ->orderBy('name')
            ->get()
            ->map(fn (Company $company) => [
                'id' => $company->id,
                'name' => $company->name,
            ]);

        $subscribedCompanyIds = $user->subscribedCompanies()->pluck('companies.id');

        $departments = JobPosting::query()
            ->whereIn('company_id', $subscribedCompanyIds)
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $countries = Continent::with(['countries' => fn ($q) => $q->where('type', 'S')->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->map(fn (Continent $continent) => [
                'name' => $continent->name,
                'countries' => $continent->countries->map(fn (Country $country) => [
                    'id' => $country->id,
                    'name' => $country->name,
                ]),
            ]);

        return Inertia::render('filters', [
            'globalFilter' => $globalFilter,
            'companyFilters' => $companyFilters,
            'companies' => $companies,
            'departments' => $departments,
            'countries' => $countries,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'company_id' => [
                'nullable',
                'uuid',
                Rule::exists('companies', 'id'),
            ],
            'title_include' => ['nullable', 'array'],
            'title_include.*' => ['string', 'max:100'],
            'title_exclude' => ['nullable', 'array'],
            'title_exclude.*' => ['string', 'max:100'],
            'country_ids' => ['nullable', 'array'],
            'country_ids.*' => ['uuid'],
            'remote_only' => ['boolean'],
            'department_include' => ['nullable', 'array'],
            'department_include.*' => ['string', 'max:100'],
        ]);

        $companyId = $validated['company_id'] ?? null;

        $existsQuery = JobFilter::query()
            ->where('user_id', $user->id);

        if ($companyId === null) {
            $existsQuery->whereNull('company_id');
        } else {
            $existsQuery->where('company_id', $companyId);
        }

        if ($existsQuery->exists()) {
            $scope = $companyId === null ? 'global' : 'company';

            return back()->withErrors(['company_id' => "A {$scope} filter already exists."]);
        }

        $user->jobFilters()->create($validated);

        return back()->with('success', 'Filter created.');
    }

    public function update(Request $request, JobFilter $jobFilter): RedirectResponse
    {
        if ($jobFilter->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'company_id' => [
                'nullable',
                'uuid',
                Rule::exists('companies', 'id'),
            ],
            'title_include' => ['nullable', 'array'],
            'title_include.*' => ['string', 'max:100'],
            'title_exclude' => ['nullable', 'array'],
            'title_exclude.*' => ['string', 'max:100'],
            'country_ids' => ['nullable', 'array'],
            'country_ids.*' => ['uuid'],
            'remote_only' => ['boolean'],
            'department_include' => ['nullable', 'array'],
            'department_include.*' => ['string', 'max:100'],
        ]);

        $jobFilter->update($validated);

        return back()->with('success', 'Filter updated.');
    }

    public function destroy(Request $request, JobFilter $jobFilter): RedirectResponse
    {
        if ($jobFilter->user_id !== $request->user()->id) {
            abort(403);
        }

        $jobFilter->delete();

        return back()->with('success', 'Filter deleted.');
    }
}
