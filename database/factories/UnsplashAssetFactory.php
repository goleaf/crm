<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\UnsplashAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnsplashAsset>
 */
final class UnsplashAssetFactory extends Factory
{
    protected $model = UnsplashAsset::class;

    public function definition(): array
    {
        $unsplashId = fake()->uuid();
        $photographerUsername = fake()->userName();

        return [
            'unsplash_id' => $unsplashId,
            'slug' => fake()->slug(),
            'description' => fake()->sentence(),
            'alt_description' => fake()->sentence(),
            'urls' => [
                'raw' => "https://images.unsplash.com/photo-{$unsplashId}?ixlib=rb-4.0.3",
                'full' => "https://images.unsplash.com/photo-{$unsplashId}?ixlib=rb-4.0.3&q=85&fm=jpg&crop=entropy&cs=srgb&w=2000",
                'regular' => "https://images.unsplash.com/photo-{$unsplashId}?ixlib=rb-4.0.3&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=1080",
                'small' => "https://images.unsplash.com/photo-{$unsplashId}?ixlib=rb-4.0.3&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=400",
                'thumb' => "https://images.unsplash.com/photo-{$unsplashId}?ixlib=rb-4.0.3&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=200",
            ],
            'links' => [
                'self' => "https://api.unsplash.com/photos/{$unsplashId}",
                'html' => "https://unsplash.com/photos/{$unsplashId}",
                'download' => "https://unsplash.com/photos/{$unsplashId}/download",
                'download_location' => "https://api.unsplash.com/photos/{$unsplashId}/download",
            ],
            'width' => fake()->numberBetween(2000, 6000),
            'height' => fake()->numberBetween(1500, 4000),
            'color' => fake()->hexColor(),
            'likes' => fake()->numberBetween(0, 1000),
            'liked_by_user' => fake()->boolean(10),
            'photographer_name' => fake()->name(),
            'photographer_username' => $photographerUsername,
            'photographer_url' => "https://unsplash.com/@{$photographerUsername}",
            'download_location' => "https://api.unsplash.com/photos/{$unsplashId}/download",
            'local_path' => null,
            'downloaded_at' => null,
            'exif' => null,
            'location' => null,
            'tags' => null,
        ];
    }

    public function downloaded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'local_path' => 'unsplash/' . $attributes['unsplash_id'] . '.jpg',
            'downloaded_at' => now(),
        ]);
    }

    public function withLocation(): static
    {
        return $this->state(fn (array $attributes): array => [
            'location' => [
                'city' => fake()->city(),
                'country' => fake()->country(),
                'position' => [
                    'latitude' => fake()->latitude(),
                    'longitude' => fake()->longitude(),
                ],
            ],
        ]);
    }

    public function withExif(): static
    {
        return $this->state(fn (array $attributes): array => [
            'exif' => [
                'make' => fake()->randomElement(['Canon', 'Nikon', 'Sony', 'Fujifilm']),
                'model' => fake()->randomElement(['EOS R5', 'D850', 'A7R IV', 'X-T4']),
                'exposure_time' => '1/' . fake()->numberBetween(100, 8000),
                'aperture' => 'f/' . fake()->randomFloat(1, 1.4, 22),
                'focal_length' => fake()->numberBetween(14, 600) . 'mm',
                'iso' => fake()->randomElement([100, 200, 400, 800, 1600, 3200]),
            ],
        ]);
    }

    public function withTags(): static
    {
        return $this->state(fn (array $attributes): array => [
            'tags' => collect(fake()->words(5))->map(fn ($word): array => [
                'title' => $word,
            ])->all(),
        ]);
    }

    public function popular(): static
    {
        return $this->state(fn (array $attributes): array => [
            'likes' => fake()->numberBetween(1000, 10000),
        ]);
    }
}
