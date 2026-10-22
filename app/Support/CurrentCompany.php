<?php

namespace App\Support;

use App\Models\Company;

/**
 * Empresa (tenant) do request atual. Preenchida pelo middleware do painel do recrutador.
 */
class CurrentCompany
{
    private ?Company $company = null;

    public function set(?Company $company): void
    {
        $this->company = $company;
    }

    public function get(): ?Company
    {
        return $this->company;
    }

    public function id(): ?int
    {
        return $this->company?->getKey();
    }

    public function check(): bool
    {
        return $this->company !== null;
    }
}
