<?php

declare(strict_types=1);

use App\Domain\User\User;
use App\Infrastructure\Http\Middleware\RedirectAdmin;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;

it('redirects admin users to admin panel', function (): void {
    $user = User::factory()->create(['is_admin' => true]);

    $request = Request::create('/dashboard');
    $request->setUserResolver(fn () => $user);

    $middleware = new RedirectAdmin;
    $response = $middleware->handle($request, fn (): ResponseFactory|\Illuminate\Http\Response => response('OK'));

    expect($response->isRedirect())->toBeTrue()
        ->and($response->headers->get('Location'))->toBe('/admin');
});

it('allows non-admin users to proceed', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $request = Request::create('/dashboard');
    $request->setUserResolver(fn () => $user);

    $middleware = new RedirectAdmin;
    $response = $middleware->handle($request, fn (): ResponseFactory|\Illuminate\Http\Response => response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('allows unauthenticated requests to proceed', function (): void {
    $request = Request::create('/dashboard');
    $request->setUserResolver(fn (): null => null);

    $middleware = new RedirectAdmin;
    $response = $middleware->handle($request, fn (): ResponseFactory|\Illuminate\Http\Response => response('OK'));

    expect($response->getContent())->toBe('OK');
});
