<?php

namespace Tests\Feature;

use App\Models\CatalogGame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SocialProfileTest extends TestCase
{
    use RefreshDatabase;

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
            ->assertSeeText('1 публичный список')
            ->assertSeeText('1 игра')
            ->assertSeeText('Играю · 1')
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

    public function test_catalog_favorite_links_to_game_page_while_manual_favorite_does_not(): void
    {
        $user = User::factory()->create(['login' => 'favorite_player']);
        $list = $user->gameLists()->create([
            'name' => 'Favorites', 'slug' => 'favorites', 'default_platform' => 'pc',
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
            ->assertSee('href="'.route('games.show', $catalogGame).'"', false)
            ->assertSee('aria-label="Открыть страницу игры Catalog Favorite"', false)
            ->assertSee('Manual Favorite')
            ->assertDontSee('aria-label="Открыть страницу игры Manual Favorite"', false);
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
            'profile_cover' => UploadedFile::fake()->image('profile.jpg', 1800, 700),
        ])->assertRedirect();

        $user->refresh();
        Storage::disk('public')->assertMissing('profile-covers/old.webp');
        Storage::disk('public')->assertExists($user->profile_cover_path);
        $this->assertStringEndsWith('.webp', $user->profile_cover_path);

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
