<?php

declare(strict_types=1);

use App\Domain\User\User;
use App\Infrastructure\Http\Responses\TwoFactorLoginResponse;
use Illuminate\Http\Request;

it('redirects admin to admin panel', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $request = Request::create('/two-factor-challenge', 'POST');
    $request->setUserResolver(fn () => $user);

    $response = (new TwoFactorLoginResponse)->toResponse($request);

    expect($response->isRedirect())->toBeTrue()
        ->and($response->getTargetUrl())->toContain('/admin');
});

it('redirects regular user to dashboard', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $request = Request::create('/two-factor-challenge', 'POST');
    $request->setUserResolver(fn () => $user);

    $response = (new TwoFactorLoginResponse)->toResponse($request);

    expect($response->isRedirect())->toBeTrue()
        ->and($response->getTargetUrl())->toContain('/dashboard');
});

it('returns json response when request wants json', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $request = Request::create('/two-factor-challenge', 'POST', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $request->setUserResolver(fn () => $user);

    $response = (new TwoFactorLoginResponse)->toResponse($request);

    expect($response->getStatusCode())->toBe(204);
});
