<?php

namespace Tests\Feature;

use App\Models\CatalogGame;
use App\Models\GameComment;
use App\Models\GameReview;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_access_admin_pages_and_see_navigation_link(): void
    {
        $user = User::factory()->create(['login' => 'regular_player']);
        $admin = User::factory()->create(['login' => 'project_admin', 'is_admin' => true]);

        $this->assertFalse($user->is_admin);
        $this->assertTrue($admin->is_admin);

        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('lists.index'))->assertOk()->assertDontSee('data-admin-link', false);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Пользователей всего')
            ->assertSee('GameList Admin');
        $this->actingAs($admin)->get(route('lists.index'))
            ->assertOk()
            ->assertSee('href="'.route('admin.dashboard').'"', false)
            ->assertSee('data-admin-link', false);
    }

    public function test_dashboard_shows_counts_storage_usage_and_latest_activity(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2026-07-23 12:00:00');

        try {
            $admin = User::factory()->create([
                'login' => 'project_admin',
                'is_admin' => true,
                'avatar_path' => 'avatars/admin.webp',
            ]);
            $recentUser = User::factory()->create(['login' => 'recent_player']);
            $recentUser->forceFill([
                'created_at' => now()->subDays(5),
                'last_seen_at' => now()->subDays(2),
            ])->saveQuietly();
            $olderUser = User::factory()->create(['login' => 'older_player']);
            $olderUser->forceFill(['created_at' => now()->subDays(40)])->saveQuietly();

            $recentGame = CatalogGame::query()->create([
                'title' => 'Recent Catalog Game',
                'normalized_title' => 'recent catalog game',
                'genres' => ['Action'],
                'genre_slugs' => ['action'],
                'platforms' => ['PC'],
                'platform_ids' => [4],
            ]);
            $olderGame = CatalogGame::query()->create([
                'title' => 'Older Catalog Game',
                'normalized_title' => 'older catalog game',
            ]);
            $olderGame->forceFill(['created_at' => now()->subDays(40)])->saveQuietly();

            $list = $recentUser->gameLists()->create([
                'name' => 'My Games',
                'slug' => 'my-games',
                'default_platform' => 'pc',
                'cover_path' => 'list-covers/list.webp',
            ]);
            $game = $list->games()->create([
                'title' => 'Community Game',
                'normalized_title' => 'community game',
                'status' => 'playing',
                'platform' => 'pc',
                'cover_path' => 'game-covers/game.webp',
                'catalog_game_id' => $recentGame->id,
            ]);
            $screenshot = $game->screenshots()->create(['path' => 'game-screenshots/shot.webp']);
            GameComment::query()->create([
                'game_id' => $game->id,
                'user_id' => $recentUser->id,
                'body' => 'Последний комментарий сообщества',
            ]);
            GameReview::query()->create([
                'user_id' => $recentUser->id,
                'catalog_game_id' => $recentGame->id,
                'rating' => 9,
                'body' => 'Свежий обзор игры',
            ]);

            Storage::disk('public')->put($admin->avatar_path, str_repeat('a', 1024));
            Storage::disk('public')->put($list->cover_path, str_repeat('b', 2048));
            Storage::disk('public')->put($game->cover_path, str_repeat('c', 3072));
            Storage::disk('public')->put($screenshot->path, str_repeat('d', 4096));

            $this->actingAs($admin)->get(route('admin.dashboard'))
                ->assertOk()
                ->assertSee('data-admin-stat="users-total" data-admin-value="3"', false)
                ->assertSee('data-admin-stat="users-7-days" data-admin-value="2"', false)
                ->assertSee('data-admin-stat="games-total" data-admin-value="2"', false)
                ->assertSee('data-admin-stat="games-7-days" data-admin-value="1"', false)
                ->assertSee('data-admin-stat="files-total" data-admin-value="10,0 КБ"', false)
                ->assertSee('aria-label="Аватары: 10%"', false)
                ->assertSee('data-admin-chart="catalog-games"', false)
                ->assertSee('data-chart-date="2026-07-23" data-chart-count="1"', false)
                ->assertSee('data-admin-chart="user-last-seen"', false)
                ->assertSee('data-chart-date="2026-07-21" data-chart-count="1"', false)
                ->assertSee('Recent Catalog Game')
                ->assertSee('Community Game')
                ->assertSee('Последний комментарий сообщества')
                ->assertSee('Свежий обзор игры');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_users_can_be_searched_and_sorted_in_admin(): void
    {
        $admin = User::factory()->create(['login' => 'middle_admin', 'is_admin' => true]);
        User::factory()->create(['login' => 'zulu_player', 'email' => 'zulu@example.com']);
        User::factory()->create(['login' => 'alpha_player', 'email' => 'alpha@example.com']);

        $this->actingAs($admin)->get(route('admin.users.index', ['q' => 'alpha@example.com']))
            ->assertOk()
            ->assertSee('data-admin-user="alpha_player"', false)
            ->assertDontSee('data-admin-user="zulu_player"', false);

        $this->actingAs($admin)->get(route('admin.users.index', ['sort' => 'login', 'direction' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder([
                'data-admin-user="alpha_player"',
                'data-admin-user="middle_admin"',
                'data-admin-user="zulu_player"',
            ], false);
    }

    public function test_catalog_games_can_be_searched_filtered_and_sorted_in_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        CatalogGame::query()->create([
            'title' => 'Zelda Adventure',
            'normalized_title' => 'zelda adventure',
            'genres' => ['Adventure'],
            'genre_slugs' => ['adventure'],
            'age_rating' => 'Everyone',
            'platforms' => ['Nintendo Switch'],
            'platform_ids' => [7],
        ]);
        CatalogGame::query()->create([
            'title' => 'Alpha Strike',
            'normalized_title' => 'alpha strike',
            'genres' => ['Action'],
            'genre_slugs' => ['action'],
            'age_rating' => 'Teen',
            'platforms' => ['PC'],
            'platform_ids' => [4],
        ]);

        $this->actingAs($admin)->get(route('admin.games.index', [
            'q' => 'Alpha',
            'genre' => 'action',
            'age_rating' => 'Teen',
            'platform' => 4,
        ]))
            ->assertOk()
            ->assertSee('data-admin-game="Alpha Strike"', false)
            ->assertDontSee('data-admin-game="Zelda Adventure"', false);

        $this->actingAs($admin)->get(route('admin.games.index', ['sort' => 'title', 'direction' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder([
                'data-admin-game="Alpha Strike"',
                'data-admin-game="Zelda Adventure"',
            ], false);
    }

    public function test_admin_can_browse_file_categories_and_download_files(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create(['login' => 'media_owner', 'avatar_path' => 'avatars/avatar.webp']);
        $list = $owner->gameLists()->create([
            'name' => 'Media List',
            'slug' => 'media-list',
            'default_platform' => 'pc',
            'cover_path' => 'list-covers/list.webp',
        ]);
        $game = $list->games()->create([
            'title' => 'Media Game',
            'normalized_title' => 'media game',
            'status' => 'playing',
            'platform' => 'pc',
            'cover_path' => 'game-covers/game.webp',
        ]);
        $screenshot = $game->screenshots()->create(['path' => 'game-screenshots/shot.webp']);

        Storage::disk('public')->put($owner->avatar_path, str_repeat('a', 512));
        Storage::disk('public')->put($list->cover_path, str_repeat('b', 1024));
        Storage::disk('public')->put($game->cover_path, str_repeat('c', 1536));
        Storage::disk('public')->put($screenshot->path, str_repeat('d', 2048));

        $this->actingAs($admin)->get(route('admin.files.index', ['type' => 'avatars']))
            ->assertOk()
            ->assertSee('data-admin-file="avatar.webp"', false)
            ->assertSee('512 Б');
        $this->actingAs($admin)->get(route('admin.files.index', ['type' => 'list-covers']))
            ->assertOk()
            ->assertSee('data-admin-file="list.webp"', false)
            ->assertSee('1,00 КБ');
        $this->actingAs($admin)->get(route('admin.files.index', ['type' => 'game-covers']))
            ->assertOk()
            ->assertSee('data-admin-file="game.webp"', false)
            ->assertSee('1,50 КБ');
        $this->actingAs($admin)->get(route('admin.files.index', ['type' => 'screenshots']))
            ->assertOk()
            ->assertSee('data-admin-file="shot.webp"', false)
            ->assertSee('2,00 КБ');

        $this->actingAs($admin)
            ->get(route('admin.files.download', ['type' => 'screenshots', 'id' => $screenshot->id]))
            ->assertOk()
            ->assertDownload('shot.webp');
    }
}
