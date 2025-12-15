<?php

declare(strict_types=1);

use App\Enums\CustomFields\TaskField;
use App\Models\Task;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Data\CustomFieldOptionSettingsData;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldOption;

return new class extends Migration
{
    private const array STATUS_NAME_MAP = [
        'To do' => 'Not Started',
        'To Do' => 'Not Started',
        'Not Started' => 'Not Started',
        'In progress' => 'In Progress',
        'In Progress' => 'In Progress',
        'Done' => 'Completed',
        'Completed' => 'Completed',
    ];

    public function up(): void
    {
        $targetOptions = [
            'Not Started',
            'In Progress',
            'Completed',
        ];

        $fields = CustomField::query()
            ->forEntity(Task::class)
            ->where('code', TaskField::STATUS->value)
            ->with('options')
            ->get();

        /** @var array<int|string, string>|null $colors */
        $colors = TaskField::STATUS->getOptionColors();

        $fields->each(function (CustomField $field) use ($targetOptions, $colors): void {
            /** @var Collection<int, CustomFieldOption> $options */
            $options = $field->options;

            foreach ($targetOptions as $sortOrder => $targetName) {
                $option = $this->findMatchingOption($options, $targetName);

                if (! $option instanceof \Relaticle\CustomFields\Models\CustomFieldOption) {
                    $options->push(
                        $field->options()->create([
                            'name' => $targetName,
                            'sort_order' => $sortOrder + 1,
                            'settings' => new CustomFieldOptionSettingsData(
                                color: $colors[$targetName] ?? null,
                            ),
                        ]),
                    );

                    continue;
                }

                $option->update([
                    'name' => $targetName,
                    'sort_order' => $sortOrder + 1,
                    'settings' => new CustomFieldOptionSettingsData(
                        color: $colors[$targetName] ?? $option->settings->color,
                    ),
                ]);
            }
        });
    }

    public function down(): void
    {
        $fields = CustomField::query()
            ->forEntity(Task::class)
            ->where('code', TaskField::STATUS->value)
            ->with('options')
            ->get();

        $revertMap = [
            'Not Started' => 'To do',
            'In Progress' => 'In progress',
            'Completed' => 'Done',
        ];

        $fields->each(function (CustomField $field) use ($revertMap): void {
            foreach ($field->options as $option) {
                $option->update([
                    'name' => $revertMap[$option->name] ?? $option->name,
                ]);
            }
        });
    }

    /**
     * @param Collection<int, CustomFieldOption> $options
     */
    private function findMatchingOption(Collection $options, string $targetName): ?CustomFieldOption
    {
        $option = $options->first(function (CustomFieldOption $option) use ($targetName): bool {
            $normalized = self::STATUS_NAME_MAP[$option->name] ?? $option->name;

            return $normalized === $targetName;
        });

        return $option instanceof CustomFieldOption ? $option : null;
    }
};
