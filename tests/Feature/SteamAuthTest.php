<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SteamAuthTest extends TestCase
{
    use RefreshDatabase;

    private const STEAM_ID = '76561198000000001';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_login_page_starts_steam_openid_authentication(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-steam-login', false)
            ->assertSee(asset('images/steam/sign-in.svg'))
            ->assertSee('w-[280px]', false);

        $response = $this->get(route('steam.redirect'))->assertRedirect();
        parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->assertSame('checkid_setup', $query['openid_mode']);
        $this->assertSame('http://specs.openid.net/auth/2.0/identifier_select', $query['openid_identity']);
        $this->assertStringStartsWith(route('steam.callback').'?state=', $query['openid_return_to']);
        $response->assertSessionHas('steam_openid.state');
        $response->assertSessionHas('steam_openid.action', 'login');
    }

    public function test_registration_page_offers_steam_registration(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('data-steam-register', false)
            ->assertSee('Зарегистрироваться через Steam')
            ->assertSee('href="'.route('steam.redirect').'"', false);
    }

    public function test_existing_user_can_login_through_verified_steam_id(): void
    {
        Carbon::setTestNow('2026-07-23 12:00:00');

        try {
            $user = User::factory()->create(['steam_id' => self::STEAM_ID]);
            Http::fake([
                'https://steamcommunity.com/openid/login' => Http::response("ns:http://specs.openid.net/auth/2.0\nis_valid:true\n"),
            ]);

            $response = $this->withSession($this->steamSession('login'))
                ->get(route('steam.callback', $this->openidResponse()));

            $response->assertRedirect(route('lists.index'))
                ->assertSessionHas('success', __('app.messages.steam_login_complete'));
            $this->assertAuthenticatedAs($user);
            Http::assertSent(fn ($request): bool => $request['openid.mode'] === 'check_authentication'
                && $request['openid.claimed_id'] === 'https://steamcommunity.com/openid/id/'.self::STEAM_ID);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_first_steam_login_creates_account_without_local_password(): void
    {
        Carbon::setTestNow('2026-07-23 12:00:00');

        try {
            Http::fake([
                'https://steamcommunity.com/openid/login' => Http::response("is_valid:true\n"),
            ]);

            $this->withSession($this->steamSession('login'))
                ->get(route('steam.callback', $this->openidResponse()))
                ->assertRedirect(route('lists.index'));

            $user = User::query()->where('steam_id', self::STEAM_ID)->firstOrFail();
            $this->assertAuthenticatedAs($user);
            $this->assertSame('steam_'.self::STEAM_ID, $user->login);
            $this->assertNull($user->password);

            $this->get(route('settings.edit'))
                ->assertOk()
                ->assertSee('Создать пароль')
                ->assertSee(self::STEAM_ID);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_authenticated_user_can_link_an_available_steam_account(): void
    {
        Carbon::setTestNow('2026-07-23 12:00:00');

        try {
            $user = User::factory()->create();
            Http::fake([
                'https://steamcommunity.com/openid/login' => Http::response("is_valid:true\n"),
            ]);

            $this->actingAs($user)
                ->withSession($this->steamSession('link'))
                ->get(route('steam.callback', $this->openidResponse()))
                ->assertRedirect(route('settings.edit'))
                ->assertSessionHas('success', __('app.messages.steam_connected'));

            $this->assertSame(self::STEAM_ID, $user->fresh()->steam_id);
            $this->assertNotNull($user->fresh()->steam_connected_at);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_steam_account_cannot_be_linked_to_two_users(): void
    {
        Carbon::setTestNow('2026-07-23 12:00:00');

        try {
            User::factory()->create(['steam_id' => self::STEAM_ID]);
            $user = User::factory()->create();
            Http::fake([
                'https://steamcommunity.com/openid/login' => Http::response("is_valid:true\n"),
            ]);

            $this->actingAs($user)
                ->withSession($this->steamSession('link'))
                ->get(route('steam.callback', $this->openidResponse()))
                ->assertRedirect(route('settings.edit'))
                ->assertSessionHasErrors('steam');

            $this->assertNull($user->fresh()->steam_id);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_invalid_steam_verification_is_rejected(): void
    {
        Carbon::setTestNow('2026-07-23 12:00:00');

        try {
            Http::fake([
                'https://steamcommunity.com/openid/login' => Http::response("is_valid:false\n"),
            ]);

            $this->withSession($this->steamSession('login'))
                ->get(route('steam.callback', $this->openidResponse()))
                ->assertRedirect(route('login'))
                ->assertSessionHasErrors('steam');

            $this->assertGuest();
            $this->assertDatabaseMissing('users', ['steam_id' => self::STEAM_ID]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_steam_only_user_must_create_password_before_unlinking(): void
    {
        $user = User::factory()->create([
            'password' => null,
            'steam_id' => self::STEAM_ID,
            'steam_connected_at' => now(),
        ]);

        $this->actingAs($user)->delete(route('settings.steam.destroy'))
            ->assertSessionHasErrors('steam');
        $this->assertSame(self::STEAM_ID, $user->fresh()->steam_id);

        $this->actingAs($user)->patch(route('settings.password'), [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect();
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));

        $this->actingAs($user)->delete(route('settings.steam.destroy'))
            ->assertRedirect()
            ->assertSessionHas('success', __('app.messages.steam_disconnected'));
        $this->assertNull($user->fresh()->steam_id);
    }

    /** @return array<string, string> */
    private function steamSession(string $action): array
    {
        return [
            'steam_openid.state' => 'test-state',
            'steam_openid.action' => $action,
        ];
    }

    /** @return array<string, string> */
    private function openidResponse(): array
    {
        $claimedId = 'https://steamcommunity.com/openid/id/'.self::STEAM_ID;

        return [
            'state' => 'test-state',
            'openid_ns' => 'http://specs.openid.net/auth/2.0',
            'openid_mode' => 'id_res',
            'openid_op_endpoint' => 'https://steamcommunity.com/openid/login',
            'openid_claimed_id' => $claimedId,
            'openid_identity' => $claimedId,
            'openid_return_to' => route('steam.callback').'?state=test-state',
            'openid_response_nonce' => now()->utc()->format('Y-m-d\TH:i:s\Z').'test-nonce',
            'openid_assoc_handle' => 'test-association',
            'openid_signed' => 'op_endpoint,claimed_id,identity,return_to,response_nonce,assoc_handle',
            'openid_sig' => 'test-signature',
        ];
    }
}
