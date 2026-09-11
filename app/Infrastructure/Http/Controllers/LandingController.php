<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    private const array SUPPORTED_LOCALES = ['en', 'it'];

    public function redirect(Request $request): RedirectResponse
    {
        $preferred = $request->getPreferredLanguage(self::SUPPORTED_LOCALES) ?? 'en';

        return redirect("/{$preferred}");
    }

    public function show(string $locale): Response
    {
        abort_unless(in_array($locale, self::SUPPORTED_LOCALES, true), 404);

        app()->setLocale($locale);

        return Inertia::render('landing', [
            'locale' => $locale,
            'alternateLocale' => $locale === 'en' ? 'it' : 'en',
            'translations' => __('landing'),
            'baseUrl' => config('app.url'),
            'config' => [
                'repo_url' => config('landing.repo_url'),
                'umami' => config('landing.umami'),
                'job_boards' => [
                    'core' => config('landing.job_boards.core'),
                    'skeleton' => config('landing.job_boards.skeleton'),
                    'connectors' => collect(config('landing.job_boards.connectors'))
                        ->map(fn (string $name, string $slug): array => [
                            'slug' => $slug,
                            'name' => $name,
                            'url' => "https://github.com/plin-code/job-boards-{$slug}",
                        ])
                        ->values()
                        ->all(),
                ],
            ],
        ]);
    }
}
