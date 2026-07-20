<?php

namespace Tests\Feature;

use App\Models\CatalogGame;
use App\Models\Game;
use App\Models\GameComment;
use App\Models\GameScreenshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GameDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_game_entry_shows_the_owner_personal_details_and_can_be_updated(): void
    {
        $owner = User::factory()->create(['login' => 'entry_owner']);
        $visitor = User::factory()->create();
        $catalogGame = CatalogGame::query()->create([
            'hltb_id' => 2077,
            'title' => 'Cyberpunk 2077',
            'normalized_title' => 'cyberpunk 2077',
        ]);
        $game = $this->gameFor($owner, true, [
            'catalog_game_id' => $catalogGame->id,
            'title' => 'Cyberpunk 2077',
            'normalized_title' => 'cyberpunk 2077',
        ]);

        $this->from(route('games.view', $game))->actingAs($owner)->patch(route('games.personal-details.update', $game), [
            'notes' => '**Моя личная запись**, а не описание из каталога.',
        ])->assertRedirect(route('games.view', $game));

        $this->from(route('games.view', $game))->actingAs($owner)->patch(route('game-reviews.rating.update', $catalogGame), [
            'rating' => 9,
        ])->assertRedirect(route('games.view', $game));
        $this->from(route('games.view', $game))->actingAs($owner)->patch(route('game-reviews.opinion.update', $catalogGame), [
            'body' => '**Найт-Сити** забрал ещё сотню часов жизни.',
        ])->assertRedirect(route('games.view', $game));

        $this->actingAs($visitor)->patch(route('games.personal-details.update', $game), [
            'notes' => 'Чужая запись не должна меняться.',
        ])->assertForbidden();

        $game->refresh();
        $this->assertDatabaseHas('game_reviews', [
            'user_id' => $owner->id,
            'catalog_game_id' => $catalogGame->id,
            'rating' => 9,
            'body' => '**Найт-Сити** забрал ещё сотню часов жизни.',
        ]);

        $this->actingAs($owner)->get(route('games.view', $game))
            ->assertOk()
            ->assertSee('Cyberpunk 2077')
            ->assertSee('Владелец записи')
            ->assertSee('@entry_owner')
            ->assertSee('href="'.route('games.show', $catalogGame).'"', false)
            ->assertSee('Страница игры')
            ->assertSee('<strong>Моя личная запись</strong>, а не описание из каталога.', false)
            ->assertSee('9 / 10')
            ->assertSee('<strong>Найт-Сити</strong> забрал ещё сотню часов жизни.', false)
            ->assertSee('Сохранить описание')
            ->assertSee('Сохранить мнение')
            ->assertSee('data-markdown-editor', false)
            ->assertSee('data-preview-url="'.route('game-reviews.preview').'"', false);

        $this->actingAs($owner)->get(route('games.show', $catalogGame))
            ->assertOk()
            ->assertSee('Ваша оценка')
            ->assertSee('Ваше мнение')
            ->assertSee('<strong>Найт-Сити</strong> забрал ещё сотню часов жизни.', false);
    }

    public function test_manual_game_cannot_be_rated_or_reviewed_until_it_is_linked_to_catalog(): void
    {
        $owner = User::factory()->create();
        $game = $this->gameFor($owner, true);

        $this->actingAs($owner)->get(route('games.view', $game))
            ->assertOk()
            ->assertSee('Доступна после привязки игры к каталогу.')
            ->assertSee('Мнение можно оставить только для игры, которая есть в каталоге.')
            ->assertDontSee('Сохранить мнение');
    }

    public function test_legacy_entry_reviews_are_migrated_to_catalog_reviews(): void
    {
        $owner = User::factory()->create();
        $catalogGame = CatalogGame::query()->create([
            'hltb_id' => 1337,
            'title' => 'Legacy Game',
            'normalized_title' => 'legacy game',
        ]);
        $game = $this->gameFor($owner, true, [
            'catalog_game_id' => $catalogGame->id,
            'title' => 'Legacy Game',
            'normalized_title' => 'legacy game',
        ]);
        DB::table('games')->where('id', $game->id)->update([
            'owner_rating' => 8,
            'owner_opinion' => 'Старое мнение из записи.',
        ]);

        $migration = require database_path('migrations/2026_07_20_000006_migrate_game_personal_reviews_to_catalog_reviews.php');
        $migration->up();

        $this->assertDatabaseHas('game_reviews', [
            'user_id' => $owner->id,
            'catalog_game_id' => $catalogGame->id,
            'rating' => 8,
            'body' => 'Старое мнение из записи.',
        ]);
    }

    public function test_private_game_entry_is_only_available_to_its_owner(): void
    {
        $owner = User::factory()->create();
        $visitor = User::factory()->create();
        $game = $this->gameFor($owner, false);

        $this->get(route('games.view', $game))->assertForbidden();
        $this->actingAs($visitor)->get(route('games.view', $game))->assertForbidden();
        $this->actingAs($owner)->get(route('games.view', $game))->assertOk();
    }

    public function test_owner_can_upload_screenshots_and_game_deletion_removes_them(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $visitor = User::factory()->create();
        $game = $this->gameFor($owner, true);

        $this->actingAs($visitor)->post(route('games.screenshots.store', $game), [
            'screenshots' => [UploadedFile::fake()->image('not-yours.jpg', 1200, 800)],
        ])->assertForbidden();

        $this->actingAs($owner)->post(route('games.screenshots.store', $game), [
            'screenshots' => [
                UploadedFile::fake()->image('one.jpg', 1800, 1200),
                UploadedFile::fake()->image('two.png', 1200, 800),
            ],
        ])->assertRedirect();

        $screenshots = $game->screenshots()->get();
        $this->assertCount(2, $screenshots);
        $screenshots->each(fn (GameScreenshot $screenshot) => Storage::disk('public')->assertExists($screenshot->path));
        $this->get(route('games.view', $game))
            ->assertOk()
            ->assertSee('data-screenshot-open', false)
            ->assertSee('data-screenshot-modal', false)
            ->assertSee('data-screenshot-url="'.$screenshots->first()->url.'"', false);

        $this->actingAs($owner)->delete(route('games.destroy', $game))->assertRedirect();

        $this->assertDatabaseMissing('games', ['id' => $game->id]);
        $this->assertDatabaseCount('game_screenshots', 0);
        $screenshots->each(fn (GameScreenshot $screenshot) => Storage::disk('public')->assertMissing($screenshot->path));
    }

    public function test_comments_notify_recipients_and_owner_can_hide_them(): void
    {
        $owner = User::factory()->create(['login' => 'owner']);
        $author = User::factory()->create(['login' => 'author']);
        $stranger = User::factory()->create(['login' => 'stranger']);
        $game = $this->gameFor($owner, true);

        $this->actingAs($author)->post(route('games.comments.store', $game), [
            'body' => 'Очень хочу увидеть продолжение истории.',
        ])->assertRedirect(route('games.view', $game).'#comment-1');

        $comment = GameComment::query()->firstOrFail();
        $this->assertTrue($owner->notifications()->get()->contains(
            fn ($notification): bool => $notification->data['event'] === 'game_comment'
                && $notification->data['game_id'] === $game->id
                && $notification->data['game_comment_id'] === $comment->id,
        ));

        $this->actingAs($owner)->post(route('games.comments.store', $game), [
            'parent_id' => $comment->id,
            'body' => 'Я тоже. Жду с геймпадом наготове.',
        ])->assertRedirect(route('games.view', $game).'#comment-2');

        $this->assertTrue($author->notifications()->get()->contains(
            fn ($notification): bool => $notification->data['event'] === 'game_comment_reply',
        ));
        $this->assertFalse($owner->notifications()->get()->contains(
            fn ($notification): bool => $notification->data['event'] === 'game_comment_reply',
        ));

        $this->actingAs($stranger)->patch(route('games.comments.visibility', [$game, $comment]))->assertForbidden();
        $this->actingAs($owner)->patch(route('games.comments.visibility', [$game, $comment]))->assertRedirect();
        $comment->refresh();
        $this->assertNotNull($comment->hidden_at);

        $this->actingAs($stranger)->get(route('games.view', $game))
            ->assertOk()
            ->assertDontSee('Очень хочу увидеть продолжение истории.');
        $this->actingAs($owner)->get(route('games.view', $game))
            ->assertOk()
            ->assertSee('Очень хочу увидеть продолжение истории.')
            ->assertSee('Скрыт');
        $this->actingAs($author)->get(route('games.view', $game))
            ->assertOk()
            ->assertSee('Очень хочу увидеть продолжение истории.')
            ->assertDontSee('Скрыт');

        $this->actingAs($stranger)->post(route('games.comments.store', $game), [
            'parent_id' => $comment->id,
            'body' => 'Мне нельзя отвечать на скрытый комментарий.',
        ])->assertForbidden();

        $this->actingAs($owner)->delete(route('games.destroy', $game))->assertRedirect();
        $this->assertDatabaseCount('game_comments', 0);
        $this->assertFalse($owner->notifications()->get()->contains(
            fn ($notification): bool => ($notification->data['game_id'] ?? null) === $game->id,
        ));
        $this->assertFalse($author->notifications()->get()->contains(
            fn ($notification): bool => ($notification->data['game_id'] ?? null) === $game->id,
        ));
    }

    /** @param array<string, mixed> $attributes */
    private function gameFor(User $owner, bool $isPublic, array $attributes = []): Game
    {
        $list = $owner->gameLists()->create([
            'name' => 'Games',
            'slug' => 'games-'.uniqid(),
            'default_platform' => 'pc',
            'is_public' => $isPublic,
        ]);

        return $list->games()->create(array_merge([
            'title' => 'Control',
            'normalized_title' => 'control',
            'status' => 'playing',
            'platform' => 'pc',
        ], $attributes));
    }
}
