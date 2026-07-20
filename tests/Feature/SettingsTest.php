<?php

namespace Tests\Feature;

use App\Enums\Achievement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_replace_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['avatar_path' => 'avatars/old.webp']);
        Storage::disk('public')->put('avatars/old.webp', 'old');

        $this->actingAs($user)->patch(route('settings.avatar'), [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 800, 800),
        ])->assertRedirect();

        $user->refresh();
        Storage::disk('public')->assertMissing('avatars/old.webp');
        Storage::disk('public')->assertExists($user->avatar_path);
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'key' => Achievement::Avatar1->value,
        ]);
    }

    public function test_user_can_change_password_with_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($user)->patch(route('settings.password'), [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_user_can_set_change_and_remove_email(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('settings.email'), [
            'email' => ' Player@Example.COM ',
        ])->assertRedirect();

        $this->assertSame('player@example.com', $user->fresh()->email);

        $this->actingAs($user)->patch(route('settings.email'), [
            'email' => '',
        ])->assertRedirect();

        $this->assertNull($user->fresh()->email);
    }

    public function test_user_cannot_use_another_users_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'current@example.com']);

        $this->actingAs($user)->patch(route('settings.email'), [
            'email' => 'TAKEN@EXAMPLE.COM',
        ])->assertSessionHasErrors('email');

        $this->assertSame('current@example.com', $user->fresh()->email);
    }
}
