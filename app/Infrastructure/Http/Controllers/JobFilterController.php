<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Domain\JobFilter\JobFilter;
use App\Domain\JobPosting\JobPosting;
use App\Infrastructure\Http\Requests\StoreJobFilterRequest;
use App\Infrastructure\Http\Requests\UpdateJobFilterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PlinCode\IstatForeignCountries\Models\ForeignCountries\Continent;
use PlinCode\IstatForeignCountries\Models\ForeignCountries\Country;

class JobFilterController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $globalFilter = $user->globalJobFilter;

        $companyFilters = $user->jobFilters()
            ->whereNotNull('company_id')
            ->with('company')
            ->get();

        $companies = $user->subscribedCompanies()
            ->orderBy('name')
            ->get(['companies.id', 'companies.name']);

        $departments = JobPosting::query()
            ->whereIn('company_id', $user->subscribedCompanies()->pluck('companies.id'))
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

        return Inertia::render('filters', [
            'globalFilter' => $globalFilter,
            'companyFilters' => $companyFilters,
            'companies' => $companies,
            'departments' => $departments,
            'countries' => $countries,
        ]);
    }

    public function store(StoreJobFilterRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $companyId = $validated['company_id'] ?? null;

        $query = $user->jobFilters();

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        } else {
            $query->whereNull('company_id');
        }

        $exists = $query->exists();

        if ($exists) {
            $scope = $companyId === null ? 'global' : 'company';

            return back()->withErrors(['company_id' => "A {$scope} filter already exists."]);
        }

        $user->jobFilters()->create($validated);

        return back()->with('success', 'Filter created.');
    }

    public function update(UpdateJobFilterRequest $request, JobFilter $jobFilter): RedirectResponse
    {
        $jobFilter->update($request->validated());

        return back()->with('success', 'Filter updated.');
    }

    public function destroy(Request $request, JobFilter $jobFilter): RedirectResponse
    {
        $this->authorize('delete', $jobFilter);

        $jobFilter->delete();

        return back()->with('success', 'Filter deleted.');
    }
}
