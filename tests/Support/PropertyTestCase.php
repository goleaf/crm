<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Base test case for property-based tests.
 *
 * Provides common setup and utilities for property-based testing
 * of tasks, notes, activities, and related entities. This class
 * automatically sets up a team and authenticated user for each test,
 * and provides helper methods for generating random test data.
 *
 *
 * @see https://en.wikipedia.org/wiki/Property_testing
 */
abstract class PropertyTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * The team instance used for testing.
     */
    protected Team $team;

    /**
     * The authenticated user instance used for testing.
     */
    protected User $user;

    /**
     * Set up the test environment.
     *
     * Creates a team and authenticated user for each test.
     * The user is automatically attached to the team and authenticated.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a team and user for testing
        $this->team = Team::factory()->create();
        $this->user = User::factory()->create();
        $this->user->teams()->attach($this->team);
        $this->user->switchTeam($this->team);

        $this->actingAs($this->user);
    }

    /**
     * Run a property test with the specified number of iterations.
     *
     * Executes the provided test function multiple times to verify
     * that a property holds across different random inputs. If any
     * iteration fails, the exception is wrapped with iteration context.
     *
     * @param callable(int): void $test       The test function to run, receives iteration number
     * @param int                 $iterations Number of iterations (default: 100)
     *
     * @throws \InvalidArgumentException If iterations is less than 1
     * @throws \RuntimeException         If any iteration fails, wraps the original exception
     */
    protected function runPropertyTest(callable $test, int $iterations = 100): void
    {
        if ($iterations < 1) {
            throw new \InvalidArgumentException('Iterations must be at least 1');
        }

        for ($i = 0; $i < $iterations; $i++) {
            try {
                $test($i);
            } catch (\Throwable $e) {
                throw new \RuntimeException(
                    sprintf('Property test failed on iteration %d: %s', $i, $e->getMessage()),
                    0,
                    $e,
                );
            }
        }
    }

    /**
     * Generate a random subset of an array.
     *
     * Returns a random number of elements from the input array.
     * The subset size is randomly chosen between 0 and the array length.
     *
     * @template T
     *
     * @param array<T> $items The array to select from
     *
     * @return array<T> A random subset of the input array
     */
    protected function randomSubset(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $count = fake()->numberBetween(0, count($items));

        if ($count === 0) {
            return [];
        }

        return fake()->randomElements($items, $count);
    }

    /**
     * Generate a random date within a range.
     *
     * Creates a Carbon instance with a random date between the specified
     * start and end dates. Dates can be specified in any format accepted
     * by strtotime().
     *
     * @param string|null $startDate Start date in strtotime format (default: '-1 year')
     * @param string|null $endDate   End date in strtotime format (default: '+1 year')
     *
     * @return Carbon A random date within the specified range
     *
     * @throws \Exception If date parsing fails
     */
    protected function randomDate(?string $startDate = '-1 year', ?string $endDate = '+1 year'): Carbon
    {
        return \Illuminate\Support\Facades\Date::parse(
            fake()->dateTimeBetween($startDate, $endDate),
        );
    }

    /**
     * Generate a random boolean with optional bias.
     *
     * @param float $trueProbability Probability of returning true (0.0 to 1.0)
     *
     * @throws \InvalidArgumentException
     */
    protected function randomBoolean(float $trueProbability = 0.5): bool
    {
        if ($trueProbability < 0.0 || $trueProbability > 1.0) {
            throw new \InvalidArgumentException('Probability must be between 0.0 and 1.0');
        }

        return fake()->boolean((int) ($trueProbability * 100));
    }

    /**
     * Generate a random integer within a range.
     *
     * @param int $min Minimum value (inclusive)
     * @param int $max Maximum value (inclusive)
     *
     * @throws \InvalidArgumentException
     */
    protected function randomInt(int $min = 0, int $max = 100): int
    {
        if ($min > $max) {
            throw new \InvalidArgumentException('Minimum value cannot be greater than maximum value');
        }

        return fake()->numberBetween($min, $max);
    }

    /**
     * Generate a random string of specified length.
     *
     * @param int $length Length of the string (default: 10)
     *
     * @throws \InvalidArgumentException
     */
    protected function randomString(int $length = 10): string
    {
        if ($length < 1) {
            throw new \InvalidArgumentException('Length must be at least 1');
        }

        return fake()->lexify(str_repeat('?', $length));
    }

    /**
     * Generate a random email address.
     */
    protected function randomEmail(): string
    {
        return fake()->unique()->safeEmail();
    }

    /**
     * Create additional users for the current team.
     *
     * @param int $count Number of users to create
     *
     * @return array<User>
     */
    protected function createTeamUsers(int $count = 1): array
    {
        if ($count < 1) {
            throw new \InvalidArgumentException('Count must be at least 1');
        }

        $users = [];
        for ($i = 0; $i < $count; $i++) {
            $user = User::factory()->create();
            $user->teams()->attach($this->team);
            $user->load('teams'); // Reload the teams relationship
            $users[] = $user;
        }

        return $users;
    }

    /**
     * Reset the database state between property test iterations.
     *
     * This is useful when you need a clean slate for each iteration
     * but want to keep the base team and user.
     */
    protected function resetPropertyTestState(): void
    {
        // Refresh the team and user instances
        $this->team->refresh();
        $this->user->refresh();

        // Re-authenticate
        $this->actingAs($this->user);
    }
}
