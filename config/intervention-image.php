<?php

use Intervention\Image\Drivers\Gd\Driver;

return [
    'driver' => env('IMAGE_DRIVER', Driver::class),

    'options' => [
        'autoOrientation' => true,
        'decodeAnimation' => false,
        'backgroundColor' => 'ffffff',
        'strip' => true,
    ],
];
