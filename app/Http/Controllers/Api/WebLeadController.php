<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\LeadAssignmentStrategy;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreWebLeadRequest;
use App\Models\Lead;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class WebLeadController extends Controller
{
    public function store(StoreWebLeadRequest $request): JsonResponse
    {
        $user = $request->user();
        $team = $user?->currentTeam;

        if ($team === null) {
            abort(403, 'No active team context.');
        }

        $data = $request->validated();

        $lead = DB::transaction(function () use ($data, $team, $request): Lead {
            $lead = Lead::create([
                ...$data,
                'team_id' => $team->getKey(),
                'creation_source' => $request->input('creation_source', \App\Enums\CreationSource::WEB),
                'web_form_payload' => $request->all(),
                'assignment_strategy' => $data['assignment_strategy'] ?? LeadAssignmentStrategy::MANUAL,
            ]);

            if (! empty($data['tags'])) {
                /** @var \Illuminate\Support\Collection<int, Tag> $tags */
                $tags = Tag::query()
                    ->whereIn('id', $data['tags'])
                    ->where('team_id', $team->getKey())
                    ->get();

                $lead->tags()->sync($tags->pluck('id'));
            }

            $duplicate = Lead::query()
                ->where('team_id', $team->getKey())
                ->where('email', $lead->email)
                ->whereKeyNot($lead->getKey())
                ->first();

            if ($duplicate instanceof Lead) {
                $lead->forceFill([
                    'duplicate_of_id' => $duplicate->getKey(),
                    'duplicate_score' => 0.99,
                ])->save();
            }

            return $lead->fresh(['tags']);
        });

        return response()->json([
            'id' => $lead->getKey(),
            'duplicate_of_id' => $lead->duplicate_of_id,
            'duplicate_score' => $lead->duplicate_score,
        ], 201);
    }
}
