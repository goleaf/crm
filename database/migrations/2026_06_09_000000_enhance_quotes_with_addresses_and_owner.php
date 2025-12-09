<?php

declare(strict_types=1);

use App\Enums\QuoteDiscountType;
use App\Models\Quote;
use App\Models\QuoteLineItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table): void {
            $table->foreignId('owner_id')->nullable()->after('creator_id')->constrained('users')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->after('contact_id')->constrained('leads')->nullOnDelete();
            $table->text('description')->nullable()->after('title');
            $table->decimal('discount_total', 12, 2)->default(0)->after('subtotal');
            $table->json('billing_address')->nullable()->after('line_items');
            $table->json('shipping_address')->nullable()->after('billing_address');
        });

        Quote::query()
            ->lazyById()
            ->each(function (Quote $quote): void {
                if ($quote->owner_id === null) {
                    $quote->owner_id = $quote->creator_id;
                }

                if (is_array($quote->line_items) && $quote->lineItems()->count() === 0) {
                    DB::transaction(function () use ($quote): void {
                        foreach ($quote->line_items as $index => $item) {
                            QuoteLineItem::query()->create([
                                'quote_id' => $quote->getKey(),
                                'team_id' => $quote->team_id,
                                'product_id' => $item['product_id'] ?? null,
                                'sku' => $item['sku'] ?? null,
                                'name' => $item['name'] ?? 'Line item ' . ($index + 1),
                                'description' => $item['description'] ?? null,
                                'quantity' => (float) ($item['quantity'] ?? 0),
                                'unit_price' => (float) ($item['unit_price'] ?? 0),
                                'discount_type' => QuoteDiscountType::tryFrom((string) ($item['discount_type'] ?? '')) ?? QuoteDiscountType::PERCENT,
                                'discount_value' => (float) ($item['discount_value'] ?? 0),
                                'tax_rate' => (float) ($item['tax_rate'] ?? 0),
                                'sort_order' => $index + 1,
                                'is_custom' => empty($item['product_id']),
                            ]);
                        }
                    });
                }

                $quote->saveQuietly();
                $quote->syncFinancials();
            });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('owner_id');
            $table->dropConstrainedForeignId('lead_id');
            $table->dropColumn(['description', 'discount_total', 'billing_address', 'shipping_address']);
        });
    }
};
