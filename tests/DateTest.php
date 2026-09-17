<?php

declare(strict_types=1);

namespace MiGears\Utils\Tests;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use MiGears\Utils\Date;

class DateTest extends TestCase
{
    // --- Construction ---

    public function testConstructWithNullGivesNow(): void
    {
        $date = new Date();
        $now = time();

        $this->assertGreaterThanOrEqual($now - 1, $date->timestamp());
        $this->assertLessThanOrEqual($now + 1, $date->timestamp());
    }

    public function testConstructWithTimestamp(): void
    {
        $ts = 1704067200; // 2024-01-01 00:00:00 UTC
        $date = new Date($ts);

        $this->assertSame($ts, $date->timestamp());
    }

    public function testConstructWithString(): void
    {
        $date = new Date('2024-01-15 14:30:00');

        $this->assertSame('2024-01-15', $date->toDateString());
        $this->assertSame('2024-01-15 14:30', $date->toTimeString());
    }

    public function testConstructWithDateTimeInterface(): void
    {
        $dt = new DateTimeImmutable('2024-06-15 10:00:00');
        $date = new Date($dt);

        $this->assertSame($dt->getTimestamp(), $date->timestamp());
    }

    public function testFromTimestamp(): void
    {
        $date = Date::fromTimestamp(1704067200);

        $this->assertSame(1704067200, $date->timestamp());
    }

    public function testFromString(): void
    {
        $date = Date::fromString('2024-03-20 08:00:00');

        $this->assertSame('2024-03-20', $date->toDateString());
    }

    // --- Formatting ---

    public function testToDateString(): void
    {
        $date = new Date('2024-07-04 12:00:00');

        $this->assertSame('2024-07-04', $date->toDateString());
    }

    public function testToTimeString(): void
    {
        $date = new Date('2024-07-04 14:30:00');

        $this->assertSame('2024-07-04 14:30', $date->toTimeString());
    }

    public function testFormat(): void
    {
        $date = new Date('2024-07-04 14:30:00');

        $this->assertSame('Jul 4, 2024', $date->format('M j, Y'));
    }

    public function testDayOfWeek(): void
    {
        // 2024-01-01 is a Monday (1)
        $date = new Date('2024-01-01');
        $this->assertSame(1, $date->dayOfWeek());

        // 2024-01-07 is a Sunday (0)
        $date = new Date('2024-01-07');
        $this->assertSame(0, $date->dayOfWeek());
    }

    // --- isToday / isYesterday / isTomorrow ---

    public function testIsToday(): void
    {
        $date = new Date();
        $this->assertTrue($date->isToday());

        $date = new Date('2000-01-01');
        $this->assertFalse($date->isToday());
    }

    public function testIsYesterday(): void
    {
        $yesterday = new DateTimeImmutable('yesterday');
        $date = new Date($yesterday);
        $this->assertTrue($date->isYesterday());

        $date = new Date('2000-01-01');
        $this->assertFalse($date->isYesterday());
    }

    public function testIsTomorrow(): void
    {
        $tomorrow = new DateTimeImmutable('tomorrow');
        $date = new Date($tomorrow);
        $this->assertTrue($date->isTomorrow());

        $date = new Date('2000-01-01');
        $this->assertFalse($date->isTomorrow());
    }

    // --- relativeTime ---

    public function testRelativeTimeJustNow(): void
    {
        // Use a timestamp 5 seconds in the past — should still be "just now"
        $date = new Date(time() - 5);
        $this->assertSame('just now', $date->relativeTime());
    }

    public function testRelativeTimeJustNowFuture(): void
    {
        // Very near future should be "in a moment"
        $date = new Date(time() + 5);
        $this->assertSame('in a moment', $date->relativeTime());
    }

    public function testRelativeTimeSecondsAgo(): void
    {
        $ts = time() - 30;
        $date = new Date($ts);

        $this->assertSame('just now', $date->relativeTime());
    }

    public function testRelativeTimeMinutesAgo(): void
    {
        $ts = time() - 180; // 3 minutes ago
        $date = new Date($ts);

        $this->assertSame('3 minutes ago', $date->relativeTime());
    }

    public function testRelativeTimeOneMinuteAgo(): void
    {
        $ts = time() - 90; // ~1.5 minutes
        $date = new Date($ts);

        $this->assertSame('1 minute ago', $date->relativeTime());
    }

    public function testRelativeTimeHoursAgo(): void
    {
        $ts = time() - 7200; // 2 hours ago
        $date = new Date($ts);

        $this->assertSame('2 hours ago', $date->relativeTime());
    }

    public function testRelativeTimeOneHourAgo(): void
    {
        $ts = time() - 3700; // ~1 hour
        $date = new Date($ts);

        $this->assertSame('1 hour ago', $date->relativeTime());
    }

    public function testRelativeTimeDaysAgo(): void
    {
        $ts = time() - 86400 * 3; // 3 days ago
        $date = new Date($ts);

        $this->assertSame('3 days ago', $date->relativeTime());
    }

    public function testRelativeTimeWeeksAgo(): void
    {
        $ts = time() - 86400 * 14; // 2 weeks ago
        $date = new Date($ts);

        $this->assertSame('2 weeks ago', $date->relativeTime());
    }

    public function testRelativeTimeMonthsAgo(): void
    {
        $dt = new DateTimeImmutable('-3 months');
        $date = new Date($dt);

        $this->assertSame('3 months ago', $date->relativeTime());
    }

    public function testRelativeTimeYearsAgo(): void
    {
        $dt = new DateTimeImmutable('-2 years');
        $date = new Date($dt);

        $this->assertSame('2 years ago', $date->relativeTime());
    }

    public function testRelativeTimeFuture(): void
    {
        $ts = time() + 3600; // 1 hour from now
        $date = new Date($ts);

        $this->assertSame('in 1 hour', $date->relativeTime());
    }

    public function testRelativeTimeFutureDays(): void
    {
        $ts = time() + 86400 * 5; // 5 days from now
        $date = new Date($ts);

        $this->assertSame('in 5 days', $date->relativeTime());
    }

    // --- humanize ---

    public function testHumanizeToday(): void
    {
        $date = new Date();
        $result = $date->humanize();

        $this->assertStringStartsWith('Today ', $result);
    }

    public function testHumanizeYesterday(): void
    {
        $yesterday = new DateTimeImmutable('yesterday 14:30:00');
        $date = new Date($yesterday);
        $result = $date->humanize();

        $this->assertStringStartsWith('Yesterday ', $result);
    }

    public function testHumanizeThisWeek(): void
    {
        $dt = new DateTimeImmutable('-3 days');
        $date = new Date($dt);
        $result = $date->humanize();

        // Should contain a day name like "Monday", "Tuesday", etc.
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $found = false;
        foreach ($days as $day) {
            if (str_starts_with($result, $day)) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "Expected a day name, got: {$result}");
    }

    public function testHumanizeThisYear(): void
    {
        // A date 2 months ago (same year)
        $dt = new DateTimeImmutable('-2 months');
        $date = new Date($dt);
        $result = $date->humanize();

        // Should be like "Jul 15 14:30" format
        $this->assertMatchesRegularExpression('/^[A-Z][a-z]{2} \d{1,2} \d{2}:\d{2}$/', $result);
    }

    public function testHumanizeOtherYear(): void
    {
        $date = new Date('2020-03-15 10:00:00');
        $result = $date->humanize();

        // Should be like "Mar 15, 2020 10:00" format
        $this->assertMatchesRegularExpression('/^[A-Z][a-z]{2} \d{1,2}, \d{4} \d{2}:\d{2}$/', $result);
    }

    // --- Timezone ---

    public function testWithTimezone(): void
    {
        $date = new Date('2024-01-01 12:00:00', 'UTC');
        $tokyo = $date->withTimezone('Asia/Tokyo');

        $this->assertSame('2024-01-01 21:00', $tokyo->toTimeString());
        $this->assertSame('Asia/Tokyo', $tokyo->timezone()->getName());
    }

    public function testWithTimezoneObject(): void
    {
        $date = new Date('2024-01-01 12:00:00', 'UTC');
        $tz = new DateTimeZone('America/New_York');
        $ny = $date->withTimezone($tz);

        $this->assertSame('2024-01-01 07:00', $ny->toTimeString());
    }

    public function testInvalidTimezoneThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Date('2024-01-01', 'Invalid/Timezone');
    }

    public function testTimezoneReturnsDateTimeZone(): void
    {
        $date = new Date('2024-01-01', 'UTC');
        $this->assertInstanceOf(DateTimeZone::class, $date->timezone());
    }

    // --- toDateTime ---

    public function testToDateTime(): void
    {
        $date = new Date('2024-06-15 10:30:00');
        $dt = $date->toDateTime();

        $this->assertInstanceOf(DateTimeImmutable::class, $dt);
        $this->assertSame('2024-06-15 10:30:00', $dt->format('Y-m-d H:i:s'));
    }
}
