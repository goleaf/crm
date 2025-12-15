<?php

declare(strict_types=1);

use App\Enums\CreationSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();

            $table->unsignedInteger('sequence');
            $table->string('number')->unique();
            $table->string('status', 50);

            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('payment_terms')->nullable();
            $table->char('currency_code', 3)->default(config('company.default_currency', 'USD'));
            $table->decimal('fx_rate', 12, 6)->default(1);

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('late_fee_rate', 5, 2)->default(0);
            $table->decimal('late_fee_amount', 15, 2)->default(0);
            $table->timestamp('late_fee_applied_at')->nullable();
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);

            $table->string('template_key')->nullable();
            $table->string('reminder_policy')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('last_reminded_at')->nullable();

            $table->boolean('is_recurring_template')->default(false);
            $table->string('recurring_frequency')->nullable();
            $table->unsignedTinyInteger('recurring_interval')->default(1);
            $table->date('recurring_starts_at')->nullable();
            $table->date('recurring_ends_at')->nullable();
            $table->date('next_issue_at')->nullable();

            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('creation_source', 50)->default(CreationSource::WEB->value);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['team_id', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
