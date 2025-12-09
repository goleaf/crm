<?php

declare(strict_types=1);

use App\Support\Metrics\LaravelMetricsHelper;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::create(2025, 4, 15, 12, 0, 0, 'UTC'));

    Schema::create('metric_examples', function (Blueprint $table): void {
        $table->id();
        $table->integer('amount')->default(0);
        $table->timestamps();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('metric_examples');
    Carbon::setTestNow();
});

it('builds monthly trend counts and fills missing months', function (): void {
    DB::table('metric_examples')->insert([
        ['amount' => 10, 'created_at' => '2025-01-10', 'updated_at' => '2025-01-10'],
        ['amount' => 5, 'created_at' => '2025-03-05', 'updated_at' => '2025-03-05'],
    ]);

    /** @var Builder $query */
    $query = DB::table('metric_examples');

    $trend = LaravelMetricsHelper::monthlyCountTrend($query, months: 4);

    expect($trend['labels'])->toBe(['January', 'February', 'March', 'April']);
    expect($trend['data'])->toBe([1, 0, 1, 0]);
});

it('calculates current month totals with variations', function (): void {
    DB::table('metric_examples')->insert([
        ['amount' => 10, 'created_at' => '2025-04-02', 'updated_at' => '2025-04-02'],
        ['amount' => 10, 'created_at' => '2025-04-03', 'updated_at' => '2025-04-03'],
        ['amount' => 10, 'created_at' => '2025-03-15', 'updated_at' => '2025-03-15'],
    ]);

    /** @var Builder $query */
    $query = DB::table('metric_examples');

    $metrics = LaravelMetricsHelper::currentMonthTotalWithVariation(
        $query,
        previousMonths: 1,
    );

    expect($metrics['count'])->toBe(2);
    expect($metrics['variation'])->toMatchArray([
        'type' => 'increase',
        'value' => 1,
    ]);
});
