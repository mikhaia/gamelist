<?php

namespace App\Services;

use Illuminate\Support\Str;

class ReviewMarkdown
{
    public function render(?string $markdown): string
    {
        if (blank($markdown)) {
            return '';
        }

        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
