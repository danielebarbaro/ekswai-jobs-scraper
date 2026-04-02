<?php

declare(strict_types=1);

use App\Domain\User\User;
use App\Infrastructure\Http\Middleware\RedirectNonAdmin;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;

it('redirects non-admin users to dashboard', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $request = Request::create('/admin');
    $request->setUserResolver(fn () => $user);

    $middleware = new RedirectNonAdmin;
    $response = $middleware->handle($request, fn (): ResponseFactory|\Illuminate\Http\Response => response('OK'));

    expect($response->isRedirect(url('/dashboard')))->toBeTrue();
});

it('allows admin users to proceed', function (): void {
    $user = User::factory()->create(['is_admin' => true]);

    $request = Request::create('/admin');
    $request->setUserResolver(fn () => $user);

    $middleware = new RedirectNonAdmin;
    $response = $middleware->handle($request, fn (): ResponseFactory|\Illuminate\Http\Response => response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('allows unauthenticated requests to proceed', function (): void {
    $request = Request::create('/admin');
    $request->setUserResolver(fn (): null => null);

    $middleware = new RedirectNonAdmin;
    $response = $middleware->handle($request, fn (): ResponseFactory|\Illuminate\Http\Response => response('OK'));

    expect($response->getContent())->toBe('OK');
});
