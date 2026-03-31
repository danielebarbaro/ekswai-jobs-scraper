<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $status = $request->query('status');
        $companyId = $request->query('company');

        $query = $user->jobPostingStatuses()
            ->with('company:id,name,workable_account_slug')
            ->orderByPivot('updated_at', 'desc');

        if ($status && $status !== 'all') {
            $query->wherePivot('status', $status);
        } else {
            // Hide dismissed by default
            $query->wherePivot('status', '!=', 'dismissed');
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $jobPostings = $query->paginate(20)->through(fn ($jp) => [
                'id' => $jp->id,
                'title' => $jp->title,
                'location' => $jp->location,
                'department' => $jp->department,
                'url' => $jp->url,
                'first_seen_at' => $jp->first_seen_at->toDateString(),
                'status' => $jp->pivot->status,
                'company' => [
                    'id' => $jp->company->id,
                    'name' => $jp->company->name,
                ],
            ]);

        $companies = $user->subscribedCompanies()
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]);

        return Inertia::render('dashboard', [
            'jobPostings' => $jobPostings,
            'companies' => $companies,
            'filters' => [
                'status' => $status ?? 'all',
                'company' => $companyId,
            ],
        ]);
    }
}
