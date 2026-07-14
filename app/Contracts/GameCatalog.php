<?php

namespace App\Contracts;

interface GameCatalog
{
    /** @return array<int, array<string, int|string|null>> */
    public function search(string $query, int $limit = 8): array;
}
