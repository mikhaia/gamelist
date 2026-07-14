<?php

namespace App\Services;

use Illuminate\Support\Str;

class GameTitleNormalizer
{
    public function normalize(string $title): string
    {
        return Str::of($title)->squish()->lower()->toString();
    }
}
