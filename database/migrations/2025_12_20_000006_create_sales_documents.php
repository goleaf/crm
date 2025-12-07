<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained('opportunities')->nullOnDelete();
            $table->string('title');
            $table->string('status', 30)->default('draft');
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->date('valid_until')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->json('line_items')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained('opportunities')->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->string('status', 30)->default('draft');
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->date('expected_delivery_date')->nullable();
            $table->json('line_items')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('status', 30)->default('pending');
            $table->string('tracking_number')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('status', 50)->default('active');
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('shipping_terms')->nullable();
            $table->string('ship_method')->nullable();
            $table->char('preferred_currency', 3)->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['team_id', 'name']);
        });

        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();

            $table->unsignedInteger('sequence');
            $table->string('number')->unique();
            $table->string('status', 50)->default('draft');

            $table->date('ordered_at')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('last_received_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->string('payment_terms')->nullable();
            $table->string('shipping_terms')->nullable();
            $table->string('ship_method')->nullable();
            $table->text('ship_to_address')->nullable();
            $table->text('bill_to_address')->nullable();

            $table->char('currency_code', 3)->default('USD');
            $table->decimal('fx_rate', 12, 6)->default(1);

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('freight_total', 15, 2)->default(0);
            $table->decimal('fee_total', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('received_cost', 15, 2)->default(0);
            $table->decimal('outstanding_commitment', 15, 2)->default(0);

            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('creation_source', 50)->default('web');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['team_id', 'sequence']);
        });

        Schema::create('purchase_order_line_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_line_item_id')->nullable()->index();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('received_quantity', 12, 2)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->date('expected_receipt_at')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_order_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_line_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('received_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('receipt_type', 50)->default('receipt');
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->timestamp('received_at')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_order_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 50)->default('pending');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamps();
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->foreignId('order_id')->nullable()->after('opportunity_id')->constrained('orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('order_id');
        });

        Schema::dropIfExists('purchase_order_approvals');
        Schema::dropIfExists('purchase_order_receipts');
        Schema::dropIfExists('purchase_order_line_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('quotes');
    }
};
