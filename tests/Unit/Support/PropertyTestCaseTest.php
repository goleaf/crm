<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\Support\PropertyTestCase;

/**
 * Tests for the PropertyTestCase base class.
 *
 * Validates all utility methods and setup behavior.
 */
final class PropertyTestCaseTest extends PropertyTestCase
{
    public function test_setup_creates_team_and_user(): void
    {
        expect($this->team)->toBeInstanceOf(Team::class);
        expect($this->user)->toBeInstanceOf(User::class);
        expect($this->team->exists)->toBeTrue();
        expect($this->user->exists)->toBeTrue();
    }

    public function test_user_is_attached_to_team(): void
    {
        expect($this->user->teams->pluck('id'))->toContain($this->team->id);
        expect($this->user->belongsToTeam($this->team))->toBeTrue();
    }

    public function test_user_has_current_team_set(): void
    {
        expect($this->user->currentTeam)->not->toBeNull();
        expect($this->user->currentTeam->id)->toBe($this->team->id);
    }

    public function test_user_is_authenticated(): void
    {
        expect(auth()->check())->toBeTrue();
        expect(auth()->user()->id)->toBe($this->user->id);
    }

    public function test_run_property_test_executes_correct_number_of_iterations(): void
    {
        $counter = 0;

        $this->runPropertyTest(function () use (&$counter): void {
            $counter++;
        }, 50);

        expect($counter)->toBe(50);
    }

    public function test_run_property_test_passes_iteration_number(): void
    {
        $iterations = [];

        $this->runPropertyTest(function (int $i) use (&$iterations): void {
            $iterations[] = $i;
        }, 10);

        expect($iterations)->toBe([0, 1, 2, 3, 4, 5, 6, 7, 8, 9]);
    }

    public function test_run_property_test_throws_exception_for_invalid_iterations(): void
    {
        expect(fn () => $this->runPropertyTest(fn (): null => null, 0))
            ->toThrow(\InvalidArgumentException::class, 'Iterations must be at least 1');

        expect(fn () => $this->runPropertyTest(fn (): null => null, -1))
            ->toThrow(\InvalidArgumentException::class, 'Iterations must be at least 1');
    }

    public function test_run_property_test_wraps_exceptions_with_iteration_info(): void
    {
        try {
            $this->runPropertyTest(function (int $i): void {
                if ($i === 5) {
                    throw new \Exception('Test failure');
                }
            }, 10);

            $this->fail('Expected RuntimeException to be thrown');
        } catch (\RuntimeException $e) {
            expect($e->getMessage())->toContain('iteration 5');
            expect($e->getMessage())->toContain('Test failure');
            expect($e->getPrevious())->toBeInstanceOf(\Exception::class);
        }
    }

    public function test_random_subset_returns_empty_array_for_empty_input(): void
    {
        $result = $this->randomSubset([]);

        expect($result)->toBe([]);
    }

    public function test_random_subset_returns_subset_of_items(): void
    {
        $items = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

        // Run multiple times to test randomness
        for ($i = 0; $i < 20; $i++) {
            $subset = $this->randomSubset($items);

            expect($subset)->toBeArray();
            expect(count($subset))->toBeLessThanOrEqual(count($items));

            // All items in subset should be from original array
            foreach ($subset as $item) {
                expect($items)->toContain($item);
            }
        }
    }

    public function test_random_subset_can_return_empty_subset(): void
    {
        $items = [1, 2, 3];
        $foundEmpty = false;

        // Try multiple times to get an empty subset
        for ($i = 0; $i < 50; $i++) {
            if ($this->randomSubset($items) === []) {
                $foundEmpty = true;
                break;
            }
        }

        expect($foundEmpty)->toBeTrue('Should occasionally return empty subset');
    }

    public function test_random_subset_can_return_full_set(): void
    {
        $items = [1, 2, 3];
        $foundFull = false;

        // Try multiple times to get the full set
        for ($i = 0; $i < 50; $i++) {
            if (count($this->randomSubset($items)) === count($items)) {
                $foundFull = true;
                break;
            }
        }

        expect($foundFull)->toBeTrue('Should occasionally return full set');
    }

    public function test_random_date_returns_carbon_instance(): void
    {
        $date = $this->randomDate();

        expect($date)->toBeInstanceOf(Carbon::class);
    }

    public function test_random_date_respects_range(): void
    {
        $start = \Illuminate\Support\Facades\Date::parse('-1 year');
        $end = \Illuminate\Support\Facades\Date::parse('+1 year');

        for ($i = 0; $i < 20; $i++) {
            $date = $this->randomDate('-1 year', '+1 year');

            expect($date->greaterThanOrEqualTo($start))->toBeTrue();
            expect($date->lessThanOrEqualTo($end))->toBeTrue();
        }
    }

    public function test_random_date_with_custom_range(): void
    {
        $start = \Illuminate\Support\Facades\Date::parse('2020-01-01');
        $end = \Illuminate\Support\Facades\Date::parse('2020-12-31');

        for ($i = 0; $i < 20; $i++) {
            $date = $this->randomDate('2020-01-01', '2020-12-31');

            expect($date->greaterThanOrEqualTo($start))->toBeTrue();
            expect($date->lessThanOrEqualTo($end))->toBeTrue();
        }
    }

    public function test_random_boolean_returns_boolean(): void
    {
        $result = $this->randomBoolean();

        expect($result)->toBeBool();
    }

    public function test_random_boolean_with_default_probability(): void
    {
        $trueCount = 0;
        $iterations = 1000;

        for ($i = 0; $i < $iterations; $i++) {
            if ($this->randomBoolean()) {
                $trueCount++;
            }
        }

        // With 50% probability, expect roughly 40-60% true values
        $percentage = $trueCount / $iterations;
        expect($percentage)->toBeGreaterThan(0.4);
        expect($percentage)->toBeLessThan(0.6);
    }

    public function test_random_boolean_with_high_probability(): void
    {
        $trueCount = 0;
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            if ($this->randomBoolean(0.9)) {
                $trueCount++;
            }
        }

        // With 90% probability, expect at least 75% true values
        expect($trueCount / $iterations)->toBeGreaterThan(0.75);
    }

    public function test_random_boolean_with_low_probability(): void
    {
        $trueCount = 0;
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            if ($this->randomBoolean(0.1)) {
                $trueCount++;
            }
        }

        // With 10% probability, expect at most 25% true values
        expect($trueCount / $iterations)->toBeLessThan(0.25);
    }

    public function test_random_boolean_throws_exception_for_invalid_probability(): void
    {
        expect(fn (): bool => $this->randomBoolean(-0.1))
            ->toThrow(\InvalidArgumentException::class, 'Probability must be between 0.0 and 1.0');

        expect(fn (): bool => $this->randomBoolean(1.1))
            ->toThrow(\InvalidArgumentException::class, 'Probability must be between 0.0 and 1.0');
    }

    public function test_random_int_returns_integer(): void
    {
        $result = $this->randomInt();

        expect($result)->toBeInt();
    }

    public function test_random_int_respects_range(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $result = $this->randomInt(10, 20);

            expect($result)->toBeGreaterThanOrEqual(10);
            expect($result)->toBeLessThanOrEqual(20);
        }
    }

    public function test_random_int_with_default_range(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $result = $this->randomInt();

            expect($result)->toBeGreaterThanOrEqual(0);
            expect($result)->toBeLessThanOrEqual(100);
        }
    }

    public function test_random_int_throws_exception_for_invalid_range(): void
    {
        expect(fn (): int => $this->randomInt(20, 10))
            ->toThrow(\InvalidArgumentException::class, 'Minimum value cannot be greater than maximum value');
    }

    public function test_random_string_returns_string(): void
    {
        $result = $this->randomString();

        expect($result)->toBeString();
    }

    public function test_random_string_respects_length(): void
    {
        expect(strlen($this->randomString(5)))->toBe(5);
        expect(strlen($this->randomString(10)))->toBe(10);
        expect(strlen($this->randomString(20)))->toBe(20);
    }

    public function test_random_string_with_default_length(): void
    {
        $result = $this->randomString();

        expect(strlen($result))->toBe(10);
    }

    public function test_random_string_throws_exception_for_invalid_length(): void
    {
        expect(fn (): string => $this->randomString(0))
            ->toThrow(\InvalidArgumentException::class, 'Length must be at least 1');

        expect(fn (): string => $this->randomString(-1))
            ->toThrow(\InvalidArgumentException::class, 'Length must be at least 1');
    }

    public function test_random_email_returns_valid_email(): void
    {
        $email = $this->randomEmail();

        expect($email)->toBeString();
        expect(filter_var($email, FILTER_VALIDATE_EMAIL))->toBeTruthy();
    }

    public function test_random_email_returns_unique_emails(): void
    {
        $emails = [];

        for ($i = 0; $i < 10; $i++) {
            $emails[] = $this->randomEmail();
        }

        // All emails should be unique
        expect(count($emails))->toBe(count(array_unique($emails)));
    }

    public function test_create_team_users_creates_correct_number(): void
    {
        $users = $this->createTeamUsers(3);

        expect($users)->toHaveCount(3);
        expect($users[0])->toBeInstanceOf(User::class);
        expect($users[1])->toBeInstanceOf(User::class);
        expect($users[2])->toBeInstanceOf(User::class);
    }

    public function test_create_team_users_attaches_to_current_team(): void
    {
        $users = $this->createTeamUsers(2);

        foreach ($users as $user) {
            expect($user->teams->pluck('id'))->toContain($this->team->id);
            expect($user->belongsToTeam($this->team))->toBeTrue();
        }
    }

    public function test_create_team_users_with_single_user(): void
    {
        $users = $this->createTeamUsers(1);

        expect($users)->toHaveCount(1);
        expect($users[0]->belongsToTeam($this->team))->toBeTrue();
    }

    public function test_create_team_users_throws_exception_for_invalid_count(): void
    {
        expect(fn (): array => $this->createTeamUsers(0))
            ->toThrow(\InvalidArgumentException::class, 'Count must be at least 1');

        expect(fn (): array => $this->createTeamUsers(-1))
            ->toThrow(\InvalidArgumentException::class, 'Count must be at least 1');
    }

    public function test_reset_property_test_state_refreshes_models(): void
    {
        $this->team->update(['name' => 'Modified Name']);

        // Reset state
        $this->resetPropertyTestState();

        // Team should be refreshed but still modified in DB
        expect($this->team->name)->toBe('Modified Name');
    }

    public function test_reset_property_test_state_maintains_authentication(): void
    {
        $this->resetPropertyTestState();

        expect(auth()->check())->toBeTrue();
        expect(auth()->user()->id)->toBe($this->user->id);
    }

    public function test_multiple_property_tests_can_run_sequentially(): void
    {
        $firstCounter = 0;
        $this->runPropertyTest(function () use (&$firstCounter): void {
            $firstCounter++;
        }, 10);

        $secondCounter = 0;
        $this->runPropertyTest(function () use (&$secondCounter): void {
            $secondCounter++;
        }, 15);

        expect($firstCounter)->toBe(10);
        expect($secondCounter)->toBe(15);
    }

    public function test_property_test_can_access_team_and_user(): void
    {
        $this->runPropertyTest(function (): void {
            expect($this->team)->toBeInstanceOf(Team::class);
            expect($this->user)->toBeInstanceOf(User::class);
            expect($this->team->exists)->toBeTrue();
            expect($this->user->exists)->toBeTrue();
        }, 5);
    }
}
