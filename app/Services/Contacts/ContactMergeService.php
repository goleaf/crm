<?php

declare(strict_types=1);

namespace App\Services\Contacts;

use App\Models\ContactMergeLog;
use App\Models\People;
use Illuminate\Support\Facades\DB;

final class ContactMergeService
{
    /**
     * Merge duplicate into primary and transfer relationships.
     */
    public function merge(People $primary, People $duplicate, int $userId, array $fieldSelections = []): People
    {
        return DB::transaction(function () use ($primary, $duplicate, $userId, $fieldSelections): People {
            $this->applyFieldSelections($primary, $duplicate, $fieldSelections);

            $this->transferRelation($duplicate, $primary, 'tasks');
            $this->transferRelation($duplicate, $primary, 'notes');
            $this->transferRelation($duplicate, $primary, 'opportunities');
            $this->transferRelation($duplicate, $primary, 'cases');
            $this->transferTags($duplicate, $primary);

            $primary->save();

            $duplicate->forceFill([
                'duplicate_of_id' => $primary->getKey(),
            ])->save();

            $duplicate->delete();

            ContactMergeLog::create([
                'primary_contact_id' => $primary->getKey(),
                'duplicate_contact_id' => $duplicate->getKey(),
                'merged_by' => $userId,
                'merge_data' => $fieldSelections,
            ]);

            return $primary->fresh(['notes', 'tasks', 'opportunities', 'cases', 'tags']);
        });
    }

    private function applyFieldSelections(People $primary, People $duplicate, array $fieldSelections): void
    {
        foreach ($fieldSelections as $field => $source) {
            if ($source === 'duplicate' && $duplicate->{$field} !== null) {
                $primary->{$field} = $duplicate->{$field};
            }
        }
    }

    private function transferRelation(People $from, People $to, string $relation): void
    {
        if (! method_exists($from, $relation) || ! method_exists($to, $relation)) {
            return;
        }

        /** @var \Illuminate\Database\Eloquent\Relations\Relation $relationFrom */
        $relationFrom = $from->{$relation}();

        if ($relationFrom instanceof \Illuminate\Database\Eloquent\Relations\HasMany) {
            $relationFrom->update(['contact_id' => $to->getKey()]);
        } elseif ($relationFrom instanceof \Illuminate\Database\Eloquent\Relations\MorphToMany) {
            $ids = $relationFrom->pluck($relationFrom->getRelated()->getKeyName());
            $to->{$relation}()->syncWithoutDetaching($ids);
        }
    }

    private function transferTags(People $from, People $to): void
    {
        $tagIds = $from->tags()->pluck('tags.id')->all();
        if ($tagIds !== []) {
            $to->tags()->syncWithoutDetaching($tagIds);
        }
    }
}
