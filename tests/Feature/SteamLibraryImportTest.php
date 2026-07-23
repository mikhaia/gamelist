<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Enums\Platform;
use App\Models\GameList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SteamLibraryImportTest extends TestCase
{
    use RefreshDatabase;

    private const STEAM_ID = '76561198000000001';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'services.steam.key' => 'steam-web-api-key',
            'services.steam.api_url' => 'https://api.steampowered.com',
        ]);
    }

    public function test_linked_user_sees_translucent_steam_library_card(): void
    {
        $linkedUser = User::factory()->create(['steam_id' => self::STEAM_ID]);
        $linkedUser->gameLists()->create([
            'name' => 'Ранее созданный список',
            'slug' => 'existing-list',
            'default_platform' => Platform::Pc->value,
        ]);

        $this->actingAs($linkedUser)->get(route('lists.index'))
            ->assertOk()
            ->assertSee('data-steam-library-import', false)
            ->assertSee('Игры из Steam')
            ->assertSee(asset('images/steam/list-cover.webp'))
            ->assertSee('opacity-75', false)
            ->assertSeeInOrder(['Ранее созданный список', 'data-steam-library-import'], false)
            ->assertSee('action="'.route('lists.steam.import').'"', false);

        $unlinkedUser = User::factory()->create();
        $this->actingAs($unlinkedUser)->get(route('lists.index'))
            ->assertOk()
            ->assertDontSee('data-steam-library-import', false);
    }

    public function test_linked_user_can_create_private_list_from_steam_library(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['steam_id' => self::STEAM_ID]);
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/IPlayerService/GetOwnedGames/')) {
                return Http::response([
                    'response' => [
                        'game_count' => 3,
                        'games' => [
                            ['appid' => 620, 'name' => 'Portal 2', 'playtime_forever' => 700],
                            ['appid' => 70, 'name' => 'Half-Life', 'playtime_forever' => 0],
                            ['appid' => 10, 'name' => 'Counter-Strike', 'playtime_forever' => 120],
                        ],
                    ],
                ]);
            }

            if (str_contains($request->url(), '/ISteamUserStats/GetPlayerAchievements/')) {
                $achievements = (int) $request['appid'] === 620
                    ? [['apiname' => 'FIRST', 'achieved' => 1], ['apiname' => 'SECOND', 'achieved' => 1]]
                    : [['apiname' => 'FIRST', 'achieved' => 1], ['apiname' => 'SECOND', 'achieved' => 0]];

                return Http::response([
                    'playerstats' => [
                        'steamID' => self::STEAM_ID,
                        'achievements' => $achievements,
                        'success' => true,
                    ],
                ]);
            }

            return Http::response([], 404);
        });

        $response = $this->actingAs($user)->post(route('lists.steam.import'));

        $list = GameList::query()->where('user_id', $user->id)->where('slug', 'steam')->firstOrFail();
        $response->assertRedirect(route('lists.show', $list))
            ->assertSessionHas('success', __('app.messages.steam_library_imported', ['count' => 3]));

        $this->assertSame('Игры из Steam', $list->name);
        $this->assertSame('Мои игры из Steam', $list->description);
        $this->assertSame(Platform::Steam->value, $list->default_platform);
        $this->assertFalse($list->is_public);
        $this->assertSame([
            GameStatus::WantToPlay->value,
            GameStatus::Playing->value,
            GameStatus::Completed->value,
            GameStatus::Completed100->value,
        ], $list->available_statuses);
        Storage::disk('public')->assertExists($list->cover_path);

        $this->assertDatabaseCount('games', 3);
        $this->assertDatabaseHas('games', [
            'game_list_id' => $list->id,
            'title' => 'Portal 2',
            'status' => GameStatus::Completed100->value,
            'platform' => Platform::Steam->value,
            'source_cover_url' => 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/620/library_600x900.jpg',
        ]);
        $this->assertDatabaseHas('games', [
            'game_list_id' => $list->id,
            'title' => 'Counter-Strike',
            'status' => GameStatus::Playing->value,
        ]);
        $this->assertDatabaseHas('games', [
            'game_list_id' => $list->id,
            'title' => 'Half-Life',
            'status' => GameStatus::WantToPlay->value,
        ]);
        $this->assertDatabaseHas('catalog_games', ['steam_id' => '620', 'title' => 'Portal 2']);
        $this->assertDatabaseHas('catalog_games', ['steam_id' => '70', 'title' => 'Half-Life']);
        $this->assertDatabaseHas('catalog_games', ['steam_id' => '10', 'title' => 'Counter-Strike']);
        $this->assertDatabaseCount('game_status_events', 3);
        $this->assertDatabaseHas('game_status_events', ['status' => GameStatus::Completed100->value]);
        $this->assertDatabaseHas('game_status_events', ['status' => GameStatus::Playing->value]);
        $this->assertDatabaseHas('game_status_events', ['status' => GameStatus::WantToPlay->value]);
        $this->assertSame(
            'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/620/library_600x900.jpg',
            $list->games()->where('title', 'Portal 2')->firstOrFail()->cover_url,
        );

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/?')
            && $request['key'] === 'steam-web-api-key'
            && $request['steamid'] === self::STEAM_ID
            && (bool) $request['include_appinfo']
            && (bool) $request['include_played_free_games']);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/ISteamUserStats/GetPlayerAchievements/v0001/')
            && (int) $request['appid'] === 620);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/ISteamUserStats/GetPlayerAchievements/v0001/')
            && (int) $request['appid'] === 10);
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/ISteamUserStats/GetPlayerAchievements/v0001/')
            && (int) $request['appid'] === 70);

        $this->get(route('lists.index'))
            ->assertOk()
            ->assertDontSee('data-steam-library-import', false);
    }

    public function test_private_steam_library_shows_privacy_settings_help(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['steam_id' => self::STEAM_ID]);
        Http::fake([
            'https://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/*' => Http::response(['response' => []]),
        ]);

        $this->actingAs($user)
            ->from(route('lists.index'))
            ->followingRedirects()
            ->post(route('lists.steam.import'))
            ->assertOk()
            ->assertSee('data-steam-import-error', false)
            ->assertSee('Детали игр')
            ->assertSee('https://steamcommunity.com/profiles/'.self::STEAM_ID.'/edit/settings');

        $this->assertDatabaseMissing('game_lists', ['user_id' => $user->id, 'slug' => 'steam']);
    }

    public function test_import_requires_web_api_key_and_connected_steam_account(): void
    {
        Storage::fake('public');
        Http::preventStrayRequests();

        $linkedUser = User::factory()->create(['steam_id' => self::STEAM_ID]);
        config()->set('services.steam.key', '');

        $this->actingAs($linkedUser)
            ->from(route('lists.index'))
            ->post(route('lists.steam.import'))
            ->assertRedirect(route('lists.index'))
            ->assertSessionHasErrors('steam_import');

        $unlinkedUser = User::factory()->create();
        $this->actingAs($unlinkedUser)->post(route('lists.steam.import'))
            ->assertRedirect(route('settings.edit'))
            ->assertSessionHasErrors('steam');
    }

    public function test_existing_steam_list_is_returned_without_another_api_request(): void
    {
        Http::preventStrayRequests();
        $user = User::factory()->create(['steam_id' => self::STEAM_ID]);
        $list = $user->gameLists()->create([
            'name' => 'Игры из Steam',
            'slug' => 'steam',
            'description' => 'Мои игры из Steam',
            'default_platform' => Platform::Steam->value,
            'is_public' => false,
        ]);

        $this->actingAs($user)->post(route('lists.steam.import'))
            ->assertRedirect(route('lists.show', $list))
            ->assertSessionHas('success', __('app.messages.steam_library_exists'));

        $this->assertSame(1, $user->gameLists()->count());
    }
}
