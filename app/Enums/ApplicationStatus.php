<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ApplicationStatus: string
{
    use HasOptions;

    case Active = 'active';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Em andamento',
            self::Rejected => 'Reprovada',
        };
    }
}
