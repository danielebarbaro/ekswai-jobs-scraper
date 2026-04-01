<?php

declare(strict_types=1);

it('has english app translations with all required keys', function () {
    app()->setLocale('en');

    expect(__('app.common.save'))->toBeString()->not->toContain('app.');
    expect(__('app.nav.dashboard'))->toBeString()->not->toContain('app.');
    expect(__('app.dashboard.title'))->toBeString()->not->toContain('app.');
    expect(__('app.companies.title'))->toBeString()->not->toContain('app.');
    expect(__('app.settings.title'))->toBeString()->not->toContain('app.');
    expect(__('app.settings.profile.heading'))->toBeString()->not->toContain('app.');
    expect(__('app.settings.password.heading'))->toBeString()->not->toContain('app.');
    expect(__('app.settings.two_factor.heading'))->toBeString()->not->toContain('app.');
    expect(__('app.settings.appearance.heading'))->toBeString()->not->toContain('app.');
    expect(__('app.settings.delete_account.heading'))->toBeString()->not->toContain('app.');
});

it('has italian app translations with all required keys', function () {
    app()->setLocale('it');

    expect(__('app.common.save'))->toBeString()->not->toContain('app.');
    expect(__('app.nav.dashboard'))->toBeString()->not->toContain('app.');
    expect(__('app.dashboard.title'))->toBeString()->not->toContain('app.');
    expect(__('app.companies.title'))->toBeString()->not->toContain('app.');
    expect(__('app.settings.title'))->toBeString()->not->toContain('app.');
    expect(__('app.settings.profile.heading'))->toBeString()->not->toContain('app.');
    expect(__('app.settings.password.heading'))->toBeString()->not->toContain('app.');
    expect(__('app.settings.two_factor.heading'))->toBeString()->not->toContain('app.');
    expect(__('app.settings.appearance.heading'))->toBeString()->not->toContain('app.');
    expect(__('app.settings.delete_account.heading'))->toBeString()->not->toContain('app.');
});

it('has english email translations', function () {
    app()->setLocale('en');

    expect(__('emails.title'))->toBeString()->not->toContain('emails.');
    expect(__('emails.greeting'))->toBeString()->not->toContain('emails.');
    expect(__('emails.summary'))->toBeString()->not->toContain('emails.');
    expect(__('emails.view_apply'))->toBeString()->not->toContain('emails.');
    expect(__('emails.footer'))->toBeString()->not->toContain('emails.');
});

it('has italian email translations', function () {
    app()->setLocale('it');

    expect(__('emails.title'))->toBeString()->not->toContain('emails.');
    expect(__('emails.greeting'))->toBeString()->not->toContain('emails.');
    expect(__('emails.summary'))->toBeString()->not->toContain('emails.');
    expect(__('emails.view_apply'))->toBeString()->not->toContain('emails.');
    expect(__('emails.footer'))->toBeString()->not->toContain('emails.');
});

it('has different app translations per locale', function () {
    app()->setLocale('en');
    $enSave = __('app.common.save');

    app()->setLocale('it');
    $itSave = __('app.common.save');

    expect($enSave)->not->toBe($itSave);
});
