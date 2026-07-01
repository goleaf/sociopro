<?php

namespace App\Enums\Concerns;

trait HasValues
{
    /**
     * @return list<string|int>
     */
    public static function values(): array
    {
        return array_map(
            static fn ($case): string|int => $case->value,
            self::cases()
        );
    }
}
