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
        $this->assertDatabaseHas('users', ['login' => 'player_one']);
    }

    public function test_user_can_login_with_login_and_password(): void
    {
        User::factory()->create(['login' => 'chrono', 'password' => 'secret123']);

        $this->post('/login', ['login' => 'CHRONO', 'password' => 'secret123'])
            ->assertRedirect(route('lists.index'));

        $this->assertAuthenticated();
    }
}
