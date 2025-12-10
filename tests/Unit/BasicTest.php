<?php

declare(strict_types=1);

// Simple test without Laravel bootstrap
test('basic php test', function () {
    expect(1 + 1)->toBe(2);
});

test('string test', function () {
    expect('hello')->toBe('hello');
});