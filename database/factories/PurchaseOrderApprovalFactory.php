<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProcessApprovalStatus;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderApproval>
 */
final class PurchaseOrderApprovalFactory extends Factory
{
    protected $model = PurchaseOrderApproval::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'team_id' => null,
            'requested_by_id' => User::factory(),
            'approver_id' => User::factory(),
            'status' => ProcessApprovalStatus::PENDING,
            'due_at' => \Illuminate\Support\Facades\Date::now()->addDays(2),
        ];
    }
}
