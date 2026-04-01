<?php

declare(strict_types=1);

use App\Domain\User\User;

it('updates user locale', function () {
    $user = User::factory()->create(['locale' => 'en']);
    $this->actingAs($user)
        ->patch('/user/locale', ['locale' => 'it'])
        ->assertRedirect();
    expect($user->fresh()->locale)->toBe('it');
});

it('rejects invalid locale', function () {
    $user = User::factory()->create(['locale' => 'en']);
    $this->actingAs($user)
        ->patch('/user/locale', ['locale' => 'fr'])
        ->assertSessionHasErrors('locale');
});

it('requires authentication', function () {
    $this->patch('/user/locale', ['locale' => 'it'])
        ->assertRedirect('/login');
});
