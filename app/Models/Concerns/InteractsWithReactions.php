<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Binafy\LaravelReaction\Contracts\HasReaction;
use Binafy\LaravelReaction\Enums\LaravelReactionTypeEnum;
use Binafy\LaravelReaction\Models\Reaction;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait InteractsWithReactions
{
    public function reaction(string|LaravelReactionTypeEnum $type, HasReaction $reactable): Reaction
    {
        $typeValue = $type instanceof LaravelReactionTypeEnum ? $type->value : $type;

        $reaction = $reactable->reactions()->updateOrCreate(
            [
                $this->reactionUserForeignKey() => $this->getKey(),
                'reactable_id' => $reactable->getKey(),
                'reactable_type' => $reactable::class,
            ],
            [
                'type' => $typeValue,
                'ip' => $this->resolveReactionIp(),
            ],
        );

        event(new \Binafy\LaravelReaction\Events\StoreReactionEvent($reaction));

        return $reaction;
    }

    public function removeReaction(string|LaravelReactionTypeEnum $type, HasReaction $reactable): bool
    {
        $typeValue = $type instanceof LaravelReactionTypeEnum ? $type->value : $type;

        $reaction = $reactable->reactions()
            ->where($this->reactionUserForeignKey(), $this->getKey())
            ->where('type', $typeValue)
            ->first();

        if ($reaction === null) {
            return false;
        }

        $reaction->delete();

        event(new \Binafy\LaravelReaction\Events\RemoveReactionEvent);

        return true;
    }

    public function removeReactions(HasReaction $reactable): bool
    {
        $reactable->reactions()
            ->where($this->reactionUserForeignKey(), $this->getKey())
            ->delete();

        event(new \Binafy\LaravelReaction\Events\RemoveAllReactionEvent);

        return true;
    }

    /**
     * @return HasMany<Reaction, $this>
     */
    public function reactionsGiven(): HasMany
    {
        return $this->hasMany(Reaction::class, $this->reactionUserForeignKey());
    }

    private function reactionUserForeignKey(): string
    {
        return config('laravel-reactions.user.foreign_key', 'user_id');
    }

    private function resolveReactionIp(): string
    {
        return request()?->ip() ?? 'cli';
    }
}
