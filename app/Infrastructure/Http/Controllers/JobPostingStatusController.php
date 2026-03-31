<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Domain\JobPosting\JobPosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class JobPostingStatusController extends Controller
{
    public function update(Request $request, JobPosting $jobPosting): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:new,bookmarked,submitted,interview,dismissed'],
        ]);

        $user = $request->user();

        $user->jobPostingStatuses()->syncWithoutDetaching([
            $jobPosting->id => ['status' => $request->input('status')],
        ]);

        return back();
    }
}
