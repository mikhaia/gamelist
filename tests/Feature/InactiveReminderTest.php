<?php

namespace Tests\Feature;

use App\Mail\InactivePlayerMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InactiveReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_receives_one_email_with_playing_games(): void
    {
        Mail::fake();
        $this->travelTo(Carbon::parse('2026-07-16 09:00:00'));
        $user = User::factory()->create([
            'email' => 'player@example.com',
            'last_seen_at' => now()->subMonths(2),
        ]);
        $list = $user->gameLists()->create([
            'name' => 'Games', 'slug' => 'games', 'default_platform' => 'pc',
        ]);
        $list->games()->create([
            'title' => 'Control',
            'normalized_title' => 'control',
            'status' => 'playing',
            'platform' => 'pc',
        ]);

        $this->artisan('gamelist:send-inactive-reminders')->assertSuccessful();

        Mail::assertSent(InactivePlayerMail::class, function (InactivePlayerMail $mail) use ($user): bool {
            return $mail->recipient->is($user)
                && $mail->playingGames->pluck('title')->contains('Control')
                && str_contains($mail->render(), 'Control');
        });
        $this->assertNotNull($user->fresh()->inactive_reminder_sent_at);

        $this->artisan('gamelist:send-inactive-reminders')->assertSuccessful();
        Mail::assertSentCount(1);
    }

    public function test_active_users_and_users_without_email_are_skipped(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'active@example.com', 'last_seen_at' => now()->subDays(10)]);
        User::factory()->create(['email' => null, 'last_seen_at' => now()->subMonths(2)]);

        $this->artisan('gamelist:send-inactive-reminders')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_inactive_user_without_playing_games_receives_a_general_question(): void
    {
        Mail::fake();
        User::factory()->create([
            'email' => 'player@example.com',
            'last_seen_at' => now()->subMonths(2),
        ]);

        $this->artisan('gamelist:send-inactive-reminders')->assertSuccessful();

        Mail::assertSent(InactivePlayerMail::class, fn (InactivePlayerMail $mail): bool => $mail->playingGames->isEmpty()
            && str_contains($mail->render(), 'Во что вы играете сейчас?'));
    }

    public function test_authenticated_visit_resets_the_inactivity_cycle(): void
    {
        $this->travelTo(Carbon::parse('2026-07-16 12:00:00'));
        $user = User::factory()->create([
            'last_seen_at' => now()->subMonths(2),
            'inactive_reminder_sent_at' => now()->subDay(),
        ]);

        $this->actingAs($user)->get(route('lists.index'))->assertOk();

        $user->refresh();
        $this->assertTrue($user->last_seen_at->equalTo(now()));
        $this->assertNull($user->inactive_reminder_sent_at);
    }
}
