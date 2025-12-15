<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Relaticle\Flowforge\Services\Rank;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->flowforgePositionColumn('order_column')->after('status');
        });

        $this->backfillPositions();
    }

    /**
     * Assign Flowforge rank values grouped by team and status to avoid card collisions.
     */
    private function backfillPositions(): void
    {
        $groups = DB::table('leads')
            ->select('id', 'team_id', 'status')
            ->orderBy('team_id')
            ->orderBy('status')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (object $lead): string => "{$lead->team_id}_{$lead->status}");

        foreach ($groups as $leads) {
            $rank = Rank::forEmptySequence();

            foreach ($leads as $lead) {
                DB::table('leads')
                    ->where('id', $lead->id)
                    ->update(['order_column' => $rank->get()]);

                $rank = Rank::after($rank);
            }
        }
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn('order_column');
        });
    }
};
