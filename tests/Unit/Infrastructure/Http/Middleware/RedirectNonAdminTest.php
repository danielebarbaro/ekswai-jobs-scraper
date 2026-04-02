<?php

declare(strict_types=1);

use App\Domain\User\User;
use App\Infrastructure\Http\Middleware\RedirectNonAdmin;
use Illuminate\Http\Request;

it('redirects non-admin users to dashboard', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $request = Request::create('/admin');
    $request->setUserResolver(fn () => $user);

    $middleware = new RedirectNonAdmin;
    $response = $middleware->handle($request, fn () => response('OK'));

    expect($response->isRedirect(url('/dashboard')))->toBeTrue();
});

it('allows admin users to proceed', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $request = Request::create('/admin');
    $request->setUserResolver(fn () => $user);

    $middleware = new RedirectNonAdmin;
    $response = $middleware->handle($request, fn () => response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('allows unauthenticated requests to proceed', function () {
    $request = Request::create('/admin');
    $request->setUserResolver(fn () => null);

    $middleware = new RedirectNonAdmin;
    $response = $middleware->handle($request, fn () => response('OK'));

    expect($response->getContent())->toBe('OK');
});
