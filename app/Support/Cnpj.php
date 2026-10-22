<?php

namespace App\Support;

class Cnpj
{
    public static function digits(?string $cnpj): string
    {
        return preg_replace('/\D/', '', (string) $cnpj);
    }

    public static function isValid(?string $cnpj): bool
    {
        $digits = self::digits($cnpj);

        if (strlen($digits) !== 14 || preg_match('/^(\d)\1{13}$/', $digits)) {
            return false;
        }

        $base = substr($digits, 0, 12);

        return $base.self::checkDigits($base) === $digits;
    }

    public static function format(string $cnpj): string
    {
        return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', self::digits($cnpj));
    }

    /**
     * Gera um CNPJ válido (somente dígitos). Usado em factories e seeders.
     */
    public static function generate(): string
    {
        $base = '';

        for ($i = 0; $i < 8; $i++) {
            $base .= random_int(0, 9);
        }

        $base .= '0001';

        return $base.self::checkDigits($base);
    }

    private static function checkDigits(string $base): string
    {
        foreach ([[5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2], [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]] as $weights) {
            $sum = 0;

            foreach ($weights as $index => $weight) {
                $sum += (int) $base[$index] * $weight;
            }

            $remainder = $sum % 11;
            $base .= $remainder < 2 ? '0' : (string) (11 - $remainder);
        }

        return substr($base, -2);
    }
}
