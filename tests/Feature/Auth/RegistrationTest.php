<?php

test('registration screen can be rendered', function (): void {
    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

test('new users can register', function (): void {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Eks#W4i!tEst9',
        'password_confirmation' => 'Eks#W4i!tEst9',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
