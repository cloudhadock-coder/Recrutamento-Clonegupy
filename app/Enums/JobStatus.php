<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum JobStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Published = 'published';
    case Paused = 'paused';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Published => 'Publicada',
            self::Paused => 'Pausada',
            self::Closed => 'Encerrada',
        };
    }
}
