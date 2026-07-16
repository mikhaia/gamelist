<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_followers_receive_public_activity_and_owner_receives_game_encouragement(): void
    {
        $owner = User::factory()->create(['login' => 'chrono']);
        $follower = User::factory()->create();
        $stranger = User::factory()->create();
        Friendship::query()->create(['user_id' => $follower->id, 'friend_id' => $owner->id]);

        $list = $owner->gameLists()->create([
            'name' => 'Games',
            'slug' => 'games',
            'default_platform' => 'pc',
            'is_public' => true,
        ]);
        $game = $list->games()->create([
            'title' => 'Control',
            'normalized_title' => 'control',
            'status' => 'want_to_play',
            'platform' => 'pc',
        ]);
        $game->update(['status' => 'playing']);
        $game->update(['status' => 'completed']);

        $this->assertEqualsCanonicalizing(
            ['public_list_created', 'public_game_added', 'friend_started_game', 'friend_completed_game'],
            $follower->notifications()->get()->pluck('data.event')->all(),
        );
        $this->assertEqualsCanonicalizing(
            ['good_luck', 'congratulations'],
            $owner->notifications()->get()->pluck('data.event')->all(),
        );
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $stranger->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_private_list_activity_is_not_sent_to_followers(): void
    {
        $owner = User::factory()->create();
        $follower = User::factory()->create();
        Friendship::query()->create(['user_id' => $follower->id, 'friend_id' => $owner->id]);
        $list = $owner->gameLists()->create([
            'name' => 'Private',
            'slug' => 'private',
            'default_platform' => 'pc',
            'is_public' => false,
        ]);
        $game = $list->games()->create([
            'title' => 'Secret',
            'normalized_title' => 'secret',
            'status' => 'want_to_play',
            'platform' => 'pc',
        ]);

        $game->update(['status' => 'playing']);

        $this->assertCount(0, $follower->notifications);
        $this->assertSame('good_luck', $owner->notifications()->firstOrFail()->data['event']);
    }

    public function test_user_can_delete_one_notification_and_clear_the_rest(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $user->notify(new ActivityNotification('one', 'Первое', '/', 'notifications'));
        $user->notify(new ActivityNotification('two', 'Второе', '/', 'notifications'));
        $other->notify(new ActivityNotification('other', 'Чужое', '/', 'notifications'));
        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($other)
            ->deleteJson(route('notifications.destroy', $notification->id))
            ->assertNotFound();
        $this->actingAs($user)
            ->deleteJson(route('notifications.destroy', $notification->id))
            ->assertOk();
        $this->assertCount(1, $user->notifications()->get());

        $this->actingAs($user)->deleteJson(route('notifications.clear'))->assertOk();
        $this->assertCount(0, $user->notifications()->get());
        $this->assertCount(1, $other->notifications()->get());
    }

    public function test_notification_bell_renders_saved_messages(): void
    {
        $user = User::factory()->create();
        $user->notify(new ActivityNotification('hello', 'Новое игровое событие', '/', 'notifications'));

        $this->actingAs($user)->get(route('lists.index'))
            ->assertOk()
            ->assertSee('Новое игровое событие')
            ->assertSee('data-notification-toggle', false)
            ->assertSee('bg-[#0b0e1a]', false);
    }
}
