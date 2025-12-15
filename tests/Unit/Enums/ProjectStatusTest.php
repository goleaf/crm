<?php

declare(strict_types=1);

use App\Enums\ProjectStatus;

uses()->group('unit', 'enum');

describe('ProjectStatus enum', function (): void {
    test('has all expected cases', function (): void {
        $cases = ProjectStatus::cases();

        expect($cases)->toHaveCount(5)
            ->and(array_map(fn (ProjectStatus $case): string => $case->value, $cases))
            ->toBe(['planning', 'active', 'on_hold', 'completed', 'cancelled']);
    });

    test('case values are correct', function (): void {
        expect(ProjectStatus::PLANNING->value)->toBe('planning')
            ->and(ProjectStatus::ACTIVE->value)->toBe('active')
            ->and(ProjectStatus::ON_HOLD->value)->toBe('on_hold')
            ->and(ProjectStatus::COMPLETED->value)->toBe('completed')
            ->and(ProjectStatus::CANCELLED->value)->toBe('cancelled');
    });
});

describe('ProjectStatus labels', function (): void {
    test('returns translated labels', function (): void {
        expect(ProjectStatus::PLANNING->getLabel())->toBe('Planning')
            ->and(ProjectStatus::ACTIVE->getLabel())->toBe('Active')
            ->and(ProjectStatus::ON_HOLD->getLabel())->toBe('On Hold')
            ->and(ProjectStatus::COMPLETED->getLabel())->toBe('Completed')
            ->and(ProjectStatus::CANCELLED->getLabel())->toBe('Cancelled');
    });

    test('labels are not empty', function (): void {
        foreach (ProjectStatus::cases() as $status) {
            expect($status->getLabel())->not()->toBeEmpty();
        }
    });
});

describe('ProjectStatus colors', function (): void {
    test('returns correct Filament colors', function (): void {
        expect(ProjectStatus::PLANNING->getColor())->toBe('gray')
            ->and(ProjectStatus::ACTIVE->getColor())->toBe('primary')
            ->and(ProjectStatus::ON_HOLD->getColor())->toBe('warning')
            ->and(ProjectStatus::COMPLETED->getColor())->toBe('success')
            ->and(ProjectStatus::CANCELLED->getColor())->toBe('danger');
    });

    test('all colors are valid Filament colors', function (): void {
        $validColors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'gray', 'grey'];

        foreach (ProjectStatus::cases() as $status) {
            expect($status->getColor())->toBeIn($validColors);
        }
    });
});

describe('ProjectStatus icons', function (): void {
    test('returns correct heroicons', function (): void {
        expect(ProjectStatus::PLANNING->getIcon())->toBe('heroicon-o-clipboard-document-list')
            ->and(ProjectStatus::ACTIVE->getIcon())->toBe('heroicon-o-play')
            ->and(ProjectStatus::ON_HOLD->getIcon())->toBe('heroicon-o-pause')
            ->and(ProjectStatus::COMPLETED->getIcon())->toBe('heroicon-o-check-circle')
            ->and(ProjectStatus::CANCELLED->getIcon())->toBe('heroicon-o-x-circle');
    });

    test('all icons follow heroicon naming convention', function (): void {
        foreach (ProjectStatus::cases() as $status) {
            expect($status->getIcon())->toStartWith('heroicon-');
        }
    });
});

describe('ProjectStatus options', function (): void {
    test('returns array of value => label pairs', function (): void {
        $options = ProjectStatus::options();

        expect($options)->toBeArray()
            ->and($options)->toHaveCount(5)
            ->and($options)->toHaveKeys(['planning', 'active', 'on_hold', 'completed', 'cancelled']);
    });

    test('option keys match case values', function (): void {
        $options = ProjectStatus::options();
        $caseValues = array_map(fn (ProjectStatus $case): string => $case->value, ProjectStatus::cases());

        expect(array_keys($options))->toBe($caseValues);
    });

    test('option values are translated labels', function (): void {
        $options = ProjectStatus::options();

        expect($options['planning'])->toBe(ProjectStatus::PLANNING->getLabel())
            ->and($options['active'])->toBe(ProjectStatus::ACTIVE->getLabel())
            ->and($options['on_hold'])->toBe(ProjectStatus::ON_HOLD->getLabel())
            ->and($options['completed'])->toBe(ProjectStatus::COMPLETED->getLabel())
            ->and($options['cancelled'])->toBe(ProjectStatus::CANCELLED->getLabel());
    });
});

describe('ProjectStatus state checks', function (): void {
    test('isActive returns true only for ACTIVE status', function (): void {
        expect(ProjectStatus::ACTIVE->isActive())->toBeTrue()
            ->and(ProjectStatus::PLANNING->isActive())->toBeFalse()
            ->and(ProjectStatus::ON_HOLD->isActive())->toBeFalse()
            ->and(ProjectStatus::COMPLETED->isActive())->toBeFalse()
            ->and(ProjectStatus::CANCELLED->isActive())->toBeFalse();
    });

    test('isCompleted returns true only for COMPLETED status', function (): void {
        expect(ProjectStatus::COMPLETED->isCompleted())->toBeTrue()
            ->and(ProjectStatus::PLANNING->isCompleted())->toBeFalse()
            ->and(ProjectStatus::ACTIVE->isCompleted())->toBeFalse()
            ->and(ProjectStatus::ON_HOLD->isCompleted())->toBeFalse()
            ->and(ProjectStatus::CANCELLED->isCompleted())->toBeFalse();
    });

    test('isTerminal returns true for COMPLETED and CANCELLED', function (): void {
        expect(ProjectStatus::COMPLETED->isTerminal())->toBeTrue()
            ->and(ProjectStatus::CANCELLED->isTerminal())->toBeTrue()
            ->and(ProjectStatus::PLANNING->isTerminal())->toBeFalse()
            ->and(ProjectStatus::ACTIVE->isTerminal())->toBeFalse()
            ->and(ProjectStatus::ON_HOLD->isTerminal())->toBeFalse();
    });

    test('allowsModifications returns false for terminal states', function (): void {
        expect(ProjectStatus::COMPLETED->allowsModifications())->toBeFalse()
            ->and(ProjectStatus::CANCELLED->allowsModifications())->toBeFalse()
            ->and(ProjectStatus::PLANNING->allowsModifications())->toBeTrue()
            ->and(ProjectStatus::ACTIVE->allowsModifications())->toBeTrue()
            ->and(ProjectStatus::ON_HOLD->allowsModifications())->toBeTrue();
    });
});

describe('ProjectStatus transitions', function (): void {
    test('PLANNING can transition to ACTIVE or CANCELLED', function (): void {
        $allowed = ProjectStatus::PLANNING->allowedTransitions();

        expect($allowed)->toHaveCount(2)
            ->and($allowed)->toContain(ProjectStatus::ACTIVE)
            ->and($allowed)->toContain(ProjectStatus::CANCELLED);
    });

    test('ACTIVE can transition to ON_HOLD, COMPLETED, or CANCELLED', function (): void {
        $allowed = ProjectStatus::ACTIVE->allowedTransitions();

        expect($allowed)->toHaveCount(3)
            ->and($allowed)->toContain(ProjectStatus::ON_HOLD)
            ->and($allowed)->toContain(ProjectStatus::COMPLETED)
            ->and($allowed)->toContain(ProjectStatus::CANCELLED);
    });

    test('ON_HOLD can transition to ACTIVE or CANCELLED', function (): void {
        $allowed = ProjectStatus::ON_HOLD->allowedTransitions();

        expect($allowed)->toHaveCount(2)
            ->and($allowed)->toContain(ProjectStatus::ACTIVE)
            ->and($allowed)->toContain(ProjectStatus::CANCELLED);
    });

    test('COMPLETED cannot transition to any status', function (): void {
        $allowed = ProjectStatus::COMPLETED->allowedTransitions();

        expect($allowed)->toBeEmpty();
    });

    test('CANCELLED cannot transition to any status', function (): void {
        $allowed = ProjectStatus::CANCELLED->allowedTransitions();

        expect($allowed)->toBeEmpty();
    });

    test('canTransitionTo validates allowed transitions', function (): void {
        // PLANNING -> ACTIVE: allowed
        expect(ProjectStatus::PLANNING->canTransitionTo(ProjectStatus::ACTIVE))->toBeTrue();

        // PLANNING -> COMPLETED: not allowed
        expect(ProjectStatus::PLANNING->canTransitionTo(ProjectStatus::COMPLETED))->toBeFalse();

        // ACTIVE -> COMPLETED: allowed
        expect(ProjectStatus::ACTIVE->canTransitionTo(ProjectStatus::COMPLETED))->toBeTrue();

        // COMPLETED -> ACTIVE: not allowed
        expect(ProjectStatus::COMPLETED->canTransitionTo(ProjectStatus::ACTIVE))->toBeFalse();

        // ON_HOLD -> ACTIVE: allowed
        expect(ProjectStatus::ON_HOLD->canTransitionTo(ProjectStatus::ACTIVE))->toBeTrue();

        // ON_HOLD -> COMPLETED: not allowed
        expect(ProjectStatus::ON_HOLD->canTransitionTo(ProjectStatus::COMPLETED))->toBeFalse();
    });

    test('terminal states cannot transition to themselves', function (): void {
        expect(ProjectStatus::COMPLETED->canTransitionTo(ProjectStatus::COMPLETED))->toBeFalse()
            ->and(ProjectStatus::CANCELLED->canTransitionTo(ProjectStatus::CANCELLED))->toBeFalse();
    });
});

describe('ProjectStatus integration', function (): void {
    test('can be used in match expressions', function (): void {
        $status = ProjectStatus::ACTIVE;

        $result = match ($status) {
            ProjectStatus::PLANNING => 'planning',
            ProjectStatus::ACTIVE => 'active',
            ProjectStatus::ON_HOLD => 'on_hold',
            ProjectStatus::COMPLETED => 'completed',
            ProjectStatus::CANCELLED => 'cancelled',
        };

        expect($result)->toBe('active');
    });

    test('can be serialized to string', function (): void {
        expect(ProjectStatus::PLANNING->value)->toBeString()
            ->and(ProjectStatus::ACTIVE->value)->toBeString();
    });

    test('can be created from string value', function (): void {
        $status = ProjectStatus::from('active');

        expect($status)->toBe(ProjectStatus::ACTIVE);
    });

    test('tryFrom returns null for invalid value', function (): void {
        $status = ProjectStatus::tryFrom('invalid');

        expect($status)->toBeNull();
    });
});

describe('ProjectStatus EnumHelpers', function (): void {
    test('values returns all enum values', function (): void {
        $values = ProjectStatus::values();

        expect($values)->toBe(['planning', 'active', 'on_hold', 'completed', 'cancelled']);
    });

    test('names returns all enum names', function (): void {
        $names = ProjectStatus::names();

        expect($names)->toBe(['PLANNING', 'ACTIVE', 'ON_HOLD', 'COMPLETED', 'CANCELLED']);
    });

    test('fromValueOrNull returns enum for valid value', function (): void {
        $status = ProjectStatus::fromValueOrNull('active');

        expect($status)->toBe(ProjectStatus::ACTIVE);
    });

    test('fromValueOrNull returns null for invalid value', function (): void {
        $status = ProjectStatus::fromValueOrNull('invalid');

        expect($status)->toBeNull();
    });

    test('fromValueOrNull returns null for null input', function (): void {
        $status = ProjectStatus::fromValueOrNull(null);

        expect($status)->toBeNull();
    });

    test('fromName returns enum for valid name', function (): void {
        $status = ProjectStatus::fromName('ACTIVE');

        expect($status)->toBe(ProjectStatus::ACTIVE);
    });

    test('fromName returns null for invalid name', function (): void {
        $status = ProjectStatus::fromName('INVALID');

        expect($status)->toBeNull();
    });

    test('random returns a valid enum case', function (): void {
        $status = ProjectStatus::random();

        expect($status)->toBeInstanceOf(ProjectStatus::class)
            ->and(ProjectStatus::cases())->toContain($status);
    });

    test('isValid returns true for valid values', function (): void {
        expect(ProjectStatus::isValid('active'))->toBeTrue()
            ->and(ProjectStatus::isValid('planning'))->toBeTrue()
            ->and(ProjectStatus::isValid('completed'))->toBeTrue();
    });

    test('isValid returns false for invalid values', function (): void {
        expect(ProjectStatus::isValid('invalid'))->toBeFalse()
            ->and(ProjectStatus::isValid(''))->toBeFalse()
            ->and(ProjectStatus::isValid(null))->toBeFalse();
    });

    test('rule returns validation rule string', function (): void {
        $rule = ProjectStatus::rule();

        expect($rule)->toStartWith('in:')
            ->and($rule)->toContain('planning')
            ->and($rule)->toContain('active')
            ->and($rule)->toContain('completed');
    });

    test('rules returns validation rule array', function (): void {
        $rules = ProjectStatus::rules();

        expect($rules)->toBeArray()
            ->and($rules)->toHaveCount(1)
            ->and($rules[0])->toStartWith('in:');
    });

    test('collect returns collection of all cases', function (): void {
        $collection = ProjectStatus::collect();

        expect($collection)->toBeInstanceOf(\Illuminate\Support\Collection::class)
            ->and($collection)->toHaveCount(5)
            ->and($collection->first())->toBeInstanceOf(ProjectStatus::class);
    });

    test('toSelectArray returns value => label pairs', function (): void {
        $array = ProjectStatus::toSelectArray();

        expect($array)->toBeArray()
            ->and($array)->toHaveKeys(['planning', 'active', 'on_hold', 'completed', 'cancelled'])
            ->and($array['planning'])->toBe('Planning')
            ->and($array['active'])->toBe('Active');
    });

    test('toArray returns array of objects with value and label', function (): void {
        $array = ProjectStatus::toArray();

        expect($array)->toBeArray()
            ->and($array)->toHaveCount(5)
            ->and($array[0])->toHaveKeys(['value', 'label'])
            ->and($array[0]['value'])->toBe('planning')
            ->and($array[0]['label'])->toBe('Planning');
    });

    test('hasValue returns true for existing values', function (): void {
        expect(ProjectStatus::hasValue('active'))->toBeTrue()
            ->and(ProjectStatus::hasValue('planning'))->toBeTrue();
    });

    test('hasValue returns false for non-existing values', function (): void {
        expect(ProjectStatus::hasValue('invalid'))->toBeFalse()
            ->and(ProjectStatus::hasValue('ACTIVE'))->toBeFalse();
    });

    test('hasName returns true for existing names', function (): void {
        expect(ProjectStatus::hasName('ACTIVE'))->toBeTrue()
            ->and(ProjectStatus::hasName('PLANNING'))->toBeTrue();
    });

    test('hasName returns false for non-existing names', function (): void {
        expect(ProjectStatus::hasName('invalid'))->toBeFalse()
            ->and(ProjectStatus::hasName('active'))->toBeFalse();
    });

    test('count returns number of cases', function (): void {
        expect(ProjectStatus::count())->toBe(5);
    });
});
