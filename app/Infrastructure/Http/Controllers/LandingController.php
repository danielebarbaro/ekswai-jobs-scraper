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
            ],
        ]);
    }
}
