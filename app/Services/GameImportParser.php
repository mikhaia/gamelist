<?php

namespace App\Services;

class GameImportParser
{
    public function __construct(private readonly GameTitleNormalizer $normalizer) {}

    /** @return array<int, array{title: string, normalized_title: string, duplicate_in_input: bool}> */
    public function parse(string $text): array
    {
        $items = [];
        $seen = [];

        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $title = preg_replace('/^\s*(?:(?:[-*+]\s*)?(?:\[[ xX]\]\s*)|(?:\d+[.)]\s*)|(?:[-*+]\s*))+/u', '', $line) ?? $line;
            $title = trim($title);

            if ($title === '') {
                continue;
            }

            $normalized = $this->normalizer->normalize($title);
            $items[] = [
                'title' => $title,
                'normalized_title' => $normalized,
                'duplicate_in_input' => isset($seen[$normalized]),
            ];
            $seen[$normalized] = true;
        }

        return $items;
    }
}
