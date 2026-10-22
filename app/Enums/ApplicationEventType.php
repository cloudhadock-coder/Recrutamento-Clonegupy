<?php

namespace App\Enums;

enum ApplicationEventType: string
{
    case Applied = 'applied';
    case StageChanged = 'stage_changed';
    case Rejected = 'rejected';
    case Reactivated = 'reactivated';

    public function label(): string
    {
        return match ($this) {
            self::Applied => 'Candidatura recebida',
            self::StageChanged => 'Mudança de etapa',
            self::Rejected => 'Reprovação',
            self::Reactivated => 'Candidatura reativada',
        };
    }
}
