<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Services\CoverImageService;
use App\Services\GameAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class GameScreenshotController extends Controller
{
    private const MAX_SCREENSHOTS = 12;

    public function __construct(
        private readonly CoverImageService $images,
        private readonly GameAccess $access,
    ) {}

    public function store(Request $request, Game $game): RedirectResponse
    {
        $this->access->authorizeOwner($request->user(), $game);
        $validated = $request->validate([
            'screenshots' => ['required', 'array', 'min:1', 'max:'.self::MAX_SCREENSHOTS],
            'screenshots.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
        ]);
        $files = $validated['screenshots'];

        if ($game->screenshots()->count() + count($files) > self::MAX_SCREENSHOTS) {
            throw ValidationException::withMessages([
                'screenshots' => 'У одной записи можно сохранить не больше '.self::MAX_SCREENSHOTS.' скриншотов.',
            ]);
        }

        $paths = [];
        $nextOrder = ((int) $game->screenshots()->max('sort_order')) + 1;

        try {
            foreach ($files as $file) {
                $path = $this->images->storeScreenshot($file);
                $paths[] = $path;
                $game->screenshots()->create(['path' => $path, 'sort_order' => $nextOrder++]);
            }
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($paths);

            throw $exception;
        }

        return back()->with('success', 'Скриншоты загружены.');
    }

    public function destroy(Request $request, Game $game, GameScreenshot $screenshot): RedirectResponse
    {
        $this->access->authorizeOwner($request->user(), $game);
        abort_unless($screenshot->game_id === $game->id, 404);

        Storage::disk('public')->delete($screenshot->path);
        $screenshot->delete();

        return back()->with('success', 'Скриншот удалён.');
    }
}
