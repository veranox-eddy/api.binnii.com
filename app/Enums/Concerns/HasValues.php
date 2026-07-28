<?php

namespace App\Enums\Concerns;

trait HasValues
{
    /**
     * All case values, e.g. for enum column definitions in migrations.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
