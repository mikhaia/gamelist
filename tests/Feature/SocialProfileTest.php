<?php

namespace Tests\Feature;

use App\Models\CatalogGame;
use App\Models\GameList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SocialProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_card_shows_online_and_relative_last_activity(): void
    {
        $this->travelTo(Carbon::parse('2026-07-17 12:00:00'));
        $profile = User::factory()->create([
            'login' => 'activity_player',
            'last_seen_at' => now()->subMinutes(59),
        ]);

        $this->get(route('profiles.show', $profile->login))
            ->assertOk()
            ->assertSeeText('Онлайн')
            ->assertDontSeeText('Игровой профиль');

        $profile->forceFill(['last_seen_at' => now()->subHours(21)])->saveQuietly();
        $this->get(route('profiles.show', $profile->login))
            ->assertOk()
            ->assertSeeText('Последняя активность: 21 час назад');

        $profile->forceFill(['last_seen_at' => now()->subDays(5)])->saveQuietly();
        $this->get(route('profiles.show', $profile->login))
            ->assertOk()
            ->assertSeeText('Последняя активность: 5 дней назад');

        $profile->forceFill(['last_seen_at' => now()->subDays(60)])->saveQuietly();
        $this->get(route('profiles.show', $profile->login))
            ->assertOk()
            ->assertSeeText('Последняя активность: 2 месяца назад');
    }

    public function test_profile_shows_three_recent_public_game_status_columns(): void
    {
        $profile = User::factory()->create(['login' => 'chrono']);
        $publicList = $profile->gameLists()->create([
            'name' => 'Public Games',
            'slug' => 'public-games',
            'default_platform' => 'pc',
            'is_public' => true,
        ]);
        $privateList = $profile->gameLists()->create([
            'name' => 'Private Games',
            'slug' => 'private-games',
            'default_platform' => 'pc',
            'is_public' => false,
        ]);

        $createGame = function (GameList $list, string $title, string $status, string $createdAt, ?string $startedAt = null, ?string $completedAt = null): void {
            $game = $list->games()->create([
                'title' => $title,
                'normalized_title' => strtolower($title),
                'status' => $status,
                'platform' => 'pc',
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
            ]);
            $game->forceFill([
                'created_at' => Carbon::parse($createdAt),
                'updated_at' => Carbon::parse($createdAt),
            ])->saveQuietly();
        };

        $createGame($publicList, 'Want Older', 'want_to_play', '2026-01-01');
        $createGame($publicList, 'Want First', 'want_to_play', '2026-01-02');
        $createGame($publicList, 'Want Second', 'want_to_play', '2026-01-03');
        $createGame($publicList, 'Want Latest', 'want_to_play', '2026-01-04');
        $createGame($publicList, 'Playing Older', 'playing', '2026-02-10', '2026-02-01');
        $createGame($publicList, 'Playing Latest', 'playing', '2026-02-02', '2026-02-05');
        $createGame($publicList, 'Completed Older', 'completed', '2026-03-10', '2026-02-01', '2026-03-01');
        $createGame($publicList, 'Completed Latest', 'completed', '2026-03-02', '2026-02-01', '2026-03-06');
        $createGame($privateList, 'Secret Playing', 'playing', '2026-04-01', '2026-04-01');

        $response = $this->get(route('profiles.show', $profile->login));

        $response->assertOk()
            ->assertSeeInOrder(['Хочу сыграть', 'Want Latest', 'Want Second', 'Want First'])
            ->assertSeeInOrder(['Играю', 'Playing Latest', 'Playing Older'])
            ->assertSeeInOrder(['Пройдена', 'Completed Latest', 'Completed Older'])
            ->assertSeeText('Добавлена 04.01.2026')
            ->assertSeeText('Начал 05.02.2026')
            ->assertSeeText('Закончил 06.03.2026')
            ->assertSeeInOrder([
                'data-profile-status-count="want_to_play"',
                'data-count="4"',
                'data-profile-status-count="playing"',
                'data-count="2"',
                'data-profile-status-count="completed"',
                'data-count="2"',
            ], false)
            ->assertDontSee('Want Older')
            ->assertDontSee('Secret Playing');
        $this->assertSame(3, substr_count($response->getContent(), 'data-profile-status-column='));
    }

    public function test_public_profile_shows_only_public_lists_and_public_game_statistics(): void
    {
        $profile = User::factory()->create(['login' => 'chrono', 'email' => 'chrono@example.com']);
        $friend = User::factory()->create();
        $profile->friends()->attach($friend);

        $publicList = $profile->gameLists()->create([
            'name' => 'Public Games',
            'slug' => 'public-games',
            'default_platform' => 'pc',
            'is_public' => true,
        ]);
        $publicList->games()->create([
            'title' => 'Control',
            'normalized_title' => 'control',
            'status' => 'playing',
            'platform' => 'pc',
        ]);
        $privateList = $profile->gameLists()->create([
            'name' => 'Private Games',
            'slug' => 'private-games',
            'default_platform' => 'pc',
            'is_public' => false,
        ]);
        $privateList->games()->create([
            'title' => 'Secret Game',
            'normalized_title' => 'secret game',
            'status' => 'completed',
            'platform' => 'pc',
        ]);

        $this->get(route('profiles.show', 'CHRONO'))
            ->assertOk()
            ->assertSee('@chrono')
            ->assertSee('Public Games')
            ->assertDontSee('Private Games')
            ->assertSee('aria-label="публичный список: 1"', false)
            ->assertSee('aria-label="игра: 1"', false)
            ->assertDontSee('title="Играю" aria-label="Играю: 1"', false)
            ->assertSeeInOrder([
                'data-profile-history',
                'data-profile-summary-counts',
                'data-profile-achievements',
            ], false)
            ->assertSee('sports_esports')
            ->assertDontSeeText('Пройдено · 1');
    }

    public function test_owner_can_choose_up_to_three_favorite_games_from_own_lists(): void
    {
        $user = User::factory()->create(['login' => 'chrono']);
        $other = User::factory()->create();
        $list = $user->gameLists()->create([
            'name' => 'Games', 'slug' => 'games', 'default_platform' => 'pc',
        ]);
        $games = collect(['Hades', 'Control', 'Celeste'])->map(
            fn (string $title) => $list->games()->create([
                'title' => $title,
                'normalized_title' => strtolower($title),
                'status' => 'completed',
                'platform' => 'pc',
            ]),
        );
        $foreignList = $other->gameLists()->create([
            'name' => 'Other', 'slug' => 'other', 'default_platform' => 'pc',
        ]);
        $foreignGame = $foreignList->games()->create([
            'title' => 'Foreign',
            'normalized_title' => 'foreign',
            'status' => 'playing',
            'platform' => 'pc',
        ]);

        $this->actingAs($user)->get(route('profiles.show', 'chrono'))
            ->assertOk()
            ->assertSee('Любимая игра 1')
            ->assertSee('Начните вводить название')
            ->assertSee('data-favorite-combobox', false)
            ->assertSee('data-title="Hades"', false)
            ->assertSee('panel relative z-30 mt-4', false)
            ->assertSee('left-0 z-50 mt-2', false)
            ->assertDontSee('<select class="field" id="favorite_game_', false);

        $this->actingAs($user)->patch(route('profile.favorites.update'), [
            'game_ids' => [$games[1]->id, '', $games[0]->id],
        ])->assertRedirect(route('profiles.show', 'chrono'));

        $this->assertSame(
            [$games[1]->id, $games[0]->id],
            $user->favoriteGames()->pluck('games.id')->all(),
        );

        $this->actingAs($user)->patch(route('profile.favorites.update'), [
            'game_ids' => [$foreignGame->id],
        ])->assertSessionHasErrors('game_ids');
    }

    public function test_profile_hides_favorite_games_when_user_has_not_added_any_games(): void
    {
        $user = User::factory()->create(['login' => 'empty_player']);

        $this->get(route('profiles.show', $user->login))
            ->assertOk()
            ->assertDontSeeText('Любимые игры')
            ->assertDontSee('data-favorite-picker', false);

        $this->actingAs($user)->get(route('profiles.show', $user->login))
            ->assertOk()
            ->assertDontSeeText('Любимые игры')
            ->assertDontSee('data-favorite-picker', false);
    }

    public function test_favorites_link_to_their_owner_game_entries(): void
    {
        $user = User::factory()->create(['login' => 'favorite_player']);
        $list = $user->gameLists()->create([
            'name' => 'Favorites', 'slug' => 'favorites', 'default_platform' => 'pc', 'is_public' => true,
        ]);
        $catalogGame = CatalogGame::query()->create([
            'hltb_id' => 451,
            'title' => 'Catalog Favorite',
            'normalized_title' => 'catalog favorite',
        ]);
        $linkedGame = $list->games()->create([
            'title' => $catalogGame->title,
            'normalized_title' => $catalogGame->normalized_title,
            'status' => 'completed',
            'platform' => 'pc',
            'hltb_id' => $catalogGame->hltb_id,
        ]);
        $manualGame = $list->games()->create([
            'title' => 'Manual Favorite',
            'normalized_title' => 'manual favorite',
            'status' => 'playing',
            'platform' => 'pc',
        ]);
        $user->favoriteGames()->attach([
            $linkedGame->id => ['sort_order' => 0],
            $manualGame->id => ['sort_order' => 1],
        ]);

        $this->get(route('profiles.show', $user->login))
            ->assertOk()
            ->assertSee('href="'.route('games.view', $linkedGame).'"', false)
            ->assertSee('aria-label="Открыть страницу игры Catalog Favorite"', false)
            ->assertSee('href="'.route('games.view', $manualGame).'"', false)
            ->assertSee('aria-label="Открыть страницу игры Manual Favorite"', false);
    }

    public function test_profile_cover_is_optimized_and_replaces_previous_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'login' => 'cover_player',
            'profile_cover_path' => 'profile-covers/old.webp',
        ]);
        Storage::disk('public')->put('profile-covers/old.webp', 'old');

        $this->actingAs($user)->patch(route('settings.profile-cover'), [
            'profile_cover' => UploadedFile::fake()->image('profile.jpg', 3000, 1800),
        ])->assertRedirect();

        $user->refresh();
        Storage::disk('public')->assertMissing('profile-covers/old.webp');
        Storage::disk('public')->assertExists($user->profile_cover_path);
        $this->assertStringEndsWith('.webp', $user->profile_cover_path);
        [$width, $height] = array_slice(
            getimagesizefromstring(Storage::disk('public')->get($user->profile_cover_path)),
            0,
            2,
        );
        $this->assertLessThanOrEqual(2432, $width);
        $this->assertLessThanOrEqual(1400, $height);

        $this->actingAs($user)->get(route('profiles.show', $user->login))
            ->assertOk()
            ->assertSee('from-[#090b16]/60', false)
            ->assertSee('via-[#090b16]/35', false)
            ->assertSee('to-[#090b16]/10', false);
    }

    public function test_public_list_links_the_author_to_their_profile(): void
    {
        $profile = User::factory()->create(['login' => 'chrono']);
        $list = $profile->gameLists()->create([
            'name' => 'Games',
            'slug' => 'games',
            'default_platform' => 'pc',
            'is_public' => true,
        ]);

        $this->get(route('public.lists.show', ['chrono', $list->slug]))
            ->assertOk()
            ->assertSee(route('profiles.show', 'chrono'))
            ->assertSee('Добавить в друзья');
    }

    public function test_authenticated_navigation_links_avatar_to_profile_and_has_settings_button(): void
    {
        $user = User::factory()->create(['login' => 'chrono']);

        $this->actingAs($user)->get(route('lists.index'))
            ->assertOk()
            ->assertSee('href="'.route('profiles.show', 'chrono').'"', false)
            ->assertSee('aria-label="Мой профиль"', false)
            ->assertSee('href="'.route('settings.edit').'"', false)
            ->assertSee('aria-label="Настройки"', false)
            ->assertSee('href="'.route('search.index').'"', false)
            ->assertSee('aria-label="Поиск игр"', false)
            ->assertSeeInOrder([
                'href="'.route('settings.edit').'"',
                'href="'.route('search.index').'"',
                'data-notification-center',
            ], false)
            ->assertSee('cursor-pointer', false);
    }
}
