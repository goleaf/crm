<?php

declare(strict_types=1);

it('can run a simple test', function (): void {
    expect(true)->toBeTrue();
});

it('can do basic math', function (): void {
    expect(1 + 1)->toBe(2);
});
