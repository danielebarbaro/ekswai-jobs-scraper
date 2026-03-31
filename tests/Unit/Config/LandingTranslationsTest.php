<?php

declare(strict_types=1);

it('has english translations with all required keys', function () {
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
    expect(__('landing.features.workable.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.features.admin.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.features.opensource.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.footer.opensource_by'))->toBeString()->not->toContain('landing.');
});

it('has italian translations with all required keys', function () {
    app()->setLocale('it');

    expect(__('landing.meta.title'))->toBeString()->not->toContain('landing.');
    expect(__('landing.hero.headline'))->toBeString()->not->toContain('landing.');
    expect(__('landing.hero.cta'))->toBeString()->not->toContain('landing.');
});

it('has different content for each locale', function () {
    app()->setLocale('en');
    $enTitle = __('landing.hero.headline');

    app()->setLocale('it');
    $itTitle = __('landing.hero.headline');

    expect($enTitle)->not->toBe($itTitle);
});
