<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Order;
use App\Models\OrderLineItem;
use App\Models\People;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

final class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating orders (300) with line items...');

        $teams = Team::all();
        $users = User::all();
        $companies = Company::all();
        $people = People::all();
        $products = Product::all();

        if ($teams->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No teams or users found.');

            return;
        }

        $orders = [];
        for ($i = 0; $i < 300; $i++) {
            $orders[] = [
                'team_id' => $teams->random()->id,
                'company_id' => $companies->isNotEmpty() ? $companies->random()->id : null,
                'contact_id' => $people->isNotEmpty() ? $people->random()->id : null,
                'creator_id' => $users->random()->id,
                'order_number' => 'ORD-'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'status' => fake()->randomElement(['pending', 'processing', 'shipped', 'delivered', 'cancelled']),
                'order_date' => now()->subDays(random_int(0, 365)),
                'subtotal' => 0,
                'tax' => 0,
                'total' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($orders, 500) as $chunk) {
            Order::insert($chunk);
        }

        $orderIds = Order::pluck('id');

        // Create line items
        $lineItems = [];
        foreach ($orderIds as $orderId) {
            for ($i = 0; $i < random_int(1, 10); $i++) {
                $quantity = random_int(1, 50);
                $unitPrice = fake()->randomFloat(2, 10, 5000);

                $lineItems[] = [
                    'order_id' => $orderId,
                    'team_id' => $teams->random()->id,
                    'product_id' => $products->isNotEmpty() ? $products->random()->id : null,
                    'description' => fake()->sentence(),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $quantity * $unitPrice,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($lineItems, 1000) as $chunk) {
            OrderLineItem::insert($chunk);
        }

        $this->command->info('✓ Created 300 orders with line items');
    }
}
