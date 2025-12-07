<?php

declare(strict_types=1);

use App\Rules\PostalCode;

test('passes for valid US postal code', function () {
    config(['address.postal_code_patterns' => [
        'US' => '/^\d{5}(-\d{4})?$/',
    ]]);

    $rule = new PostalCode('US');

    expect($rule->passes('postal_code', '12345'))->toBeTrue()
        ->and($rule->passes('postal_code', '12345-6789'))->toBeTrue();
});

test('fails for invalid US postal code', function () {
    config(['address.postal_code_patterns' => [
        'US' => '/^\d{5}(-\d{4})?$/',
    ]]);

    $rule = new PostalCode('US');

    expect($rule->passes('postal_code', '1234'))->toBeFalse()
        ->and($rule->passes('postal_code', 'ABCDE'))->toBeFalse();
});

test('passes for valid CA postal code', function () {
    config(['address.postal_code_patterns' => [
        'CA' => '/^[A-Z]\d[A-Z] ?\d[A-Z]\d$/',
    ]]);

    $rule = new PostalCode('CA');

    expect($rule->passes('postal_code', 'K1A 0B1'))->toBeTrue()
        ->and($rule->passes('postal_code', 'K1A0B1'))->toBeTrue();
});

test('passes for null or empty value', function () {
    config(['address.postal_code_patterns' => [
        'US' => '/^\d{5}(-\d{4})?$/',
    ]]);

    $rule = new PostalCode('US');

    expect($rule->passes('postal_code', null))->toBeTrue()
        ->and($rule->passes('postal_code', ''))->toBeTrue();
});

test('passes when no pattern configured for country', function () {
    config(['address.postal_code_patterns' => []]);

    $rule = new PostalCode('XX');

    expect($rule->passes('postal_code', 'anything'))->toBeTrue();
});

test('has appropriate error message', function () {
    $rule = new PostalCode('US');

    expect($rule->message())->toBe('The :attribute format is invalid for the selected country.');
});

test('handles lowercase country code', function () {
    config(['address.postal_code_patterns' => [
        'US' => '/^\d{5}(-\d{4})?$/',
    ]]);

    $rule = new PostalCode('us');

    expect($rule->passes('postal_code', '12345'))->toBeTrue();
});
