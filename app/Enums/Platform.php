<?php

namespace App\Enums;

enum Platform: string
{
    case NintendoSwitch = 'nintendo_switch';
    case Steam = 'steam';
    case Pc = 'pc';

    public function label(): string
    {
        return __("app.platforms.{$this->value}");
    }
}
