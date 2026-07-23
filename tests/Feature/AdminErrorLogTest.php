<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminErrorLogTest extends TestCase
{
    use RefreshDatabase;

    private string $logDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logDirectory = sys_get_temp_dir().'/gamelist-admin-logs-'.Str::random(12);
        File::ensureDirectoryExists($this->logDirectory);
        config()->set('logging.admin_viewer.path', $this->logDirectory);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        File::deleteDirectory($this->logDirectory);

        parent::tearDown();
    }

    public function test_only_admin_can_open_error_log_section(): void
    {
        $user = User::factory()->create();

        $this->get(route('admin.errors.index'))->assertRedirect(route('login'));
        $this->actingAs($user)->get(route('admin.errors.index'))->assertForbidden();
    }

    public function test_admin_can_inspect_search_and_filter_sanitized_log_entries(): void
    {
        Carbon::setTestNow('2026-07-23 18:00:00');
        $admin = User::factory()->create(['is_admin' => true]);
        $projectPath = base_path();

        File::put($this->logDirectory.'/laravel-2026-07-23.log', <<<LOG
[2026-07-23 17:30:00] production.ERROR: RuntimeException: Checkout failed {"password":"super-secret","token":"token-value","exception":"[object] (RuntimeException(code: 0): Checkout failed at {$projectPath}/app/Services/Checkout.php:42)
[stacktrace]
#0 {$projectPath}/app/Http/Controllers/CheckoutController.php(19): App\\Services\\Checkout->run()
"}
[2026-07-23 16:15:00] production.WARNING: Steam API answered slowly {"api_key":"steam-key-value"}
[2026-07-23 15:00:00] production.INFO: Routine request completed
LOG);

        $this->actingAs($admin)->get(route('admin.errors.index'))
            ->assertOk()
            ->assertSee('data-admin-error-stat="total" data-admin-value="2"', false)
            ->assertSee('data-admin-error-stat="today" data-admin-value="2"', false)
            ->assertSee('RuntimeException: Checkout failed')
            ->assertSee('Steam API answered slowly')
            ->assertSee('[project]/app/Services/Checkout.php:42')
            ->assertSee('[REDACTED]')
            ->assertDontSee('super-secret')
            ->assertDontSee('token-value')
            ->assertDontSee('steam-key-value')
            ->assertDontSee('Routine request completed')
            ->assertSee('href="'.route('admin.errors.index').'"', false);

        $this->actingAs($admin)->get(route('admin.errors.index', ['level' => 'warning']))
            ->assertOk()
            ->assertSee('Steam API answered slowly')
            ->assertDontSee('RuntimeException: Checkout failed');

        $this->actingAs($admin)->get(route('admin.errors.index', ['q' => 'CheckoutController']))
            ->assertOk()
            ->assertSee('RuntimeException: Checkout failed')
            ->assertDontSee('Steam API answered slowly');
    }
}
