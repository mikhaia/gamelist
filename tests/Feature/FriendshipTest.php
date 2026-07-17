<?php

namespace Tests\Feature;

use App\Mail\FriendAddedMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FriendshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_and_remove_a_friend_without_confirmation(): void
    {
        Mail::fake();
        $user = User::factory()->create(['login' => 'player']);
        $friend = User::factory()->create(['login' => 'chrono', 'email' => 'chrono@example.com']);

        $this->actingAs($user)->post(route('friends.store', $friend))->assertRedirect();

        $this->assertDatabaseHas('friendships', [
            'user_id' => $user->id,
            'friend_id' => $friend->id,
        ]);
        $this->assertSame('friend_added', $friend->notifications()->firstOrFail()->data['event']);
        Mail::assertSent(FriendAddedMail::class, fn (FriendAddedMail $mail): bool => $mail->friend->is($user)
            && str_contains($mail->render(), '@player'));

        $this->actingAs($user)->get(route('friends.index'))
            ->assertOk()
            ->assertSee('@chrono')
            ->assertSee('Мои друзья');
        $this->actingAs($friend)->get(route('friends.index'))
            ->assertOk()
            ->assertSee('@player')
            ->assertSee('Со мной хотят дружить');

        $this->actingAs($user)->delete(route('friends.destroy', $friend))->assertRedirect();
        $this->assertDatabaseMissing('friendships', [
            'user_id' => $user->id,
            'friend_id' => $friend->id,
        ]);
    }

    public function test_user_cannot_add_themselves_as_a_friend(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('friends.store', $user))->assertUnprocessable();
        $this->assertDatabaseCount('friendships', 0);
    }

    public function test_incoming_user_is_hidden_after_friendship_becomes_mutual(): void
    {
        $user = User::factory()->create(['login' => 'player']);
        $friend = User::factory()->create(['login' => 'chrono']);
        $friend->friends()->attach($user);
        $user->friends()->attach($friend);

        $this->actingAs($user)->get(route('friends.index'))
            ->assertOk()
            ->assertSee('@chrono')
            ->assertDontSee('Со мной хотят дружить')
            ->assertDontSee('Новых входящих добавлений пока нет.');
    }
}
