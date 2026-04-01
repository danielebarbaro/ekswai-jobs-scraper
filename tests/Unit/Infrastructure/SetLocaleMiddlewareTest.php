<?php

declare(strict_types=1);

use App\Domain\User\User;
use App\Infrastructure\Http\Middleware\SetLocale;
use Illuminate\Http\Request;

it('sets locale from authenticated user', function () {
    $user = User::factory()->create(['locale' => 'it']);
    $request = Request::create('/dashboard');
    $request->setUserResolver(fn () => $user);

    $middleware = new SetLocale();
    $middleware->handle($request, fn () => new \Illuminate\Http\Response());

    expect(app()->getLocale())->toBe('it');
});

it('defaults to en for unauthenticated users', function () {
    app()->setLocale('it');
    $request = Request::create('/dashboard');
    $request->setUserResolver(fn () => null);

    $middleware = new SetLocale();
    $middleware->handle($request, fn () => new \Illuminate\Http\Response());

    expect(app()->getLocale())->toBe('en');
});

it('defaults to en when user has no locale', function () {
    $user = User::factory()->create(['locale' => 'en']);
    $request = Request::create('/dashboard');
    $request->setUserResolver(fn () => $user);

    $middleware = new SetLocale();
    $middleware->handle($request, fn () => new \Illuminate\Http\Response());

    expect(app()->getLocale())->toBe('en');
});
