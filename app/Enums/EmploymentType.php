<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum EmploymentType: string
{
    use HasOptions;

    case Clt = 'clt';
    case Pj = 'pj';
    case Internship = 'estagio';
    case Temporary = 'temporario';

    public function label(): string
    {
        return match ($this) {
            self::Clt => 'CLT',
            self::Pj => 'PJ',
            self::Internship => 'Estágio',
            self::Temporary => 'Temporário',
        };
    }
}
