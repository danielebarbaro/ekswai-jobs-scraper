<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Actions\Company\FollowCompanyAction;
use App\Application\Actions\Company\LoadDemoCompaniesAction;
use App\Application\Actions\Company\UnfollowCompanyAction;
use App\Application\Actions\JobPosting\SyncCompanyJobPostingsAction;
use App\Domain\Company\Company;
use App\Domain\Company\JobBoardProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanySubscriptionController extends Controller
{
    public function index(Request $request): Response
    {
        $companies = $request->user()->subscribedCompanies()
            ->withCount('jobPostings')
            ->orderBy('name')
            ->get()
            ->map(fn (Company $company) => [
                'id' => $company->id,
                'name' => $company->name,
                'provider' => $company->provider->value,
                'provider_slug' => $company->provider_slug,
                'is_active' => $company->is_active,
                'job_postings_count' => $company->job_postings_count,
                'email_notifications' => (bool) $company->pivot->email_notifications,
            ]);

        return Inertia::render('companies', [
            'companies' => $companies,
        ]);
    }

    public function follow(Request $request, FollowCompanyAction $action): RedirectResponse
    {
        $request->validate([
            'slug' => ['required', 'string', 'max:255'],
        ]);

        $provider = JobBoardProvider::from($request->input('provider', 'workable'));

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
