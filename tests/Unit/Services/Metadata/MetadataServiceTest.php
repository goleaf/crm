<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Metadata;

use App\Models\Company;
use App\Services\Metadata\MetadataService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = resolve(MetadataService::class);
    $this->company = Company::factory()->create();
});

it('can set and get metadata', function (): void {
    $this->service->set($this->company, 'test_key', 'test_value');

    expect($this->service->get($this->company, 'test_key'))->toBe('test_value');
});

it('can set multiple metadata values', function (): void {
    $this->service->set($this->company, [
        'key1' => 'value1',
        'key2' => 'value2',
    ]);

    expect($this->service->get($this->company, 'key1'))->toBe('value1')
        ->and($this->service->get($this->company, 'key2'))->toBe('value2');
});

it('can remove metadata', function (): void {
    $this->service->set($this->company, 'test_key', 'test_value');
    expect($this->service->has($this->company, 'test_key'))->toBeTrue();

    $this->service->remove($this->company, 'test_key');
    expect($this->service->has($this->company, 'test_key'))->toBeFalse();
});

it('can check if metadata exists', function (): void {
    expect($this->service->has($this->company, 'test_key'))->toBeFalse();

    $this->service->set($this->company, 'test_key', 'test_value');
    expect($this->service->has($this->company, 'test_key'))->toBeTrue();
});

it('can get all metadata', function (): void {
    $this->service->bulkSet($this->company, [
        'key1' => 'value1',
        'key2' => 'value2',
        'key3' => 'value3',
    ]);

    $all = $this->service->all($this->company);

    expect($all)->toHaveCount(3)
        ->and($all->get('key1'))->toBe('value1')
        ->and($all->get('key2'))->toBe('value2')
        ->and($all->get('key3'))->toBe('value3');
});

it('can bulk set metadata', function (): void {
    $this->service->bulkSet($this->company, [
        'key1' => 'value1',
        'key2' => 'value2',
    ]);

    expect($this->service->get($this->company, 'key1'))->toBe('value1')
        ->and($this->service->get($this->company, 'key2'))->toBe('value2');
});

it('can bulk remove metadata', function (): void {
    $this->service->bulkSet($this->company, [
        'key1' => 'value1',
        'key2' => 'value2',
        'key3' => 'value3',
    ]);

    $this->service->bulkRemove($this->company, ['key1', 'key2']);

    expect($this->service->has($this->company, 'key1'))->toBeFalse()
        ->and($this->service->has($this->company, 'key2'))->toBeFalse()
        ->and($this->service->has($this->company, 'key3'))->toBeTrue();
});

it('can sync metadata', function (): void {
    $this->service->bulkSet($this->company, [
        'key1' => 'value1',
        'key2' => 'value2',
    ]);

    $this->service->sync($this->company, [
        'key3' => 'value3',
        'key4' => 'value4',
    ]);

    $all = $this->service->all($this->company);

    expect($all)->toHaveCount(2)
        ->and($all->has('key1'))->toBeFalse()
        ->and($all->has('key2'))->toBeFalse()
        ->and($all->get('key3'))->toBe('value3')
        ->and($all->get('key4'))->toBe('value4');
});

it('can merge metadata', function (): void {
    $this->service->bulkSet($this->company, [
        'key1' => 'value1',
        'key2' => 'value2',
    ]);

    $this->service->merge($this->company, [
        'key2' => 'updated_value2',
        'key3' => 'value3',
    ]);

    $all = $this->service->all($this->company);

    expect($all)->toHaveCount(3)
        ->and($all->get('key1'))->toBe('value1')
        ->and($all->get('key2'))->toBe('updated_value2')
        ->and($all->get('key3'))->toBe('value3');
});

it('can get metadata with default value', function (): void {
    $value = $this->service->getWithDefault($this->company, 'nonexistent', 'default_value');

    expect($value)->toBe('default_value');
});

it('can increment numeric metadata', function (): void {
    $this->service->set($this->company, 'counter', 5);

    $this->service->increment($this->company, 'counter');
    expect($this->service->get($this->company, 'counter'))->toBe(6);

    $this->service->increment($this->company, 'counter', 10);
    expect($this->service->get($this->company, 'counter'))->toBe(16);
});

it('can increment from zero if metadata does not exist', function (): void {
    $this->service->increment($this->company, 'new_counter', 5);

    expect($this->service->get($this->company, 'new_counter'))->toBe(5);
});

it('can decrement numeric metadata', function (): void {
    $this->service->set($this->company, 'counter', 10);

    $this->service->decrement($this->company, 'counter');
    expect($this->service->get($this->company, 'counter'))->toBe(9);

    $this->service->decrement($this->company, 'counter', 5);
    expect($this->service->get($this->company, 'counter'))->toBe(4);
});

it('can toggle boolean metadata', function (): void {
    $this->service->set($this->company, 'is_active', false);

    $this->service->toggle($this->company, 'is_active');
    expect($this->service->get($this->company, 'is_active'))->toBeTrue();

    $this->service->toggle($this->company, 'is_active');
    expect($this->service->get($this->company, 'is_active'))->toBeFalse();
});

it('handles different data types correctly', function (): void {
    $this->service->bulkSet($this->company, [
        'string_value' => 'test',
        'int_value' => 42,
        'float_value' => 3.14,
        'bool_value' => true,
        'array_value' => ['a', 'b', 'c'],
        'null_value' => null,
    ]);

    expect($this->service->get($this->company, 'string_value'))->toBe('test')
        ->and($this->service->get($this->company, 'int_value'))->toBe(42)
        ->and($this->service->get($this->company, 'float_value'))->toBe(3.14)
        ->and($this->service->get($this->company, 'bool_value'))->toBeTrue()
        ->and($this->service->get($this->company, 'array_value'))->toBe(['a', 'b', 'c'])
        ->and($this->service->get($this->company, 'null_value'))->toBeNull();
});

it('throws exception when model does not use HasMetadata trait', function (): void {
    $model = new class extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'users';
    };

    $this->service->set($model, 'key', 'value');
})->throws(\InvalidArgumentException::class);
