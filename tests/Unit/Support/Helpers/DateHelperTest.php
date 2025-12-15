<?php

declare(strict_types=1);

use App\Support\Helpers\DateHelper;
use Illuminate\Support\Facades\Date;

beforeEach(function (): void {
    Date::setTestNow('2025-01-15 12:00:00');
});

afterEach(function (): void {
    Date::setTestNow();
});

it('formats dates for humans', function (): void {
    $date = Date::parse('2025-01-15');
    $result = DateHelper::humanDate($date, 'M j, Y');

    expect($result)->toBe('Jan 15, 2025');
});

it('returns null for null dates', function (): void {
    expect(DateHelper::humanDate(null))->toBeNull();
});

it('calculates relative time', function (): void {
    $date = Date::now()->subHours(2);
    $result = DateHelper::ago($date);

    expect($result)->toContain('2 hours ago');
});

it('checks if date is in past', function (): void {
    $past = Date::now()->subDay();
    $future = Date::now()->addDay();

    expect(DateHelper::isPast($past))->toBeTrue();
    expect(DateHelper::isPast($future))->toBeFalse();
});

it('checks if date is in future', function (): void {
    $past = Date::now()->subDay();
    $future = Date::now()->addDay();

    expect(DateHelper::isFuture($future))->toBeTrue();
    expect(DateHelper::isFuture($past))->toBeFalse();
});

it('checks if date is today', function (): void {
    $today = Date::now();
    $yesterday = Date::now()->subDay();

    expect(DateHelper::isToday($today))->toBeTrue();
    expect(DateHelper::isToday($yesterday))->toBeFalse();
});

it('gets start of day', function (): void {
    $date = Date::parse('2025-01-15 14:30:00');
    $result = DateHelper::startOfDay($date);

    expect($result->format('H:i:s'))->toBe('00:00:00');
});

it('gets end of day', function (): void {
    $date = Date::parse('2025-01-15 14:30:00');
    $result = DateHelper::endOfDay($date);

    expect($result->format('H:i:s'))->toBe('23:59:59');
});

it('creates date range', function (): void {
    $start = Date::parse('2025-01-01');
    $end = Date::parse('2025-01-15');

    $range = DateHelper::range($start, $end);

    expect($range)->toHaveKeys(['start', 'end']);
    expect($range['start']->format('H:i:s'))->toBe('00:00:00');
    expect($range['end']->format('H:i:s'))->toBe('23:59:59');
});

it('calculates business days between dates', function (): void {
    $start = Date::parse('2025-01-13'); // Monday
    $end = Date::parse('2025-01-17'); // Friday

    $days = DateHelper::businessDaysBetween($start, $end);

    expect($days)->toBe(4); // Mon-Fri = 4 business days
});

it('formats date range', function (): void {
    $start = Date::parse('2025-01-01');
    $end = Date::parse('2025-01-15');

    $result = DateHelper::formatRange($start, $end, 'M j, Y');

    expect($result)->toBe('Jan 1, 2025 - Jan 15, 2025');
});

it('formats same day range as single date', function (): void {
    $date = Date::parse('2025-01-15');

    $result = DateHelper::formatRange($date, $date, 'M j, Y');

    expect($result)->toBe('Jan 15, 2025');
});
