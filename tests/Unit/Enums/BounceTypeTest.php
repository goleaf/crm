<?php

declare(strict_types=1);

use App\Enums\BounceType;

uses()->group('unit', 'enum');

describe('BounceType enum', function (): void {
    test('has all expected cases', function (): void {
        $cases = BounceType::cases();

        expect($cases)->toHaveCount(3)
            ->and(array_map(fn (BounceType $case): string => $case->value, $cases))
            ->toBe(['hard', 'soft', 'complaint']);
    });

    test('case values are correct', function (): void {
        expect(BounceType::HARD->value)->toBe('hard')
            ->and(BounceType::SOFT->value)->toBe('soft')
            ->and(BounceType::COMPLAINT->value)->toBe('complaint');
    });
});

describe('BounceType labels', function (): void {
    test('returns translated labels', function (): void {
        expect(BounceType::HARD->getLabel())->toBe('Hard Bounce')
            ->and(BounceType::SOFT->getLabel())->toBe('Soft Bounce')
            ->and(BounceType::COMPLAINT->getLabel())->toBe('Complaint');
    });

    test('labels are not empty', function (): void {
        foreach (BounceType::cases() as $type) {
            expect($type->getLabel())->not()->toBeEmpty();
        }
    });
});

describe('BounceType colors', function (): void {
    test('returns correct Filament colors', function (): void {
        expect(BounceType::HARD->getColor())->toBe('danger')
            ->and(BounceType::SOFT->getColor())->toBe('warning')
            ->and(BounceType::COMPLAINT->getColor())->toBe('danger');
    });

    test('all colors are valid Filament colors', function (): void {
        $validColors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'gray', 'grey'];

        foreach (BounceType::cases() as $type) {
            expect($type->getColor())->toBeIn($validColors);
        }
    });
});

describe('BounceType icons', function (): void {
    test('returns correct heroicons', function (): void {
        expect(BounceType::HARD->getIcon())->toBe('heroicon-o-x-circle')
            ->and(BounceType::SOFT->getIcon())->toBe('heroicon-o-exclamation-triangle')
            ->and(BounceType::COMPLAINT->getIcon())->toBe('heroicon-o-flag');
    });

    test('all icons follow heroicon naming convention', function (): void {
        foreach (BounceType::cases() as $type) {
            expect($type->getIcon())->toStartWith('heroicon-');
        }
    });
});

describe('BounceType options', function (): void {
    test('returns array of value => label pairs', function (): void {
        $options = BounceType::options();

        expect($options)->toBeArray()
            ->and($options)->toHaveCount(3)
            ->and($options)->toHaveKeys(['hard', 'soft', 'complaint']);
    });

    test('option keys match case values', function (): void {
        $options = BounceType::options();
        $caseValues = array_map(fn (BounceType $case): string => $case->value, BounceType::cases());

        expect(array_keys($options))->toBe($caseValues);
    });

    test('option values are translated labels', function (): void {
        $options = BounceType::options();

        expect($options['hard'])->toBe(BounceType::HARD->getLabel())
            ->and($options['soft'])->toBe(BounceType::SOFT->getLabel())
            ->and($options['complaint'])->toBe(BounceType::COMPLAINT->getLabel());
    });
});

describe('BounceType permanence checks', function (): void {
    test('isPermanent returns true for HARD and COMPLAINT', function (): void {
        expect(BounceType::HARD->isPermanent())->toBeTrue()
            ->and(BounceType::COMPLAINT->isPermanent())->toBeTrue()
            ->and(BounceType::SOFT->isPermanent())->toBeFalse();
    });

    test('isTemporary returns true only for SOFT', function (): void {
        expect(BounceType::SOFT->isTemporary())->toBeTrue()
            ->and(BounceType::HARD->isTemporary())->toBeFalse()
            ->and(BounceType::COMPLAINT->isTemporary())->toBeFalse();
    });

    test('shouldSuppressEmail returns true for permanent bounces', function (): void {
        expect(BounceType::HARD->shouldSuppressEmail())->toBeTrue()
            ->and(BounceType::COMPLAINT->shouldSuppressEmail())->toBeTrue()
            ->and(BounceType::SOFT->shouldSuppressEmail())->toBeFalse();
    });
});

describe('BounceType severity', function (): void {
    test('returns correct severity levels', function (): void {
        expect(BounceType::HARD->getSeverity())->toBe(3)
            ->and(BounceType::COMPLAINT->getSeverity())->toBe(3)
            ->and(BounceType::SOFT->getSeverity())->toBe(1);
    });

    test('severity is always between 1 and 3', function (): void {
        foreach (BounceType::cases() as $type) {
            expect($type->getSeverity())->toBeGreaterThanOrEqual(1)
                ->and($type->getSeverity())->toBeLessThanOrEqual(3);
        }
    });

    test('permanent bounces have higher severity than temporary', function (): void {
        foreach (BounceType::cases() as $type) {
            if ($type->isPermanent()) {
                expect($type->getSeverity())->toBeGreaterThan(BounceType::SOFT->getSeverity());
            }
        }
    });
});

describe('BounceType integration', function (): void {
    test('can be used in match expressions', function (): void {
        $type = BounceType::HARD;

        $result = match ($type) {
            BounceType::HARD => 'hard',
            BounceType::SOFT => 'soft',
            BounceType::COMPLAINT => 'complaint',
        };

        expect($result)->toBe('hard');
    });

    test('can be serialized to string', function (): void {
        expect(BounceType::HARD->value)->toBeString()
            ->and(BounceType::SOFT->value)->toBeString()
            ->and(BounceType::COMPLAINT->value)->toBeString();
    });

    test('can be created from string value', function (): void {
        $type = BounceType::from('hard');

        expect($type)->toBe(BounceType::HARD);
    });

    test('tryFrom returns null for invalid value', function (): void {
        $type = BounceType::tryFrom('invalid');

        expect($type)->toBeNull();
    });
});

describe('BounceType EnumHelpers trait', function (): void {
    test('values returns all enum values', function (): void {
        $values = BounceType::values();

        expect($values)->toBe(['hard', 'soft', 'complaint']);
    });

    test('names returns all enum names', function (): void {
        $names = BounceType::names();

        expect($names)->toBe(['HARD', 'SOFT', 'COMPLAINT']);
    });

    test('fromValueOrNull returns enum or null', function (): void {
        expect(BounceType::fromValueOrNull('hard'))->toBe(BounceType::HARD)
            ->and(BounceType::fromValueOrNull('invalid'))->toBeNull()
            ->and(BounceType::fromValueOrNull(null))->toBeNull();
    });

    test('fromName returns enum by name', function (): void {
        expect(BounceType::fromName('HARD'))->toBe(BounceType::HARD)
            ->and(BounceType::fromName('SOFT'))->toBe(BounceType::SOFT)
            ->and(BounceType::fromName('INVALID'))->toBeNull();
    });

    test('random returns a valid enum case', function (): void {
        $random = BounceType::random();

        expect($random)->toBeInstanceOf(BounceType::class)
            ->and(BounceType::cases())->toContain($random);
    });

    test('isValid checks if value is valid', function (): void {
        expect(BounceType::isValid('hard'))->toBeTrue()
            ->and(BounceType::isValid('soft'))->toBeTrue()
            ->and(BounceType::isValid('invalid'))->toBeFalse();
    });

    test('rule returns validation rule string', function (): void {
        $rule = BounceType::rule();

        expect($rule)->toBe('in:hard,soft,complaint');
    });

    test('rules returns validation rule array', function (): void {
        $rules = BounceType::rules();

        expect($rules)->toBe(['in:hard,soft,complaint']);
    });

    test('collect returns collection of cases', function (): void {
        $collection = BounceType::collect();

        expect($collection)->toHaveCount(3)
            ->and($collection->first())->toBeInstanceOf(BounceType::class);
    });

    test('toSelectArray returns value => label array', function (): void {
        $array = BounceType::toSelectArray();

        expect($array)->toBeArray()
            ->and($array)->toHaveKeys(['hard', 'soft', 'complaint'])
            ->and($array['hard'])->toBe(BounceType::HARD->getLabel());
    });

    test('toArray returns array of objects with value and label', function (): void {
        $array = BounceType::toArray();

        expect($array)->toBeArray()
            ->and($array)->toHaveCount(3)
            ->and($array[0])->toHaveKeys(['value', 'label'])
            ->and($array[0]['value'])->toBe('hard')
            ->and($array[0]['label'])->toBe(BounceType::HARD->getLabel());
    });

    test('hasValue checks if enum has specific value', function (): void {
        expect(BounceType::hasValue('hard'))->toBeTrue()
            ->and(BounceType::hasValue('invalid'))->toBeFalse();
    });

    test('hasName checks if enum has specific name', function (): void {
        expect(BounceType::hasName('HARD'))->toBeTrue()
            ->and(BounceType::hasName('INVALID'))->toBeFalse();
    });

    test('count returns number of cases', function (): void {
        expect(BounceType::count())->toBe(3);
    });
});