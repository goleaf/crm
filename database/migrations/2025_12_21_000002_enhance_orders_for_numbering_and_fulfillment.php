<?php

declare(strict_types=1);

use App\Enums\CreationSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedInteger('sequence')->nullable()->after('id');
            $table->string('number')->nullable()->unique()->after('sequence');
            $table->string('fulfillment_status', 50)->default('pending')->after('status');
            $table->date('ordered_at')->nullable()->after('quote_id');
            $table->date('fulfillment_due_at')->nullable()->after('ordered_at');
            $table->timestamp('fulfilled_at')->nullable()->after('fulfillment_due_at');
            $table->string('payment_terms')->nullable()->after('expected_delivery_date');
            $table->decimal('fx_rate', 12, 6)->default(1)->after('currency_code');
            $table->decimal('discount_total', 12, 2)->default(0)->after('subtotal');
            $table->decimal('balance_due', 12, 2)->default(0)->after('total');
            $table->decimal('paid_total', 12, 2)->default(0)->after('balance_due');
            $table->decimal('invoiced_total', 12, 2)->default(0)->after('paid_total');
            $table->string('invoice_template_key')->nullable()->after('invoiced_total');
            $table->string('quote_reference')->nullable()->after('invoice_template_key');
            $table->text('notes')->nullable()->after('quote_reference');
            $table->text('terms')->nullable()->after('notes');
            $table->string('creation_source', 50)->default(CreationSource::WEB->value)->after('terms');
            $table->unique(['team_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['team_id', 'sequence']);
            $table->dropColumn([
                'sequence',
                'number',
                'fulfillment_status',
                'ordered_at',
                'fulfillment_due_at',
                'fulfilled_at',
                'payment_terms',
                'fx_rate',
                'discount_total',
                'balance_due',
                'paid_total',
                'invoiced_total',
                'invoice_template_key',
                'quote_reference',
                'notes',
                'terms',
                'creation_source',
            ]);
        });
    }
};
