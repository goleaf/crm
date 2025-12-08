<?php

declare(strict_types=1);

namespace App\Support\Reactions;

use Binafy\LaravelReaction\Enums\LaravelReactionTypeEnum;
use Illuminate\Support\Str;

final class ReactionOptions
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(LaravelReactionTypeEnum::cases())
            ->mapWithKeys(function (LaravelReactionTypeEnum $type): array {
                $key = "app.reactions.types.{$type->value}";
                $label = __($key);

                return [$type->value => $label === $key ? Str::headline($type->value) : $label];
            })
            ->all();
    }

    public static function default(): string
    {
        return LaravelReactionTypeEnum::REACTION_LIKE->value;
    }
}
