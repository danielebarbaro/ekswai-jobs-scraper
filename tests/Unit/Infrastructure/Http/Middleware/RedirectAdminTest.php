<?php

declare(strict_types=1);

use App\Domain\User\User;
use App\Infrastructure\Http\Middleware\RedirectAdmin;
use Illuminate\Http\Request;

it('redirects admin users to admin panel', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $request = Request::create('/dashboard');
    $request->setUserResolver(fn () => $user);

    $middleware = new RedirectAdmin;
    $response = $middleware->handle($request, fn () => response('OK'));

    expect($response->isRedirect())->toBeTrue()
        ->and($response->headers->get('Location'))->toBe('/admin');
});

it('allows non-admin users to proceed', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $request = Request::create('/dashboard');
    $request->setUserResolver(fn () => $user);

    $middleware = new RedirectAdmin;
    $response = $middleware->handle($request, fn () => response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('allows unauthenticated requests to proceed', function () {
    $request = Request::create('/dashboard');
    $request->setUserResolver(fn () => null);

    $middleware = new RedirectAdmin;
    $response = $middleware->handle($request, fn () => response('OK'));

    expect($response->getContent())->toBe('OK');
});
