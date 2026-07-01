<?php

namespace App\Support\Validation;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final class DateTimeRules
{
    public const BROWSER_DATE_FORMAT = 'Y-m-d';

    public const BROWSER_TIME_FORMAT = 'H:i';

    public const BROWSER_DATETIME_LOCAL_FORMAT = 'Y-m-d\TH:i';

    private const MIN_BIRTH_DATE = '1900-01-01';

    /**
     * @return list<string>
     */
    public static function requiredBrowserDate(): array
    {
        return ['required', 'date_format:'.self::BROWSER_DATE_FORMAT];
    }

    /**
     * @return list<string>
     */
    public static function nullableBrowserDate(): array
    {
        return ['nullable', 'date_format:'.self::BROWSER_DATE_FORMAT];
    }

    /**
     * @return list<string>
     */
    public static function requiredBrowserTime(): array
    {
        return ['required', 'date_format:'.self::BROWSER_TIME_FORMAT];
    }

    /**
     * @return list<string>
     */
    public static function requiredDateRangeEnd(string $startField = 'start_date'): array
    {
        return ['required', 'date_format:'.self::BROWSER_DATE_FORMAT, 'after_or_equal:'.$startField];
    }

    /**
     * @return list<string>
     */
    public static function requiredBirthDate(): array
    {
        return [
            'required',
            'date_format:'.self::BROWSER_DATE_FORMAT,
            'after_or_equal:'.self::MIN_BIRTH_DATE,
            'before_or_equal:today',
        ];
    }

    /**
     * @return list<string>
     */
    public static function nullableBirthDate(): array
    {
        return [
            'nullable',
            'date_format:'.self::BROWSER_DATE_FORMAT,
            'after_or_equal:'.self::MIN_BIRTH_DATE,
            'before_or_equal:today',
        ];
    }

    /**
     * @return list<string>
     */
    public static function nullableBrowserDateTimeLocal(): array
    {
        return ['nullable', 'date_format:'.self::BROWSER_DATETIME_LOCAL_FORMAT];
    }

    /**
     * @return list<string>
     */
    public static function nullableTimezone(): array
    {
        return ['nullable', 'timezone'];
    }

    public static function birthDateTimestamp(string $date): int
    {
        return self::browserDate($date)->timestamp;
    }

    public static function browserDateAtCurrentTime(string $date): string
    {
        $time = now(self::appTimezone())->format('H:i:s');

        return self::dateTimeFromFormat(
            self::BROWSER_DATE_FORMAT.' H:i:s',
            $date.' '.$time
        )->format('Y-m-d H:i:s');
    }

    public static function dateTimeLocalToDatabase(?string $dateTime): ?string
    {
        if ($dateTime === null || $dateTime === '') {
            return null;
        }

        return self::dateTimeFromFormat(self::BROWSER_DATETIME_LOCAL_FORMAT, $dateTime)
            ->format('Y-m-d H:i:s');
    }

    public static function timezoneOrDefault(?string $timezone): string
    {
        return $timezone ?: self::appTimezone();
    }

    public static function browserDate(string $date): CarbonImmutable
    {
        return self::dateTimeFromFormat('!'.self::BROWSER_DATE_FORMAT, $date);
    }

    private static function dateTimeFromFormat(string $format, string $value): CarbonImmutable
    {
        $dateTime = CarbonImmutable::createFromFormat($format, $value, self::appTimezone());

        if (! $dateTime instanceof CarbonImmutable) {
            throw new InvalidArgumentException('Invalid date or time value.');
        }

        return $dateTime;
    }

    private static function appTimezone(): string
    {
        return (string) config('app.timezone');
    }
}
