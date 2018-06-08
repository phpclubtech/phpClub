<?php

declare(strict_types=1);

namespace phpClub\ThreadParser;

use phpClub\ThreadParser\Exception\DateParseException;

class DateConverter
{
    /**
     * @var \DateTimeZone
     */
    private $timeZone;

    /**
     * @param \DateTimeZone $timeZone The timezone that date belongs to.
     */
    public function __construct(\DateTimeZone $timeZone)
    {
        $this->timeZone = $timeZone;
    }

    /**
     * @param string $date
     *
     * @throws \Exception
     *
     * @return \DateTimeImmutable
     */
    public function toDateTime(string $date): \DateTimeImmutable
    {
        $normalized = $this->normalizeDate($date);

        $dateTime = \DateTimeImmutable::createFromFormat(
            'd M Y H:i:s',
            $normalized,
            $this->timeZone
        );

        if ($dateTime !== false) {
            return $dateTime;
        }

        $dateTime = \DateTimeImmutable::createFromFormat(
            'd/m/y  H:i:s',
            $normalized,
            $this->timeZone
        );

        if ($dateTime !== false) {
            return $dateTime;
        }

        throw new DateParseException("Unable to parse date: {$date}");
    }

    /**
     * Parses datetime like '02 Май, 19:34' from m2-ch.ru.
     */
    public function parseMDvachDate(string $rawDate, int $year): \DateTimeInterface
    {
        if (!preg_match("/(\d+)\s+(\w+),\s*(\d+):(\d+)/u", $rawDate, $matches)) {
            throw new DateParseException("Invalid date format: '$rawDate'");
        }

        [, $day, $month, $hours, $minutes] = $matches;

        $monthNames = [
            'Янв' => 1,
            'Фев' => 2,
            'Мар' => 3,
            'Апр' => 4,
            'Май' => 5,
            'Июн' => 6,
            'Июл' => 7,
            'Авг' => 8,
            'Сен' => 9,
            'Окт' => 10,
            'Ноя' => 11,
            'Дек' => 12,
        ];

        $month = $monthNames[$month];

        // m2-ch always uses Moscow timezone
        $mskTimezone = new \DateTimeZone('Europe/Moscow');
        $dateTime = new \DateTimeImmutable("$year-$month-$day $hours:$minutes", $mskTimezone);

        return $dateTime;
    }

    /**
     * @param string $date
     *
     * @return string
     */
    private function normalizeDate(string $date): string
    {
        $rusToEng = [
            'Янв' => 'Jan',
            'Фев' => 'Feb',
            'Мар' => 'Mar',
            'Апр' => 'Apr',
            'Май' => 'May',
            'Июн' => 'Jun',
            'Июл' => 'Jul',
            'Авг' => 'Aug',
            'Сен' => 'Sep',
            'Окт' => 'Oct',
            'Ноя' => 'Nov',
            'Дек' => 'Dec',
        ];

        $withEngMonths = strtr($date, $rusToEng);

        return trim(preg_replace('/[^a-z\d\s:\/]+/i', '', $withEngMonths));
    }
}
