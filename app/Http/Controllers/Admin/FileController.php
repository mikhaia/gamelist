<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminFileService;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function __construct(private readonly AdminFileService $files) {}

    public function index(?string $type = null): View
    {
        $type ??= 'screenshots';
        abort_unless(array_key_exists($type, $this->files->types()), 404);

        return view('admin.files.index', [
            'type' => $type,
            'types' => $this->files->types(),
            'files' => $this->files->paginate($type),
        ]);
    }

    public function download(string $type, int $id): StreamedResponse
    {
        $path = $this->files->downloadPath($type, $id);
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->download($path, basename($path));
    }
}
