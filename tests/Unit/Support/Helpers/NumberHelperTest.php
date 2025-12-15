<?php

declare(strict_types=1);

use App\Support\Helpers\NumberHelper;

it('formats currency', function (): void {
    $result = NumberHelper::currency(1234.56, 'USD');

    expect($result)->toContain('1,234.56');
});

it('returns placeholder for null currency', function (): void {
    expect(NumberHelper::currency(null))->toBe('—');
});

it('formats numbers with thousands separator', function (): void {
    $result = NumberHelper::format(1234567, 2);

    expect($result)->toBe('1,234,567.00');
});

it('formats percentages', function (): void {
    $result = NumberHelper::percentage(75.5, 2);

    expect($result)->toBe('75.50%');
});

it('formats percentages without symbol', function (): void {
    $result = NumberHelper::percentage(75.5, 2, includeSymbol: false);

    expect($result)->toBe('75.50');
});

it('converts bytes to human-readable format', function (): void {
    expect(NumberHelper::fileSize(1024))->toBe('1 KB');
    expect(NumberHelper::fileSize(1048576))->toBe('1 MB');
    expect(NumberHelper::fileSize(1073741824))->toBe('1 GB');
    expect(NumberHelper::fileSize(1536))->toBe('1.5 KB');
    expect(NumberHelper::fileSize(2097152))->toBe('2 MB');
});

it('abbreviates large numbers', function (): void {
    expect(NumberHelper::abbreviate(999))->toBe('999');
    expect(NumberHelper::abbreviate(1000))->toBe('1K');
    expect(NumberHelper::abbreviate(1500))->toBe('1.5K');
    expect(NumberHelper::abbreviate(1000000))->toBe('1M');
    expect(NumberHelper::abbreviate(1500000))->toBe('1.5M');
});

it('clamps numbers between min and max', function (): void {
    expect(NumberHelper::clamp(5, 1, 10))->toBe(5);
    expect(NumberHelper::clamp(0, 1, 10))->toBe(1);
    expect(NumberHelper::clamp(15, 1, 10))->toBe(10);
});

it('checks if number is in range', function (): void {
    expect(NumberHelper::inRange(5, 1, 10))->toBeTrue();
    expect(NumberHelper::inRange(0, 1, 10))->toBeFalse();
    expect(NumberHelper::inRange(15, 1, 10))->toBeFalse();
});

it('formats ordinal numbers', function (): void {
    expect(NumberHelper::ordinal(1))->toBe('1st');
    expect(NumberHelper::ordinal(2))->toBe('2nd');
    expect(NumberHelper::ordinal(3))->toBe('3rd');
    expect(NumberHelper::ordinal(4))->toBe('4th');
    expect(NumberHelper::ordinal(11))->toBe('11th');
    expect(NumberHelper::ordinal(21))->toBe('21st');
    expect(NumberHelper::ordinal(22))->toBe('22nd');
    expect(NumberHelper::ordinal(23))->toBe('23rd');
});
