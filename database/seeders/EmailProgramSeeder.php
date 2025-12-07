<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EmailProgram;
use App\Models\EmailProgramAnalytic;
use App\Models\EmailProgramRecipient;
use App\Models\EmailProgramStep;
use App\Models\EmailProgramUnsubscribe;
use App\Models\People;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class EmailProgramSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating email programs with steps and recipients...');

        $teams = Team::all();
        $users = User::all();
        $people = People::all();

        if ($teams->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No teams or users found.');

            return;
        }

        $programCount = 0;
        foreach ($teams as $team) {
            $programs = EmailProgram::factory()
                ->count(2)
                ->create([
                    'team_id' => $team->id,
                    'name' => fn (): string => 'Nurture '.Str::random(5),
                    'status' => 'scheduled',
                    'audience_filters' => ['segment' => 'all'],
                    'estimated_audience_size' => 50,
                    'scheduled_start_at' => now()->addDays(1),
                    'respect_quiet_hours' => true,
                    'quiet_hours_start' => '22:00',
                    'quiet_hours_end' => '06:00',
                ]);

            foreach ($programs as $program) {
                $programCount++;
                $steps = EmailProgramStep::factory()
                    ->count(2)
                    ->create([
                        'email_program_id' => $program->id,
                        'name' => fn (array $attributes): string => 'Email Step '.(($attributes['step_order'] ?? 0) + 1),
                        'subject_line' => fn (): string => 'Hello '.fake()->company(),
                        'html_content' => '<p>Hi {{ first_name }}, here is an update.</p>',
                        'delay_value' => 1,
                        'delay_unit' => 'days',
                        'from_name' => 'Marketing Team',
                        'from_email' => 'marketing@example.com',
                    ]);

                $recipients = collect(range(1, 10))->map(function () use ($people, $program, $steps) {
                    $person = $people->random() ?? null;
                    $email = $person?->email ?? fake()->safeEmail();

                    return EmailProgramRecipient::create([
                        'email_program_id' => $program->id,
                        'email_program_step_id' => $steps->random()->id,
                        'email' => $email,
                        'first_name' => $person?->first_name ?? fake()->firstName(),
                        'last_name' => $person?->last_name ?? fake()->lastName(),
                        'recipient_type' => $person ? $person::class : null,
                        'recipient_id' => $person?->id,
                        'status' => Arr::random(['pending', 'queued', 'sent', 'delivered']),
                        'scheduled_send_at' => now()->addDay(),
                        'open_count' => random_int(0, 2),
                        'click_count' => random_int(0, 1),
                    ]);
                });

                // Mark a couple as unsubscribed
                $recipients->take(2)->each(fn (EmailProgramRecipient $recipient) => EmailProgramUnsubscribe::create([
                    'team_id' => $program->team_id,
                    'email' => $recipient->email,
                    'email_program_id' => $program->id,
                    'reason' => 'too_frequent',
                    'feedback' => 'Seed data unsubscribe',
                ]));

                EmailProgramAnalytic::create([
                    'email_program_id' => $program->id,
                    'email_program_step_id' => $steps->first()->id,
                    'date' => now()->toDateString(),
                    'sent_count' => $recipients->count(),
                    'delivered_count' => $recipients->count() - 1,
                    'opened_count' => random_int(0, $recipients->count()),
                    'unique_opens' => random_int(0, $recipients->count()),
                    'clicked_count' => random_int(0, $recipients->count()),
                    'unique_clicks' => random_int(0, $recipients->count()),
                    'bounced_count' => 1,
                    'unsubscribed_count' => 2,
                    'complained_count' => 0,
                ]);
            }
        }

        $this->command->info("✓ Created {$programCount} email programs with steps, recipients, and analytics");
    }
}
