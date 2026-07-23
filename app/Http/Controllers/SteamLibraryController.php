<?php

namespace App\Http\Controllers;

use App\Exceptions\SteamLibraryException;
use App\Services\SteamLibraryImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class SteamLibraryController extends Controller
{
    public function __construct(private readonly SteamLibraryImporter $importer) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->steam_id) {
            return redirect()->route('settings.edit')->withErrors([
                'steam' => __('app.errors.steam_connection_required'),
            ]);
        }

        try {
            $result = $this->importer->import($user);
        } catch (SteamLibraryException $exception) {
            return back()
                ->withErrors(['steam_import' => $exception->getMessage()])
                ->with('steam_privacy_url', $this->privacyUrl((string) $user->steam_id));
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['steam_import' => __('app.errors.steam_import_failed')])
                ->with('steam_privacy_url', $this->privacyUrl((string) $user->steam_id));
        }

        $message = $result['created']
            ? __('app.messages.steam_library_imported', ['count' => $result['imported']])
            : __('app.messages.steam_library_exists');

        return redirect()->route('lists.show', $result['list'])->with('success', $message);
    }

    private function privacyUrl(string $steamId): string
    {
        return "https://steamcommunity.com/profiles/{$steamId}/edit/settings";
    }
}
