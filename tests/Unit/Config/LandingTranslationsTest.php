<?php

declare(strict_types=1);

it('has english translations with all required keys', function (): void {
    app()->setLocale('en');

    expect(__('landing.meta.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.meta.description'))->toBeString()->not->toContain('landing.');
    expect(__('landing.hero.headline'))->toBeString()->not->toContain('landing.');
    expect(__('landing.hero.subtitle'))->toBeString()->not->toContain('landing.');
    expect(__('landing.hero.cta'))->toBeString()->not->toContain('landing.');
    expect(__('landing.steps.1.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.steps.2.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.steps.3.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.features.notifications.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.features.providers.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.features.pipeline.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.preview_heading'))->toBeString()->not->toContain('landing.');
    expect(__('landing.preview.companies.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.preview.dashboard.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.features.opensource.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.connectors.heading'))->toBeString()->not->toContain('landing.');
    expect(__('landing.connectors.badge'))->toBeString()->not->toContain('landing.');
    expect(__('landing.connectors.core.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.connectors.skeleton.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.connectors.scrapers_note'))->toBeString()->not->toContain('landing.');
    expect(__('landing.connectors.cta'))->toBeString()->not->toContain('landing.');
    expect(__('landing.footer.opensource_by'))->toBeString()->not->toContain('landing.');
});

it('has italian translations with all required keys', function (): void {
    app()->setLocale('it');

    expect(__('landing.meta.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.meta.description'))->toBeString()->not->toContain('landing.');
    expect(__('landing.hero.headline'))->toBeString()->not->toContain('landing.');
    expect(__('landing.hero.subtitle'))->toBeString()->not->toContain('landing.');
    expect(__('landing.hero.cta'))->toBeString()->not->toContain('landing.');
    expect(__('landing.steps.1.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.steps.2.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.steps.3.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.features.notifications.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.features.providers.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.features.pipeline.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.preview_heading'))->toBeString()->not->toContain('landing.');
    expect(__('landing.preview.companies.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.preview.dashboard.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.features.opensource.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.connectors.heading'))->toBeString()->not->toContain('landing.');
    expect(__('landing.connectors.badge'))->toBeString()->not->toContain('landing.');
    expect(__('landing.connectors.core.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.connectors.skeleton.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.connectors.scrapers_note'))->toBeString()->not->toContain('landing.');
    expect(__('landing.connectors.cta'))->toBeString()->not->toContain('landing.');
    expect(__('landing.footer.opensource_by'))->toBeString()->not->toContain('landing.');
});

it('has different content for each locale', function (): void {
    app()->setLocale('en');
    $enTitle = __('landing.hero.headline');

    app()->setLocale('it');
    $itTitle = __('landing.hero.headline');

    expect($enTitle)->not->toBe($itTitle);
});

it('no longer resolves the old extensibility translation key', function (): void {
    app()->setLocale('en');

    expect(__('landing.extensibility.heading'))->toBe('landing.extensibility.heading');
});
