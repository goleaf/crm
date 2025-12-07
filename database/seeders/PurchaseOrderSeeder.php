<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PurchaseOrderReceiptType;
use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderApproval;
use App\Models\PurchaseOrderLineItem;
use App\Models\PurchaseOrderReceipt;
use App\Models\Team;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

final class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Creating purchase orders with line items, approvals, and receipts...');

        $teams = Team::all();
        $users = User::all();
        $vendors = Vendor::all();

        if ($teams->isEmpty() || $users->isEmpty()) {
            $this->command?->warn('No teams or users found; skipping purchase order seed.');

            return;
        }

        PurchaseOrder::factory()
            ->count(150)
            ->make()
            ->each(function (PurchaseOrder $purchaseOrder) use ($teams, $users, $vendors): void {
                $team = $teams->random();
                $creator = $users->random();
                $vendor = $vendors->where('team_id', $team->id)->random() ?? $vendors->random();
                $orderedAt = Carbon::now()->subDays(random_int(0, 90));

                $purchaseOrder->forceFill([
                    'team_id' => $team->id,
                    'creator_id' => $creator->id,
                    'vendor_id' => $vendor?->id,
                    'ordered_at' => $orderedAt,
                    'expected_delivery_date' => (clone $orderedAt)->addDays(random_int(5, 30)),
                    'payment_terms' => 'Net 30',
                    'shipping_terms' => 'Standard',
                    'status' => PurchaseOrderStatus::ISSUED,
                ])->save();

                $lineItems = PurchaseOrderLineItem::factory()
                    ->count(random_int(2, 6))
                    ->make([
                        'purchase_order_id' => $purchaseOrder->id,
                        'team_id' => $purchaseOrder->team_id,
                        'unit_cost' => fake()->randomFloat(2, 50, 600),
                        'quantity' => fake()->numberBetween(1, 20),
                        'tax_rate' => fake()->randomElement([0, 5, 8, 10]),
                    ]);

                $purchaseOrder->lineItems()->saveMany($lineItems);

                if (fake()->boolean(70)) {
                    PurchaseOrderApproval::factory()
                        ->count(random_int(1, 2))
                        ->create([
                            'purchase_order_id' => $purchaseOrder->id,
                            'team_id' => $purchaseOrder->team_id,
                            'requested_by_id' => $creator->id,
                            'approver_id' => $users->random()->id,
                        ]);
                }

                if (fake()->boolean(60)) {
                    $linesToReceive = $purchaseOrder->lineItems()->inRandomOrder()->limit(random_int(1, 3))->get();

                    $linesToReceive->each(function (PurchaseOrderLineItem $lineItem) use ($purchaseOrder, $users): void {
                        $receivedQty = max(1, (int) min($lineItem->quantity, fake()->numberBetween(1, (int) $lineItem->quantity)));

                        PurchaseOrderReceipt::factory()->create([
                            'purchase_order_id' => $purchaseOrder->id,
                            'purchase_order_line_item_id' => $lineItem->id,
                            'team_id' => $purchaseOrder->team_id,
                            'received_by_id' => $users->random()->id,
                            'receipt_type' => PurchaseOrderReceiptType::RECEIPT,
                            'quantity' => $receivedQty,
                            'unit_cost' => $lineItem->unit_cost,
                            'received_at' => Carbon::now()->subDays(random_int(0, 10)),
                        ]);
                    });
                }

                $purchaseOrder->syncApprovalState();
                $purchaseOrder->syncFinancials();
            });

        $this->command?->info('✓ Created purchase orders with linked vendors, approvals, and receipts');
    }
}
