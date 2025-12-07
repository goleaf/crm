<?php

declare(strict_types=1);

namespace Tests\Support\Generators;

use App\Enums\CreationSource;
use App\Enums\NoteCategory;
use App\Enums\NoteVisibility;
use App\Models\Note;
use App\Models\Team;
use App\Models\User;

/**
 * Generator for creating random Note instances for property-based testing.
 */
final class NoteGenerator
{
    /**
     * Generate a random note with all fields populated.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function generate(Team $team, ?User $creator = null, array $overrides = []): Note
    {
        $creator = $creator ?? User::factory()->create();

        $data = array_merge([
            'team_id' => $team->id,
            'user_id' => $creator->id,
            'title' => fake()->sentence(),
            'category' => fake()->randomElement(NoteCategory::cases())->value,
            'visibility' => fake()->randomElement(NoteVisibility::cases()),
            'creation_source' => fake()->randomElement(CreationSource::cases()),
            'is_template' => fake()->boolean(10), // 10% chance of being a template
        ], $overrides);

        return Note::factory()->create($data);
    }

    /**
     * Generate a note with specific visibility.
     */
    public static function generateWithVisibility(Team $team, NoteVisibility $visibility): Note
    {
        return self::generate($team, overrides: ['visibility' => $visibility]);
    }

    /**
     * Generate a private note.
     */
    public static function generatePrivate(Team $team, User $creator): Note
    {
        return self::generate($team, $creator, ['visibility' => NoteVisibility::PRIVATE]);
    }

    /**
     * Generate an internal note.
     */
    public static function generateInternal(Team $team): Note
    {
        return self::generate($team, overrides: ['visibility' => NoteVisibility::INTERNAL]);
    }

    /**
     * Generate an external note.
     */
    public static function generateExternal(Team $team): Note
    {
        return self::generate($team, overrides: ['visibility' => NoteVisibility::EXTERNAL]);
    }

    /**
     * Generate a note with specific category.
     */
    public static function generateWithCategory(Team $team, NoteCategory $category): Note
    {
        return self::generate($team, overrides: ['category' => $category->value]);
    }

    /**
     * Generate random note data without creating a model.
     *
     * @return array<string, mixed>
     */
    public static function generateData(Team $team, ?User $creator = null): array
    {
        $creator = $creator ?? User::factory()->create();

        return [
            'team_id' => $team->id,
            'user_id' => $creator->id,
            'title' => fake()->sentence(),
            'category' => fake()->randomElement(NoteCategory::cases())->value,
            'visibility' => fake()->randomElement(NoteVisibility::cases()),
            'creation_source' => fake()->randomElement(CreationSource::cases()),
            'is_template' => fake()->boolean(10),
        ];
    }

    /**
     * Generate a note template.
     */
    public static function generateTemplate(Team $team): Note
    {
        return self::generate($team, overrides: ['is_template' => true]);
    }

    /**
     * Generate notes with all visibility levels.
     *
     * @return array<string, Note>
     */
    public static function generateAllVisibilities(Team $team, User $creator): array
    {
        return [
            'private' => self::generatePrivate($team, $creator),
            'internal' => self::generateInternal($team),
            'external' => self::generateExternal($team),
        ];
    }

    /**
     * Generate notes with all categories.
     *
     * @return array<string, Note>
     */
    public static function generateAllCategories(Team $team): array
    {
        $notes = [];

        foreach (NoteCategory::cases() as $category) {
            $notes[$category->value] = self::generateWithCategory($team, $category);
        }

        return $notes;
    }
}
