<?php

declare(strict_types=1);

use App\Models\InMemoryModel;
use Waad\Truffle\Enums\DataType;

it('bootstraps records into an in-memory connection and keeps casts intact', function (): void {
    $product = new class extends InMemoryModel
    {
        protected $table = 'truffle_products';

        protected array $records = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 999.99, 'tags' => ['hardware']],
            ['id' => 2, 'name' => 'Notebook', 'price' => 5.00, 'tags' => ['stationery']],
        ];

        protected array $schema = [
            'id' => DataType::Id,
            'name' => DataType::String,
            'price' => DataType::Decimal,
            'tags' => DataType::Json,
        ];

        protected $casts = [
            'tags' => 'array',
        ];

        protected int $insertChunkRecords = 1;
    };

    $results = $product::query()
        ->where('price', '>', 10)
        ->orderByDesc('price')
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Laptop')
        ->and($results->first()->tags)->toBe(['hardware'])
        ->and((float) $product::sum('price'))->toBe(1004.99)
        ->and($product::resolveConnection()->getDriverName())->toBe('sqlite');
});

it('rebuilds the in-memory dataset after clearing the connection', function (): void {
    $country = new class extends InMemoryModel
    {
        public static int $seedSet = 1;

        protected $table = 'truffle_countries';

        protected array $schema = [
            'code' => DataType::String,
            'name' => DataType::String,
        ];

        protected $primaryKey = 'code';

        public $incrementing = false;

        protected $keyType = 'string';

        public function getRecords(): array
        {
            if (self::$seedSet === 1) {
                return [
                    ['code' => 'US', 'name' => 'United States'],
                    ['code' => 'CA', 'name' => 'Canada'],
                ];
            }

            return [
                ['code' => 'GB', 'name' => 'United Kingdom'],
            ];
        }
    };

    expect($country::count())->toBe(2);

    $country::$seedSet = 2;
    $country::rebuildInMemoryData();

    expect($country::count())->toBe(1)
        ->and($country::first()->code)->toBe('GB');
});
