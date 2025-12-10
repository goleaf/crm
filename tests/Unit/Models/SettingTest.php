<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

it('can create a setting', function (): void {
    $setting = Setting::create([
        'key' => 'test.key',
        'value' => 'test value',
        'type' => 'string',
        'group' => 'general',
    ]);

    expect($setting)->toBeInstanceOf(Setting::class)
        ->and($setting->key)->toBe('test.key')
        ->and($setting->value)->toBe('test value');
});

it('belongs to a team', function (): void {
    $team = Team::factory()->create();
    $setting = Setting::factory()->create(['team_id' => $team->id]);

    expect($setting->team)->toBeInstanceOf(Team::class)
        ->and($setting->team->id)->toBe($team->id);
});

it('can have null team_id for global settings', function (): void {
    $setting = Setting::create([
        'key' => 'global.key',
        'value' => 'global value',
        'type' => 'string',
        'group' => 'general',
        'team_id' => null,
    ]);

    expect($setting->team_id)->toBeNull();
});

it('casts is_public to boolean', function (): void {
    $setting = Setting::create([
        'key' => 'test.key',
        'value' => 'value',
        'type' => 'string',
        'group' => 'general',
        'is_public' => true,
    ]);

    expect($setting->is_public)->toBeTrue()
        ->and($setting->is_public)->toBeBool();
});

it('casts is_encrypted to boolean', function (): void {
    $setting = Setting::create([
        'key' => 'test.key',
        'value' => 'value',
        'type' => 'string',
        'group' => 'general',
        'is_encrypted' => true,
    ]);

    expect($setting->is_encrypted)->toBeTrue()
        ->and($setting->is_encrypted)->toBeBool();
});

it('gets string value correctly', function (): void {
    $setting = Setting::create([
        'key' => 'test.string',
        'value' => 'test value',
        'type' => 'string',
        'group' => 'general',
    ]);

    expect($setting->getValue())->toBe('test value');
});

it('gets boolean value correctly', function (): void {
    $setting = Setting::create([
        'key' => 'test.bool',
        'value' => '1',
        'type' => 'boolean',
        'group' => 'general',
    ]);

    expect($setting->getValue())->toBeTrue();

    $setting->value = '0';
    expect($setting->getValue())->toBeFalse();
});

it('gets integer value correctly', function (): void {
    $setting = Setting::create([
        'key' => 'test.int',
        'value' => '42',
        'type' => 'integer',
        'group' => 'general',
    ]);

    expect($setting->getValue())->toBe(42);
});

it('gets float value correctly', function (): void {
    $setting = Setting::create([
        'key' => 'test.float',
        'value' => '3.14',
        'type' => 'float',
        'group' => 'general',
    ]);

    expect($setting->getValue())->toBe(3.14);
});

it('gets json value correctly', function (): void {
    $data = ['key1' => 'value1', 'key2' => 'value2'];
    $setting = Setting::create([
        'key' => 'test.json',
        'value' => json_encode($data),
        'type' => 'json',
        'group' => 'general',
    ]);

    expect($setting->getValue())->toBe($data);
});

it('gets array value correctly', function (): void {
    $data = ['item1', 'item2', 'item3'];
    $setting = Setting::create([
        'key' => 'test.array',
        'value' => json_encode($data),
        'type' => 'array',
        'group' => 'general',
    ]);

    expect($setting->getValue())->toBe($data);
});

it('sets string value correctly', function (): void {
    $setting = new Setting([
        'key' => 'test.string',
        'type' => 'string',
        'group' => 'general',
    ]);

    $setting->setValue('test value');

    expect($setting->value)->toBe('test value');
});

it('sets boolean value correctly', function (): void {
    $setting = new Setting([
        'key' => 'test.bool',
        'type' => 'boolean',
        'group' => 'general',
    ]);

    $setting->setValue(true);
    expect($setting->value)->toBe('1');

    $setting->setValue(false);
    expect($setting->value)->toBe('0');
});

it('sets integer value correctly', function (): void {
    $setting = new Setting([
        'key' => 'test.int',
        'type' => 'integer',
        'group' => 'general',
    ]);

    $setting->setValue(42);

    expect($setting->value)->toBe('42');
});

it('sets json value correctly', function (): void {
    $setting = new Setting([
        'key' => 'test.json',
        'type' => 'json',
        'group' => 'general',
    ]);

    $data = ['key' => 'value'];
    $setting->setValue($data);

    expect($setting->value)->toBe(json_encode($data));
});

it('sets array value correctly', function (): void {
    $setting = new Setting([
        'key' => 'test.array',
        'type' => 'array',
        'group' => 'general',
    ]);

    $data = ['item1', 'item2'];
    $setting->setValue($data);

    expect($setting->value)->toBe(json_encode($data));
});

it('encrypts value when is_encrypted is true', function (): void {
    $setting = new Setting([
        'key' => 'test.encrypted',
        'type' => 'string',
        'group' => 'general',
        'is_encrypted' => true,
    ]);

    $setting->setValue('secret value');

    expect($setting->value)->not->toBe('secret value')
        ->and(Crypt::decryptString($setting->value))->toBe('secret value');
});

it('decrypts value when is_encrypted is true', function (): void {
    $setting = Setting::create([
        'key' => 'test.encrypted',
        'value' => Crypt::encryptString('secret value'),
        'type' => 'string',
        'group' => 'general',
        'is_encrypted' => true,
    ]);

    expect($setting->getValue())->toBe('secret value');
});

it('handles encrypted boolean values', function (): void {
    $setting = new Setting([
        'key' => 'test.encrypted.bool',
        'type' => 'boolean',
        'group' => 'general',
        'is_encrypted' => true,
    ]);

    $setting->setValue(true);
    $setting->save();

    expect($setting->fresh()->getValue())->toBeTrue();
});

it('handles encrypted json values', function (): void {
    $data = ['secret' => 'data'];
    $setting = new Setting([
        'key' => 'test.encrypted.json',
        'type' => 'json',
        'group' => 'general',
        'is_encrypted' => true,
    ]);

    $setting->setValue($data);
    $setting->save();

    expect($setting->fresh()->getValue())->toBe($data);
});

it('enforces unique key constraint', function (): void {
    Setting::create([
        'key' => 'duplicate.key',
        'value' => 'value1',
        'type' => 'string',
        'group' => 'general',
    ]);

    expect(fn () => Setting::create([
        'key' => 'duplicate.key',
        'value' => 'value2',
        'type' => 'string',
        'group' => 'general',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

it('allows same key for different teams', function (): void {
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $setting1 = Setting::create([
        'key' => 'team.key',
        'value' => 'value1',
        'type' => 'string',
        'group' => 'general',
        'team_id' => $team1->id,
    ]);

    $setting2 = Setting::create([
        'key' => 'team.key',
        'value' => 'value2',
        'type' => 'string',
        'group' => 'general',
        'team_id' => $team2->id,
    ]);

    expect($setting1->value)->toBe('value1')
        ->and($setting2->value)->toBe('value2');
});

it('cascades delete when team is deleted', function (): void {
    $team = Team::factory()->create();
    $setting = Setting::create([
        'key' => 'team.key',
        'value' => 'value',
        'type' => 'string',
        'group' => 'general',
        'team_id' => $team->id,
    ]);

    $team->delete();

    expect(Setting::find($setting->id))->toBeNull();
});

it('has timestamps', function (): void {
    $setting = Setting::create([
        'key' => 'test.key',
        'value' => 'value',
        'type' => 'string',
        'group' => 'general',
    ]);

    expect($setting->created_at)->not->toBeNull()
        ->and($setting->updated_at)->not->toBeNull();
});

it('updates updated_at timestamp on save', function (): void {
    $setting = Setting::create([
        'key' => 'test.key',
        'value' => 'initial',
        'type' => 'string',
        'group' => 'general',
    ]);

    $originalUpdatedAt = $setting->updated_at;

    \Illuminate\Support\Sleep::sleep(1);

    $setting->value = 'updated';
    $setting->save();

    expect($setting->updated_at->isAfter($originalUpdatedAt))->toBeTrue();
});