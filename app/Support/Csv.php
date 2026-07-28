<?php

namespace App\Support;

/**
 * CSV cell hardening: user-entered text (names, notes, captions) must not
 * execute as a formula when the export opens in Excel/Sheets.
 */
class Csv
{
    /**
     * Write one sanitized row to an open stream.
     *
     * @param  resource  $stream
     * @param  array<int, mixed>  $row
     */
    public static function row($stream, array $row): void
    {
        fputcsv($stream, array_map(self::sanitize(...), $row));
    }

    /** Prefix formula-triggering leading characters with a quote. */
    public static function sanitize(mixed $cell): mixed
    {
        if (is_string($cell) && $cell !== '' && str_contains('=+-@'."\t\r", $cell[0])) {
            return "'".$cell;
        }

        return $cell;
    }
}
