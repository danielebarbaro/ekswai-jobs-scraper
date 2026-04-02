<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Actions\Company\FollowCompanyAction;
use App\Application\Actions\Company\LoadDemoCompaniesAction;
use App\Application\Actions\Company\UnfollowCompanyAction;
use App\Application\Actions\JobPosting\SyncCompanyJobPostingsAction;
use App\Domain\Company\Company;
use App\Domain\Company\JobBoardProvider;
use App\Domain\JobPosting\JobPosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;
use PlinCode\IstatForeignCountries\Models\ForeignCountries\Continent;
use PlinCode\IstatForeignCountries\Models\ForeignCountries\Country;

class CompanySubscriptionController extends Controller
{
    private const int MAX_SYNCS_PER_DAY = 2;

    private const int MIN_SYNC_INTERVAL_MINUTES = 60;

    public function index(Request $request): Response
    {
        $user = $request->user();

        $companies = $user->subscribedCompanies()
            ->withCount('jobPostings')
            ->orderBy('name')
            ->get()
            ->map(function (Company $company) use ($user) {
                $key = "company-sync:{$user->id}:{$company->id}";
                $rateLimitExhausted = RateLimiter::tooManyAttempts($key, self::MAX_SYNCS_PER_DAY);
                $tooRecent = $company->last_synced_at && $company->last_synced_at->diffInMinutes(now()) < self::MIN_SYNC_INTERVAL_MINUTES;

                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'provider' => $company->provider->value,
                    'provider_slug' => $company->provider_slug,
                    'is_active' => $company->is_active,
                    'job_postings_count' => $company->job_postings_count,
                    'email_notifications' => (bool) $company->pivot->email_notifications,
                    'last_synced_at' => $company->last_synced_at?->diffForHumans(),
                    'can_sync' => ! $rateLimitExhausted && ! $tooRecent,
                ];
            });

        $companyFilters = $request->user()->jobFilters()
            ->whereNotNull('company_id')
            ->with('company')
            ->get();

        $subscribedCompanyIds = $request->user()->subscribedCompanies()->pluck('companies.id');

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

        return Inertia::render('companies', [
            'companies' => $companies,
            'companyFilters' => $companyFilters,
            'departments' => $departments,
            'countries' => $countries,
        ]);
    }

    public function follow(Request $request, FollowCompanyAction $action): RedirectResponse
    {
        $request->validate([
            'slug' => ['required', 'string', 'max:500'],
            'provider' => ['nullable', 'string'],
        ]);

        $provider = $request->input('provider')
            ? JobBoardProvider::tryFrom($request->input('provider'))
            : null;

        $action->execute($request->user(), $request->input('slug'), $provider);

        return back()->with('success', 'Company followed successfully.');
    }

    public function unfollow(Request $request, Company $company, UnfollowCompanyAction $action): RedirectResponse
    {
        $action->execute($request->user(), $company);

        return back()->with('success', 'Company unfollowed.');
    }

    public function loadDefaults(Request $request, LoadDemoCompaniesAction $action): RedirectResponse
    {
        $count = $action->execute($request->user());

        if ($count === 0) {
            return back()->with('info', 'All demo companies are already in your list.');
        }

        return back()->with('success', "{$count} demo companies added.");
    }

    public function toggleNotifications(Request $request, Company $company): RedirectResponse
    {
        $pivot = $request->user()->subscribedCompanies()
            ->where('company_id', $company->id)
            ->first();

        if (! $pivot) {
            abort(404);
        }

        $request->user()->subscribedCompanies()->updateExistingPivot($company->id, [
            'email_notifications' => ! $pivot->pivot->email_notifications,
        ]);

        return back()->with('success', 'Notification preference updated.');
    }

    public function sync(Request $request, Company $company, SyncCompanyJobPostingsAction $syncAction): RedirectResponse
    {
        $user = $request->user();

        if (! $user->subscribedCompanies()->where('company_id', $company->id)->exists()) {
            abort(404);
        }

        if ($company->last_synced_at && $company->last_synced_at->diffInMinutes(now()) < self::MIN_SYNC_INTERVAL_MINUTES) {
            return back()->with('error', 'Please wait at least 1 hour between syncs.');
        }

        $key = "company-sync:{$user->id}:{$company->id}";

        if (RateLimiter::tooManyAttempts($key, self::MAX_SYNCS_PER_DAY)) {
            return back()->with('error', 'You can only sync this company twice per day.');
        }

        RateLimiter::hit($key, 86400);

        $newJobs = $syncAction->execute($company);

        if ($newJobs->isNotEmpty()) {
            $pivotData = $newJobs->mapWithKeys(fn ($jp) => [
                $jp->id => ['status' => 'new'],
            ])->toArray();

            $user->jobPostingStatuses()->syncWithoutDetaching($pivotData);
        }

        return back()->with('success', "{$newJobs->count()} new jobs synced for {$company->name}.");
    }
}
