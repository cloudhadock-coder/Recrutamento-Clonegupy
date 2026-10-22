<?php

namespace Database\Seeders;

/**
 * Gera um PDF mínimo, de uma página e só com texto, para currículos de exemplo e testes.
 */
class ResumePdf
{
    /**
     * @param  list<string>  $lines
     */
    public static function make(array $lines): string
    {
        $text = collect($lines)
            ->map(fn (string $line): string => '('.self::escape($line).') Tj T*')
            ->implode("\n");

        $stream = "BT /F1 12 Tf 50 790 Td 18 TL\n{$text}\nET";

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf.'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }

    private static function escape(string $line): string
    {
        $latin1 = mb_convert_encoding($line, 'Windows-1252', 'UTF-8');

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $latin1);
    }
}
