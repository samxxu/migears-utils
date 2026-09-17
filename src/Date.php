<?php

declare(strict_types=1);

namespace MiGears\Utils;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Date utility with friendly formatting, relative time, and timezone handling.
 *
 * All output is in English. Internationalization is left to the caller.
 */
final class Date
{
    private readonly DateTimeImmutable $datetime;

    /**
     * @param DateTimeInterface|int|string|null $input Timestamp, datetime string, DateTime, or null for "now"
     * @param DateTimeZone|string|null $timezone Timezone for interpretation (null uses default)
     */
    public function __construct(
        DateTimeInterface|int|string|null $input = null,
        DateTimeZone|string|null $timezone = null,
    ) {
        $tz = $this->resolveTimezone($timezone);

        $dt = match (true) {
            $input === null => new DateTimeImmutable('now', $tz),
            $input instanceof DateTimeInterface => DateTimeImmutable::createFromInterface($input),
            is_int($input) => (new DateTimeImmutable('now', $tz))->setTimestamp($input),
            is_string($input) => new DateTimeImmutable($input, $tz),
        };

        if ($tz !== null && !($input instanceof DateTimeInterface && $input->getTimezone()->getName() === $tz->getName())) {
            $dt = $dt->setTimezone($tz);
        }

        $this->datetime = $dt;
    }

    /** Create a Date from a Unix timestamp. */
    public static function fromTimestamp(int $timestamp, DateTimeZone|string|null $timezone = null): self
    {
        return new self($timestamp, $timezone);
    }

    /** Create a Date from a datetime string. */
    public static function fromString(string $datetime, DateTimeZone|string|null $timezone = null): self
    {
        return new self($datetime, $timezone);
    }

    /** Get the underlying DateTimeImmutable instance. */
    public function toDateTime(): DateTimeImmutable
    {
        return $this->datetime;
    }

    /** Get the Unix timestamp. */
    public function timestamp(): int
    {
        return $this->datetime->getTimestamp();
    }

    /** Format as date string: Y-m-d */
    public function toDateString(): string
    {
        return $this->datetime->format('Y-m-d');
    }

    /** Format as datetime string: Y-m-d H:i */
    public function toTimeString(): string
    {
        return $this->datetime->format('Y-m-d H:i');
    }

    /** Format using an arbitrary date pattern. */
    public function format(string $pattern): string
    {
        return $this->datetime->format($pattern);
    }

    /** Day of week as integer (0 = Sunday, 6 = Saturday). */
    public function dayOfWeek(): int
    {
        return (int) $this->datetime->format('w');
    }

    /** Whether this date is today. */
    public function isToday(): bool
    {
        $today = new DateTimeImmutable('today', $this->datetime->getTimezone());
        return $this->datetime >= $today && $this->datetime < $today->modify('+1 day');
    }

    /** Whether this date is yesterday. */
    public function isYesterday(): bool
    {
        $yesterday = new DateTimeImmutable('yesterday', $this->datetime->getTimezone());
        return $this->datetime >= $yesterday && $this->datetime < $yesterday->modify('+1 day');
    }

    /** Whether this date is tomorrow. */
    public function isTomorrow(): bool
    {
        $tomorrow = new DateTimeImmutable('tomorrow', $this->datetime->getTimezone());
        return $this->datetime >= $tomorrow && $this->datetime < $tomorrow->modify('+1 day');
    }

    /**
     * Human-readable relative time (e.g. "2 hours ago", "in 3 days").
     * Output is always in English.
     */
    public function relativeTime(): string
    {
        $now = new DateTimeImmutable('now', $this->datetime->getTimezone());
        $diff = $this->datetime->diff($now);
        $isPast = $this->datetime < $now;
        $secs = abs($now->getTimestamp() - $this->datetime->getTimestamp());

        return match (true) {
            $secs < 60 => $isPast ? 'just now' : 'in a moment',
            $secs < 3600 => $this->pluralize((int) floor($secs / 60), 'minute', $isPast),
            $secs < 86400 => $this->pluralize((int) floor($secs / 3600), 'hour', $isPast),
            $secs < 604800 => $this->pluralize((int) floor($secs / 86400), 'day', $isPast),
            $secs < 2592000 => $this->pluralize((int) floor($secs / 604800), 'week', $isPast),
            $diff->y === 0 => $this->pluralize($diff->m, 'month', $isPast),
            default => $this->pluralize($diff->y, 'year', $isPast),
        };
    }

    /**
     * Friendly human-readable datetime string.
     *
     * Recent: "Today 14:30", "Yesterday 09:15", "Monday 14:30"
     * This year: "Jan 15 14:30"
     * Older: "Jan 15, 2024 14:30"
     */
    public function humanize(): string
    {
        $now = new DateTimeImmutable('now', $this->datetime->getTimezone());
        $diffDays = (int) floor(abs($now->getTimestamp() - $this->datetime->getTimestamp()) / 86400);
        $time = $this->datetime->format('H:i');

        return match (true) {
            $this->isToday() => "Today {$time}",
            $this->isYesterday() => "Yesterday {$time}",
            $diffDays <= 6 => $this->datetime->format('l') . " {$time}",
            $this->isSameYear($now) => $this->datetime->format('M j') . " {$time}",
            default => $this->datetime->format('M j, Y') . " {$time}",
        };
    }

    /** Return a new Date instance converted to the given timezone. */
    public function withTimezone(DateTimeZone|string $timezone): self
    {
        $tz = $this->resolveTimezone($timezone);
        return new self($this->datetime->setTimezone($tz), $tz);
    }

    /** Get the current timezone. */
    public function timezone(): DateTimeZone
    {
        return $this->datetime->getTimezone();
    }

    private function isSameYear(DateTimeImmutable $other): bool
    {
        return $this->datetime->format('Y') === $other->format('Y');
    }

    private function pluralize(int $count, string $unit, bool $isPast): string
    {
        $word = $count === 1 ? $unit : "{$unit}s";
        return $isPast ? "{$count} {$word} ago" : "in {$count} {$word}";
    }

    private function resolveTimezone(DateTimeZone|string|null $timezone): ?DateTimeZone
    {
        if ($timezone === null) {
            return null;
        }
        if ($timezone instanceof DateTimeZone) {
            return $timezone;
        }
        $tz = @timezone_open($timezone);
        if ($tz === false) {
            throw new InvalidArgumentException("Invalid timezone: {$timezone}");
        }
        return $tz;
    }
}
