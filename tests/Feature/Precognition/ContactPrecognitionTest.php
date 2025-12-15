<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\People;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->team = Team::factory()->create();
    $this->user->currentTeam()->associate($this->team);
    $this->user->save();

    $this->company = Company::factory()->create(['team_id' => $this->team->id]);
});

describe('Contact Precognitive Validation', function (): void {
    it('validates contact name precognitively', function (): void {
        actingAs($this->user)
            ->postJson('/api/contacts', [
                'name' => '',
                'email' => 'test@example.com',
                'company_id' => $this->company->id,
            ], [
                'Precognition' => 'true',
                'Precognition-Validate-Only' => 'name',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('validates contact email format precognitively', function (): void {
        actingAs($this->user)
            ->postJson('/api/contacts', [
                'name' => 'John Doe',
                'email' => 'invalid-email',
                'company_id' => $this->company->id,
            ], [
                'Precognition' => 'true',
                'Precognition-Validate-Only' => 'email',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('allows valid email during precognitive validation', function (): void {
        actingAs($this->user)
            ->postJson('/api/contacts', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'company_id' => $this->company->id,
            ], [
                'Precognition' => 'true',
                'Precognition-Validate-Only' => 'email',
            ])->assertNoContent();
    });

    it('detects duplicate email during precognitive validation', function (): void {
        People::factory()->create([
            'email' => 'existing@example.com',
            'team_id' => $this->team->id,
        ]);

        actingAs($this->user)
            ->postJson('/api/contacts', [
                'name' => 'Jane Doe',
                'email' => 'existing@example.com',
                'company_id' => $this->company->id,
            ], [
                'Precognition' => 'true',
                'Precognition-Validate-Only' => 'email',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('validates company_id exists precognitively', function (): void {
        actingAs($this->user)
            ->postJson('/api/contacts', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'company_id' => 99999,
            ], [
                'Precognition' => 'true',
                'Precognition-Validate-Only' => 'company_id',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['company_id']);
    });

    it('validates multiple fields precognitively', function (): void {
        actingAs($this->user)
            ->postJson('/api/contacts', [
                'name' => '',
                'email' => 'invalid-email',
                'company_id' => 99999,
            ], [
                'Precognition' => 'true',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'company_id']);
    });

    it('does not save data during precognitive validation', function (): void {
        $initialCount = People::count();

        actingAs($this->user)
            ->postJson('/api/contacts', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'company_id' => $this->company->id,
            ], [
                'Precognition' => 'true',
            ])->assertNoContent();

        expect(People::count())->toBe($initialCount);
    });

    it('saves data during actual submission', function (): void {
        actingAs($this->user)
            ->postJson('/api/contacts', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'company_id' => $this->company->id,
            ])
            ->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'company_id',
                ],
            ]);

        expect(People::where('email', 'john@example.com')->exists())->toBeTrue();
    });

    it('validates phone format precognitively', function (): void {
        actingAs($this->user)
            ->postJson('/api/contacts', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'company_id' => $this->company->id,
                'phone' => str_repeat('x', 51), // Exceeds max length
            ], [
                'Precognition' => 'true',
                'Precognition-Validate-Only' => 'phone',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    });

    it('allows optional fields to be null during precognitive validation', function (): void {
        actingAs($this->user)
            ->postJson('/api/contacts', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'company_id' => $this->company->id,
                'phone' => null,
                'title' => null,
                'department' => null,
            ], [
                'Precognition' => 'true',
            ])->assertNoContent();
    });
});

describe('Contact Update Precognitive Validation', function (): void {
    beforeEach(function (): void {
        $this->contact = People::factory()->create([
            'email' => 'original@example.com',
            'team_id' => $this->team->id,
            'company_id' => $this->company->id,
        ]);
    });

    it('allows same email during update precognitive validation', function (): void {
        actingAs($this->user)
            ->putJson("/api/contacts/{$this->contact->id}", [
                'name' => 'Updated Name',
                'email' => 'original@example.com', // Same email
                'company_id' => $this->company->id,
            ], [
                'Precognition' => 'true',
                'Precognition-Validate-Only' => 'email',
            ])->assertNoContent();
    });

    it('detects duplicate email during update precognitive validation', function (): void {
        $otherContact = People::factory()->create([
            'email' => 'other@example.com',
            'team_id' => $this->team->id,
        ]);

        actingAs($this->user)
            ->putJson("/api/contacts/{$this->contact->id}", [
                'name' => 'Updated Name',
                'email' => 'other@example.com', // Duplicate
                'company_id' => $this->company->id,
            ], [
                'Precognition' => 'true',
                'Precognition-Validate-Only' => 'email',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('validates all fields during update precognitive validation', function (): void {
        actingAs($this->user)
            ->putJson("/api/contacts/{$this->contact->id}", [
                'name' => '',
                'email' => 'invalid-email',
                'company_id' => 99999,
            ], [
                'Precognition' => 'true',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'company_id']);
    });

    it('does not update data during precognitive validation', function (): void {
        $originalName = $this->contact->name;

        actingAs($this->user)
            ->putJson("/api/contacts/{$this->contact->id}", [
                'name' => 'New Name',
                'email' => $this->contact->email,
                'company_id' => $this->company->id,
            ], [
                'Precognition' => 'true',
            ])->assertNoContent();

        expect($this->contact->fresh()->name)->toBe($originalName);
    });

    it('updates data during actual submission', function (): void {
        actingAs($this->user)
            ->putJson("/api/contacts/{$this->contact->id}", [
                'name' => 'Updated Name',
                'email' => $this->contact->email,
                'company_id' => $this->company->id,
            ])->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                ],
            ]);

        expect($this->contact->fresh()->name)->toBe('Updated Name');
    });
});

describe('Precognition Performance', function (): void {
    it('validates single field without loading unnecessary data', function (): void {
        $queries = 0;
        \DB::listen(function () use (&$queries): void {
            $queries++;
        });

        actingAs($this->user)
            ->postJson('/api/contacts', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'company_id' => $this->company->id,
            ], [
                'Precognition' => 'true',
                'Precognition-Validate-Only' => 'name',
            ])->assertNoContent();

        // Should only run minimal queries for validation
        expect($queries)->toBeLessThan(10);
    });

    it('handles concurrent precognitive requests', function (): void {
        $responses = [];

        for ($i = 0; $i < 5; $i++) {
            $responses[] = actingAs($this->user)
                ->postJson('/api/contacts', [
                    'name' => "User {$i}",
                    'email' => "user{$i}@example.com",
                    'company_id' => $this->company->id,
                ], [
                    'Precognition' => 'true',
                    'Precognition-Validate-Only' => 'email',
                ])->assertNoContent();
        }

        expect(count($responses))->toBe(5);
    });
});

describe('Precognition Error Messages', function (): void {
    it('returns translated error messages', function (): void {
        actingAs($this->user)
            ->postJson('/api/contacts', [
                'name' => '',
                'email' => 'test@example.com',
                'company_id' => $this->company->id,
            ], [
                'Precognition' => 'true',
                'Precognition-Validate-Only' => 'name',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name'])
            ->assertJson([
                'errors' => [
                    'name' => [__('app.validation.contact_name_required')],
                ],
            ]);
    });

    it('returns custom validation messages for email', function (): void {
        actingAs($this->user)
            ->postJson('/api/contacts', [
                'name' => 'John Doe',
                'email' => 'invalid',
                'company_id' => $this->company->id,
            ], [
                'Precognition' => 'true',
                'Precognition-Validate-Only' => 'email',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });
});

describe('Precognition with Team Scoping', function (): void {
    it('scopes unique email validation to current team', function (): void {
        $otherTeam = Team::factory()->create();

        // Create contact in other team with same email
        People::factory()->create([
            'email' => 'shared@example.com',
            'team_id' => $otherTeam->id,
        ]);

        // Should allow same email in different team
        actingAs($this->user)
            ->postJson('/api/contacts', [
                'name' => 'John Doe',
                'email' => 'shared@example.com',
                'company_id' => $this->company->id,
            ], [
                'Precognition' => 'true',
                'Precognition-Validate-Only' => 'email',
            ])->assertNoContent();
    });

    it('detects duplicate email within same team', function (): void {
        People::factory()->create([
            'email' => 'duplicate@example.com',
            'team_id' => $this->team->id,
        ]);

        actingAs($this->user)
            ->postJson('/api/contacts', [
                'name' => 'Jane Doe',
                'email' => 'duplicate@example.com',
                'company_id' => $this->company->id,
            ], [
                'Precognition' => 'true',
                'Precognition-Validate-Only' => 'email',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });
});
