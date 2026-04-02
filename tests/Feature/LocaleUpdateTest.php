<?php

declare(strict_types=1);

use App\Domain\User\User;

it('updates user locale', function (): void {
    $user = User::factory()->create(['locale' => 'en']);
    $this->actingAs($user)
        ->patch('/user/locale', ['locale' => 'it'])
        ->assertRedirect();
    expect($user->fresh()->locale)->toBe('it');
});

it('rejects invalid locale', function (): void {
    $user = User::factory()->create(['locale' => 'en']);
    $this->actingAs($user)
        ->patch('/user/locale', ['locale' => 'fr'])
        ->assertSessionHasErrors('locale');
});

it('requires authentication', function (): void {
    $this->patch('/user/locale', ['locale' => 'it'])
        ->assertRedirect('/login');
});
