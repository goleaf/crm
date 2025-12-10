<?php

declare(strict_types=1);

it('can run a simple test', function () {
    expect(true)->toBeTrue();
});

it('can do basic math', function () {
    expect(1 + 1)->toBe(2);
});