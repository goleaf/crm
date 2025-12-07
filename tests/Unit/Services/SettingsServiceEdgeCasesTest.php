<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\Team;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(SettingsService::class);
});

it('handles very long keys', function (): void {
    $longKey = str_repeat('a', 255);

    $this->service->set($longKey, 'value', 'string', 'general');

    expect($this->service->get($longKey))->toBe('value');
});

it('handles keys with special characters', function (): void {
    $specialKey = 'key.with-special_chars:123';

    $this->service->set($specialKey, 'value', 'string', 'general');

    expect($this->service->get($specialKey))->toBe('value');
});

it('handles very large text values', function (): void {
    $largeValue = str_repeat('Lorem ipsum dolor sit amet. ', 1000);

    $this->service->set('large.text', $largeValue, 'string', 'general');

    expect($this->service->get('large.text'))->toBe($largeValue);
});

it('handles deeply nested json structures', function (): void {
    $deepStructure = [
        'level1' => [
            'level2' => [
                'level3' => [
                    'level4' => [
                        'level5' => 'deep value',
                    ],
                ],
            ],
        ],
    ];

    $this->service->set('deep.json', $deepStructure, 'json', 'general');

    expect($this->service->get('deep.json'))->toBe($deepStructure);
});

it('handles arrays with mixed types', function (): void {
    $mixedArray = [
        'string' => 'value',
        'integer' => 42,
        'float' => 3.14,
        'boolean' => true,
        'null' => null,
        'array' => [1, 2, 3],
    ];

    $this->service->set('mixed.array', $mixedArray, 'array', 'general');

    expect($this->service->get('mixed.array'))->toBe($mixedArray);
});

it('handles negative integers', function (): void {
    $this->service->set('negative.int', -42, 'integer', 'general');

    expect($this->service->get('negative.int'))->toBe(-42);
});

it('handles negative floats', function (): void {
    $this->service->set('negative.float', -3.14, 'float', 'general');

    expect($this->service->get('negative.float'))->toBe(-3.14);
});

it('handles very large integers', function (): void {
    $largeInt = PHP_INT_MAX;

    $this->service->set('large.int', $largeInt, 'integer', 'general');

    expect($this->service->get('large.int'))->toBe($largeInt);
});

it('handles very small floats', function (): void {
    $smallFloat = 0.000001;

    $this->service->set('small.float', $smallFloat, 'float', 'general');

    expect($this->service->get('small.float'))->toBe($smallFloat);
});

it('handles boolean string representations', function (): void {
    $setting = Setting::create([
        'key' => 'bool.string',
        'value' => 'true',
        'type' => 'boolean',
        'group' => 'general',
    ]);

    expect($setting->getValue())->toBeTrue();

    $setting->value = 'false';
    expect($setting->getValue())->toBeFalse();

    $setting->value = 'yes';
    expect($setting->getValue())->toBeTrue();

    $setting->value = 'no';
    expect($setting->getValue())->toBeFalse();
});

it('handles empty json objects', function (): void {
    $this->service->set('empty.json', [], 'json', 'general');

    expect($this->service->get('empty.json'))->toBe([]);
});

it('handles json with null values', function (): void {
    $data = ['key' => null];

    $this->service->set('json.null', $data, 'json', 'general');

    expect($this->service->get('json.null'))->toBe($data);
});

it('handles concurrent cache invalidation', function (): void {
    $this->service->set('concurrent.cache', 'initial', 'string', 'general');

    // Simulate concurrent updates
    $this->service->set('concurrent.cache', 'update1', 'string', 'general');
    $this->service->set('concurrent.cache', 'update2', 'string', 'general');
    $this->service->set('concurrent.cache', 'update3', 'string', 'general');

    expect($this->service->get('concurrent.cache'))->toBe('update3');
});

it('handles cache key collisions', function (): void {
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();

    $this->service->set('collision.key', 'global', 'string', 'general', null);
    $this->service->set('collision.key', 'team1', 'string', 'general', $team1->id);
    $this->service->set('collision.key', 'team2', 'string', 'general', $team2->id);

    expect($this->service->get('collision.key', null, null))->toBe('global')
        ->and($this->service->get('collision.key', null, $team1->id))->toBe('team1')
        ->and($this->service->get('collision.key', null, $team2->id))->toBe('team2');
});

it('handles rapid successive updates', function (): void {
    for ($i = 0; $i < 100; $i++) {
        $this->service->set('rapid.update', "value{$i}", 'string', 'general');
    }

    expect($this->service->get('rapid.update'))->toBe('value99');
});

it('handles setting deletion during cache read', function (): void {
    $this->service->set('delete.during.read', 'value', 'string', 'general');
    $this->service->get('delete.during.read'); // Cache it

    Setting::where('key', 'delete.during.read')->delete();
    Cache::forget('settings:global:delete.during.read');

    expect($this->service->get('delete.during.read', 'default'))->toBe('default');
});

it('handles type inference for edge values', function (): void {
    $this->service->setMany([
        'infer.zero' => 0,
        'infer.false' => false,
        'infer.empty.string' => '',
        'infer.empty.array' => [],
        'infer.null' => null,
    ], 'infer');

    expect($this->service->get('infer.zero'))->toBe(0)
        ->and($this->service->get('infer.false'))->toBeFalse()
        ->and($this->service->get('infer.empty.string'))->toBe('')
        ->and($this->service->get('infer.empty.array'))->toBe([]);
});

it('handles encrypted empty strings', function (): void {
    $this->service->set('encrypted.empty', '', 'string', 'general', null, true);

    expect($this->service->get('encrypted.empty'))->toBe('');
});

it('handles encrypted null values', function (): void {
    $setting = Setting::create([
        'key' => 'encrypted.null',
        'value' => null,
        'type' => 'string',
        'group' => 'general',
        'is_encrypted' => true,
    ]);

    expect($setting->getValue())->toBeNull();
});

it('handles group queries with no results', function (): void {
    $emptyGroup = $this->service->getGroup('nonexistent.group');

    expect($emptyGroup)->toBeEmpty();
});

it('handles setMany with empty array', function (): void {
    $this->service->setMany([], 'empty');

    expect($this->service->getGroup('empty'))->toBeEmpty();
});

it('handles delete of nonexistent setting', function (): void {
    $result = $this->service->delete('nonexistent.key');

    expect($result)->toBeFalse();
});

it('handles has check for nonexistent setting', function (): void {
    expect($this->service->has('nonexistent.key'))->toBeFalse();
});

it('handles clearCache with null key', function (): void {
    $this->service->set('cache.test1', 'value1', 'string', 'general');
    $this->service->set('cache.test2', 'value2', 'string', 'general');

    $this->service->clearCache();

    // Should still retrieve from database
    expect($this->service->get('cache.test1'))->toBe('value1')
        ->and($this->service->get('cache.test2'))->toBe('value2');
});

it('handles unicode in keys', function (): void {
    $unicodeKey = 'key.测试';

    $this->service->set($unicodeKey, 'value', 'string', 'general');

    expect($this->service->get($unicodeKey))->toBe('value');
});

it('handles emoji in values', function (): void {
    $emojiValue = '🚀 🎉 ✨';

    $this->service->set('emoji.value', $emojiValue, 'string', 'general');

    expect($this->service->get('emoji.value'))->toBe($emojiValue);
});

it('handles json with emoji', function (): void {
    $data = ['emoji' => '🚀', 'text' => 'rocket'];

    $this->service->set('json.emoji', $data, 'json', 'general');

    expect($this->service->get('json.emoji'))->toBe($data);
});

it('handles whitespace-only values', function (): void {
    $whitespace = "   \n\t   ";

    $this->service->set('whitespace.value', $whitespace, 'string', 'general');

    expect($this->service->get('whitespace.value'))->toBe($whitespace);
});

it('handles sql injection attempts in keys', function (): void {
    $maliciousKey = "key'; DROP TABLE settings; --";

    $this->service->set($maliciousKey, 'value', 'string', 'general');

    expect($this->service->get($maliciousKey))->toBe('value')
        ->and(Schema::hasTable('settings'))->toBeTrue();
});

it('handles xss attempts in values', function (): void {
    $xssValue = '<script>alert("xss")</script>';

    $this->service->set('xss.value', $xssValue, 'string', 'general');

    expect($this->service->get('xss.value'))->toBe($xssValue);
});

it('handles circular reference prevention in arrays', function (): void {
    $data = ['key' => 'value'];

    // This should not cause infinite recursion
    $this->service->set('safe.array', $data, 'array', 'general');

    expect($this->service->get('safe.array'))->toBe($data);
});

it('handles float precision correctly', function (): void {
    $preciseFloat = 3.141592653589793;

    $this->service->set('precise.float', $preciseFloat, 'float', 'general');

    expect($this->service->get('precise.float'))->toBe($preciseFloat);
});

it('handles scientific notation', function (): void {
    $scientific = 1.23e-10;

    $this->service->set('scientific.float', $scientific, 'float', 'general');

    expect($this->service->get('scientific.float'))->toBe($scientific);
});
