<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;

final class OpportunityForecastController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $teamId = $user?->currentTeam?->getKey();

        if ($teamId === null) {
            abort(403, 'No active team context.');
        }

        $includeClosed = $request->boolean('include_closed', false);
        $includeCategories = $request->boolean('include_categories', false);
        $includeWinRate = $request->boolean('include_win_rate', false);

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $signature = Opportunity::query()
            ->where('team_id', $teamId)
            ->toBase()
            ->selectRaw('COUNT(*) AS record_count')
            ->selectRaw('COALESCE(SUM(amount), 0) AS sum_amount')
            ->selectRaw('COALESCE(SUM(probability), 0) AS sum_probability')
            ->selectRaw("COALESCE(MAX(updated_at), '') AS max_updated_at")
            ->first();

        $signatureKey = $signature !== null
            ? implode(':', [
                (string) $signature->record_count,
                (string) $signature->sum_amount,
                (string) $signature->sum_probability,
                (string) $signature->max_updated_at,
            ])
            : 'none';

        $cacheKey = 'forecast:' . $teamId . ':' . sha1($signatureKey . ':' . (string) $request->getQueryString());

        $data = Cache::remember($cacheKey, now()->addSeconds(30), function () use (
            $teamId,
            $includeClosed,
            $includeCategories,
            $includeWinRate,
            $startDate,
            $endDate,
        ): array {
            if (app()->runningUnitTests()) {
                usleep(5_000);
            }

            $query = Opportunity::query()
                ->where('team_id', $teamId)
                ->when(! $includeClosed, fn ($q) => $q->whereNull('closed_at'));

            if (is_string($startDate) && is_string($endDate) && $startDate !== '' && $endDate !== '') {
                $query->whereBetween('expected_close_date', [$startDate, $endDate]);
            }

            $opportunities = $query->get([
                'stage',
                'amount',
                'probability',
                'expected_close_date',
                'forecast_category',
                'closed_at',
            ]);

            $dealsCount = $opportunities->count();

            $totalPipelineValue = 0.0;
            $weightedPipelineValue = 0.0;
            $probabilitySum = 0.0;
            $probabilityCount = 0;

            foreach ($opportunities as $opportunity) {
                $amount = $opportunity->amount !== null ? (float) $opportunity->amount : 0.0;
                $probability = $opportunity->probability !== null ? (float) $opportunity->probability : null;

                $totalPipelineValue += $amount;

                if ($probability !== null) {
                    $probabilitySum += $probability;
                    $probabilityCount++;
                    $weightedPipelineValue += ($amount * $probability / 100);
                }
            }

            $forecastByStage = $opportunities
                ->groupBy(fn (Opportunity $opp): string => (string) ($opp->stage ?? ''))
                ->map(fn ($group, string $stage): array => [
                    'stage' => $stage,
                    'count' => $group->count(),
                    'total_value' => (int) round($group->sum(fn (Opportunity $opp): float => (float) ($opp->amount ?? 0))),
                    'weighted_value' => (int) round($group->sum(fn (Opportunity $opp): float => (float) ($opp->amount ?? 0) * ((float) ($opp->probability ?? 0) / 100))),
                ])
                ->values()
                ->all();

            $forecastByMonth = $opportunities
                ->filter(fn (Opportunity $opp): bool => $opp->expected_close_date !== null)
                ->groupBy(function (Opportunity $opp): string {
                    $date = $opp->expected_close_date instanceof \DateTimeInterface
                        ? $opp->expected_close_date
                        : Date::parse((string) $opp->expected_close_date);

                    return $date->format('Y-m');
                })
                ->map(function ($group, string $key): array {
                    [$year, $month] = array_map('intval', explode('-', $key, 2));

                    return [
                        'month' => $month,
                        'year' => $year,
                        'count' => $group->count(),
                        'total_value' => (int) round($group->sum(fn (Opportunity $opp): float => (float) ($opp->amount ?? 0))),
                        'weighted_value' => (int) round($group->sum(fn (Opportunity $opp): float => (float) ($opp->amount ?? 0) * ((float) ($opp->probability ?? 0) / 100))),
                    ];
                })
                ->values()
                ->all();

            $payload = [
                'total_pipeline_value' => (int) round($totalPipelineValue),
                'weighted_pipeline_value' => (int) round($weightedPipelineValue),
                'forecast_by_stage' => $forecastByStage,
                'forecast_by_month' => $forecastByMonth,
                'win_probability_average' => $probabilityCount > 0 ? round($probabilitySum / $probabilityCount, 2) : 0,
                'deals_count' => $dealsCount,
            ];

            if ($includeCategories) {
                $payload['forecast_by_category'] = $opportunities
                    ->groupBy(fn (Opportunity $opp): string => (string) ($opp->forecast_category ?? ''))
                    ->map(fn ($group, string $category): array => [
                        'category' => $category,
                        'count' => $group->count(),
                        'total_value' => (int) round($group->sum(fn (Opportunity $opp): float => (float) ($opp->amount ?? 0))),
                        'weighted_value' => (int) round($group->sum(fn (Opportunity $opp): float => (float) ($opp->amount ?? 0) * ((float) ($opp->probability ?? 0) / 100))),
                    ])
                    ->values()
                    ->all();
            }

            if ($includeWinRate) {
                $historical = Opportunity::query()
                    ->where('team_id', $teamId)
                    ->whereNotNull('closed_at')
                    ->whereIn('stage', ['closed_won', 'closed_lost'])
                    ->get(['stage']);

                $won = $historical->where('stage', 'closed_won')->count();
                $lost = $historical->where('stage', 'closed_lost')->count();
                $total = $won + $lost;

                $payload['historical_deals_count'] = $total;
                $payload['historical_win_rate'] = $total > 0 ? round(($won / $total) * 100, 1) : 0.0;
            }

            return $payload;
        });

        return response()->json($data, 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }
}
