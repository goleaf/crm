<?php

declare(strict_types=1);

use App\Data\SubscriberData;

test('can create subscriber data', function (): void {
    $subscriber = new SubscriberData(
        email: 'test@example.com',
        first_name: 'John',
        last_name: 'Doe',
        tags: ['newsletter', 'customer'],
        skip_confirmation: true,
    );

    expect($subscriber->email)->toBe('test@example.com')
        ->and($subscriber->first_name)->toBe('John')
        ->and($subscriber->last_name)->toBe('Doe')
        ->and($subscriber->tags)->toBe(['newsletter', 'customer'])
        ->and($subscriber->skip_confirmation)->toBeTrue();
});

test('has default values', function (): void {
    $subscriber = new SubscriberData(email: 'test@example.com');

    expect($subscriber->first_name)->toBe('')
        ->and($subscriber->last_name)->toBe('')
        ->and($subscriber->tags)->toBe([])
        ->and($subscriber->skip_confirmation)->toBeTrue();
});

test('can create with minimal data', function (): void {
    $subscriber = new SubscriberData(email: 'minimal@example.com');

    expect($subscriber->email)->toBe('minimal@example.com');
});
