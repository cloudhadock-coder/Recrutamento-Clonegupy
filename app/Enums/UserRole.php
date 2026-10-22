<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum UserRole: string
{
    use HasOptions;

    case Admin = 'admin';
    case Recruiter = 'recruiter';
    case Manager = 'manager';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Recruiter => 'Recrutador',
            self::Manager => 'Gestor',
        };
    }

    /**
     * Gestores apenas acompanham o processo; admins e recrutadores operam vagas e candidaturas.
     */
    public function canManageHiring(): bool
    {
        return $this !== self::Manager;
    }
}
