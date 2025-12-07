<?php

declare(strict_types=1);

use App\Enums\AccountType;
use App\Enums\InvoiceStatus;
use App\Enums\LeadStatus;

test('enums have cases', function () {
    expect(AccountType::cases())->not()->toBeEmpty()
        ->and(LeadStatus::cases())->not()->toBeEmpty()
        ->and(InvoiceStatus::cases())->not()->toBeEmpty();
});

test('enum cases have valid values', function () {
    expect(AccountType::CUSTOMER->value)->toBe('customer')
        ->and(LeadStatus::NEW->value)->toBe('new')
        ->and(InvoiceStatus::DRAFT->value)->toBe('draft');
});

test('enums have label methods', function () {
    expect(AccountType::CUSTOMER->label())->toBe('Customer')
        ->and(LeadStatus::NEW->getLabel())->toBe('New')
        ->and(InvoiceStatus::DRAFT->label())->toBe('Draft');
});

test('enums have color methods', function () {
    expect(AccountType::CUSTOMER->color())->toBe('success')
        ->and(LeadStatus::NEW->color())->toBe('gray')
        ->and(InvoiceStatus::DRAFT->color())->toBe('gray');
});

test('enum colors are valid filament colors', function () {
    $validColors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'gray', 'grey'];

    expect(AccountType::CUSTOMER->color())->toBeIn($validColors)
        ->and(LeadStatus::QUALIFIED->color())->toBeIn($validColors)
        ->and(InvoiceStatus::PAID->color())->toBeIn($validColors);
});

test('enums have options method', function () {
    $options = AccountType::options();

    expect($options)->toBeArray()
        ->and($options)->toHaveKey('customer', 'Customer')
        ->and($options)->toHaveKey('prospect', 'Prospect');
});

test('enum options keys match case values', function () {
    $options = LeadStatus::options();
    $caseValues = array_map(fn ($case) => $case->value, LeadStatus::cases());

    expect(array_keys($options))->toBe($caseValues);
});
