<?php

declare(strict_types=1);

use App\Domain\User\User;
use App\Infrastructure\Http\Responses\LoginResponse;
use Illuminate\Http\Request;

it('redirects admin to admin panel', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $request = Request::create('/login', 'POST');
    $request->setUserResolver(fn () => $user);

    $loginResponse = new LoginResponse;
    $response = $loginResponse->toResponse($request);

    expect($response->isRedirect())->toBeTrue()
        ->and($response->getTargetUrl())->toContain('/admin');
});

it('redirects regular user to dashboard', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $request = Request::create('/login', 'POST');
    $request->setUserResolver(fn () => $user);

    $loginResponse = new LoginResponse;
    $response = $loginResponse->toResponse($request);

    expect($response->isRedirect())->toBeTrue()
        ->and($response->getTargetUrl())->toContain('/dashboard');
});

it('returns json response when request wants json', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $request = Request::create('/login', 'POST', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $request->setUserResolver(fn () => $user);

    $loginResponse = new LoginResponse;
    $response = $loginResponse->toResponse($request);

    expect($response->getStatusCode())->toBe(204);
});
