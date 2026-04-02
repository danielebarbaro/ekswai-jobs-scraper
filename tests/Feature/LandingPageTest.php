<?php

declare(strict_types=1);

it('redirects root to locale based on accept-language', function (): void {
    $this->get('/', ['Accept-Language' => 'it-IT,it;q=0.9,en;q=0.8'])
        ->assertRedirect('/it');
});

it('redirects root to en by default', function (): void {
    $this->get('/')
        ->assertRedirect('/en');
});

it('renders landing page for en locale', function (): void {
    $this->get('/en')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('landing')
            ->has('translations')
            ->has('config')
            ->where('locale', 'en')
            ->where('alternateLocale', 'it')
        );
});

it('renders landing page for it locale', function (): void {
    $this->get('/it')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('landing')
            ->where('locale', 'it')
            ->where('alternateLocale', 'en')
        );
});

it('returns 404 for invalid locale', function (): void {
    $this->get('/fr')
        ->assertNotFound();
});

it('passes landing config to the page', function (): void {
    $this->get('/en')
        ->assertInertia(fn ($page) => $page
            ->has('config.repo_url')
            ->has('config.umami')
        );
});

it('passes translations to the page', function (): void {
    $this->get('/en')
        ->assertInertia(fn ($page) => $page
            ->has('translations.hero.headline')
            ->has('translations.meta.title')
            ->has('translations.steps')
            ->has('translations.features')
        );
});
