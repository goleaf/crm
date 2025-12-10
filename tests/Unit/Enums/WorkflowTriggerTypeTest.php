<?php

declare(strict_types=1);

use App\Enums\WorkflowTriggerType;
use Filament\Support\Contracts\HasLabel;

describe('WorkflowTriggerType enum', function (): void {
    it('has all expected cases', function (): void {
        $cases = WorkflowTriggerType::cases();

        expect($cases)->toHaveCount(4)
            ->and($cases)->toContain(WorkflowTriggerType::ON_CREATE)
            ->and($cases)->toContain(WorkflowTriggerType::ON_EDIT)
            ->and($cases)->toContain(WorkflowTriggerType::AFTER_SAVE)
            ->and($cases)->toContain(WorkflowTriggerType::SCHEDULED);
    });

    it('has correct string values', function (): void {
        expect(WorkflowTriggerType::ON_CREATE->value)->toBe('on_create')
            ->and(WorkflowTriggerType::ON_EDIT->value)->toBe('on_edit')
            ->and(WorkflowTriggerType::AFTER_SAVE->value)->toBe('after_save')
            ->and(WorkflowTriggerType::SCHEDULED->value)->toBe('scheduled');
    });

    it('implements HasLabel interface', function (): void {
        expect(WorkflowTriggerType::ON_CREATE)->toBeInstanceOf(HasLabel::class);
    });

    it('returns translated labels', function (): void {
        expect(WorkflowTriggerType::ON_CREATE->getLabel())->toBe('On Create')
            ->and(WorkflowTriggerType::ON_EDIT->getLabel())->toBe('On Edit')
            ->and(WorkflowTriggerType::AFTER_SAVE->getLabel())->toBe('After Save')
            ->and(WorkflowTriggerType::SCHEDULED->getLabel())->toBe('Scheduled');
    });

    it('can be instantiated from string value', function (): void {
        expect(WorkflowTriggerType::from('on_create'))->toBe(WorkflowTriggerType::ON_CREATE)
            ->and(WorkflowTriggerType::from('on_edit'))->toBe(WorkflowTriggerType::ON_EDIT)
            ->and(WorkflowTriggerType::from('after_save'))->toBe(WorkflowTriggerType::AFTER_SAVE)
            ->and(WorkflowTriggerType::from('scheduled'))->toBe(WorkflowTriggerType::SCHEDULED);
    });

    it('can be safely instantiated with tryFrom', function (): void {
        expect(WorkflowTriggerType::tryFrom('on_create'))->toBe(WorkflowTriggerType::ON_CREATE)
            ->and(WorkflowTriggerType::tryFrom('invalid'))->toBeNull();
    });

    it('throws exception for invalid value with from', function (): void {
        WorkflowTriggerType::from('invalid');
    })->throws(ValueError::class);

    it('can be serialized to string', function (): void {
        expect(WorkflowTriggerType::ON_CREATE->value)->toBeString()
            ->and(WorkflowTriggerType::SCHEDULED->value)->toBeString();
    });

    it('can be used in match expressions', function (): void {
        $result = match (WorkflowTriggerType::ON_CREATE) {
            WorkflowTriggerType::ON_CREATE => 'create',
            WorkflowTriggerType::ON_EDIT => 'edit',
            WorkflowTriggerType::AFTER_SAVE => 'save',
            WorkflowTriggerType::SCHEDULED => 'schedule',
        };

        expect($result)->toBe('create');
    });

    it('can be compared for equality', function (): void {
        expect(WorkflowTriggerType::ON_CREATE === WorkflowTriggerType::ON_CREATE)->toBeTrue()
            ->and(WorkflowTriggerType::ON_CREATE === WorkflowTriggerType::ON_EDIT)->toBeFalse();
    });

    it('has unique values', function (): void {
        $values = array_map(fn (\App\Enums\WorkflowTriggerType $case) => $case->value, WorkflowTriggerType::cases());
        $uniqueValues = array_unique($values);

        expect($values)->toBe($uniqueValues);
    });

    it('labels are non-empty strings', function (): void {
        foreach (WorkflowTriggerType::cases() as $case) {
            expect($case->getLabel())
                ->toBeString()
                ->not()->toBeEmpty();
        }
    });

    it('can be used in arrays', function (): void {
        $triggers = [
            WorkflowTriggerType::ON_CREATE,
            WorkflowTriggerType::SCHEDULED,
        ];

        expect($triggers)->toHaveCount(2)
            ->and(in_array(WorkflowTriggerType::ON_CREATE, $triggers, true))->toBeTrue()
            ->and(in_array(WorkflowTriggerType::ON_EDIT, $triggers, true))->toBeFalse();
    });

    it('translation keys follow correct pattern', function (): void {
        // Verify translation keys exist and follow the pattern
        expect(__('enums.workflow_trigger_type.on_create'))->toBe('On Create')
            ->and(__('enums.workflow_trigger_type.on_edit'))->toBe('On Edit')
            ->and(__('enums.workflow_trigger_type.after_save'))->toBe('After Save')
            ->and(__('enums.workflow_trigger_type.scheduled'))->toBe('Scheduled');
    });

    it('can be used in database queries', function (): void {
        // Simulate database value comparison
        $dbValue = 'on_create';
        $enum = WorkflowTriggerType::from($dbValue);

        expect($enum)->toBe(WorkflowTriggerType::ON_CREATE)
            ->and($enum->value)->toBe($dbValue);
    });

    it('supports all trigger types for workflow automation', function (): void {
        // Verify we have the essential trigger types for workflow automation
        $triggerTypes = array_map(fn (\App\Enums\WorkflowTriggerType $case) => $case->value, WorkflowTriggerType::cases());

        expect($triggerTypes)->toContain('on_create') // Record creation
            ->and($triggerTypes)->toContain('on_edit') // Record edit
            ->and($triggerTypes)->toContain('after_save') // After save (create or edit)
            ->and($triggerTypes)->toContain('scheduled'); // Time-based triggers
    });
});