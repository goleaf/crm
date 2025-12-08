<?php

declare(strict_types=1);

namespace Tests\Feature\Metadata;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->company = Company::factory()->create();
});

it('can set and get metadata using trait', function (): void {
    $this->company->setMeta('test_key', 'test_value');
    $this->company->save();

    expect($this->company->getMeta('test_key'))->toBe('test_value');
});

it('can set multiple metadata values', function (): void {
    $this->company->setMeta([
        'key1' => 'value1',
        'key2' => 'value2',
    ]);
    $this->company->save();

    expect($this->company->getMeta('key1'))->toBe('value1')
        ->and($this->company->getMeta('key2'))->toBe('value2');
});

it('can unset metadata', function (): void {
    $this->company->setMeta('test_key', 'test_value');
    $this->company->save();

    $this->company->unsetMeta('test_key');
    $this->company->save();

    expect($this->company->hasMeta('test_key'))->toBeFalse();
});

it('can check if metadata exists', function (): void {
    expect($this->company->hasMeta('test_key'))->toBeFalse();

    $this->company->setMeta('test_key', 'test_value');
    $this->company->save();

    expect($this->company->hasMeta('test_key'))->toBeTrue();
});

it('can get all metadata', function (): void {
    $this->company->setMeta([
        'key1' => 'value1',
        'key2' => 'value2',
    ]);
    $this->company->save();

    $all = $this->company->getMeta();

    expect($all)->toHaveCount(2)
        ->and($all->get('key1'))->toBe('value1')
        ->and($all->get('key2'))->toBe('value2');
});

it('can query models by metadata', function (): void {
    $company1 = Company::factory()->create();
    $company1->setMeta('is_partner', true);
    $company1->save();

    $company2 = Company::factory()->create();
    $company2->setMeta('is_partner', false);
    $company2->save();

    $company3 = Company::factory()->create();

    $partners = Company::whereMeta('is_partner', true)->get();

    expect($partners)->toHaveCount(1)
        ->and($partners->first()->id)->toBe($company1->id);
});

it('handles default values correctly', function (): void {
    $company = new class extends Company
    {
        public array $defaultMetaValues = [
            'theme' => 'light',
            'notifications_enabled' => true,
        ];
    };
    $company->save();

    expect($company->getMeta('theme'))->toBe('light')
        ->and($company->getMeta('notifications_enabled'))->toBeTrue();
});

it('removes metadata when set to default value', function (): void {
    $company = new class extends Company
    {
        public array $defaultMetaValues = [
            'is_verified' => false,
        ];
    };
    $company->save();

    $company->setMeta('is_verified', true);
    $company->save();
    expect($company->hasMeta('is_verified'))->toBeTrue();

    $company->setMeta('is_verified', false);
    $company->save();
    expect($company->hasMeta('is_verified'))->toBeFalse();
    expect($company->getMeta('is_verified'))->toBeFalse();
});

it('persists metadata across model reloads', function (): void {
    $this->company->setMeta('test_key', 'test_value');
    $this->company->save();

    $reloaded = Company::find($this->company->id);

    expect($reloaded->getMeta('test_key'))->toBe('test_value');
});

it('deletes metadata when model is deleted', function (): void {
    $this->company->setMeta('test_key', 'test_value');
    $this->company->save();

    $metaCount = $this->company->metas()->count();
    expect($metaCount)->toBe(1);

    $this->company->delete();

    expect(\App\Models\ModelMeta::where('metable_id', $this->company->id)->count())->toBe(0);
});

it('handles comma-separated keys in getMeta', function (): void {
    $this->company->setMeta([
        'key1' => 'value1',
        'key2' => 'value2',
        'key3' => 'value3',
    ]);
    $this->company->save();

    $result = $this->company->getMeta('key1,key2');

    expect($result)->toHaveCount(2)
        ->and($result->get('key1'))->toBe('value1')
        ->and($result->get('key2'))->toBe('value2');
});

it('handles pipe-separated keys in getMeta', function (): void {
    $this->company->setMeta([
        'key1' => 'value1',
        'key2' => 'value2',
    ]);
    $this->company->save();

    $result = $this->company->getMeta('key1|key2');

    expect($result)->toHaveCount(2)
        ->and($result->get('key1'))->toBe('value1')
        ->and($result->get('key2'))->toBe('value2');
});

it('returns raw ModelMeta objects when requested', function (): void {
    $this->company->setMeta('test_key', 'test_value');
    $this->company->save();

    $raw = $this->company->getMeta('test_key', raw: true);

    expect($raw)->toBeInstanceOf(\App\Models\ModelMeta::class)
        ->and($raw->key)->toBe('test_key')
        ->and($raw->value)->toBe('test_value');
});

it('handles array values correctly', function (): void {
    $array = ['item1', 'item2', 'item3'];

    $this->company->setMeta('array_key', $array);
    $this->company->save();

    expect($this->company->getMeta('array_key'))->toBe($array);
});

it('handles nested array values correctly', function (): void {
    $nested = [
        'level1' => [
            'level2' => [
                'value' => 'deep',
            ],
        ],
    ];

    $this->company->setMeta('nested_key', $nested);
    $this->company->save();

    expect($this->company->getMeta('nested_key'))->toBe($nested);
});

it('handles boolean values correctly', function (): void {
    $this->company->setMeta([
        'bool_true' => true,
        'bool_false' => false,
    ]);
    $this->company->save();

    expect($this->company->getMeta('bool_true'))->toBeTrue()
        ->and($this->company->getMeta('bool_false'))->toBeFalse();
});

it('handles numeric values correctly', function (): void {
    $this->company->setMeta([
        'int_value' => 42,
        'float_value' => 3.14159,
    ]);
    $this->company->save();

    expect($this->company->getMeta('int_value'))->toBe(42)
        ->and($this->company->getMeta('float_value'))->toBe(3.14159);
});

it('handles null values correctly', function (): void {
    $this->company->setMeta('null_key', null);
    $this->company->save();

    expect($this->company->getMeta('null_key'))->toBeNull();
});

it('converts keys to lowercase', function (): void {
    $this->company->setMeta('TestKey', 'test_value');
    $this->company->save();

    expect($this->company->getMeta('testkey'))->toBe('test_value')
        ->and($this->company->getMeta('TestKey'))->toBe('test_value')
        ->and($this->company->getMeta('TESTKEY'))->toBe('test_value');
});
