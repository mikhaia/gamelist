<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_login_and_password(): void
    {
        $response = $this->post('/register', [
            'login' => 'Player_One',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('lists.index'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['login' => 'player_one', 'email' => null]);
    }

    public function test_user_can_register_with_an_optional_email(): void
    {
        $this->post('/register', [
            'login' => 'Player_Email',
            'email' => ' Player@Example.COM ',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('lists.index'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'login' => 'player_email',
            'email' => 'player@example.com',
        ]);
    }

    public function test_email_must_be_unique_during_registration(): void
    {
        User::factory()->create(['email' => 'player@example.com']);

        $this->post('/register', [
            'login' => 'Another_Player',
            'email' => 'PLAYER@EXAMPLE.COM',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['login' => 'another_player']);
    }

    public function test_route_names_cannot_be_used_as_logins(): void
    {
        $this->post('/register', [
            'login' => 'Friends',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_user_can_login_with_login_and_password(): void
    {
        User::factory()->create(['login' => 'chrono', 'password' => 'secret123']);

        $this->post('/login', ['login' => 'CHRONO', 'password' => 'secret123'])
            ->assertRedirect(route('lists.index'));

        $this->assertAuthenticated();
    }

    public function test_user_can_login_with_email_and_password(): void
    {
        User::factory()->create([
            'login' => 'chrono',
            'email' => 'chrono@example.com',
            'password' => 'secret123',
        ]);

        $this->post('/login', ['login' => ' CHRONO@EXAMPLE.COM ', 'password' => 'secret123'])
            ->assertRedirect(route('lists.index'));

        $this->assertAuthenticated();
    }
}
