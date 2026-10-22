<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum WorkModel: string
{
    use HasOptions;

    case Onsite = 'presencial';
    case Hybrid = 'hibrido';
    case Remote = 'remoto';

    public function label(): string
    {
        return match ($this) {
            self::Onsite => 'Presencial',
            self::Hybrid => 'Híbrido',
            self::Remote => 'Remoto',
        };
    }
}
