<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Actions\Company\FollowCompanyAction;
use App\Application\Actions\Company\UnfollowCompanyAction;
use App\Domain\Company\Company;
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
                'workable_account_slug' => $company->workable_account_slug,
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

        $action->execute($request->user(), $request->input('slug'));

        return back()->with('success', 'Company followed successfully.');
    }

    public function unfollow(Request $request, Company $company, UnfollowCompanyAction $action): RedirectResponse
    {
        $action->execute($request->user(), $company);

        return back()->with('success', 'Company unfollowed.');
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
}
