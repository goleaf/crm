<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->foreignId('parent_company_id')
                ->nullable()
                ->after('account_owner_id')
                ->constrained('companies')
                ->nullOnDelete();

            $table->string('account_type')
                ->nullable()
                ->after('name');

            $table->string('ownership')
                ->nullable()
                ->after('account_type');

            $table->string('phone')
                ->nullable()
                ->after('ownership');

            $table->string('primary_email')
                ->nullable()
                ->after('phone');

            $table->char('currency_code', 3)
                ->default(config('company.default_currency', 'USD'))
                ->after('employee_count');

            $table->json('social_links')
                ->nullable()
                ->after('website');

            $table->string('billing_street')->nullable()->after('description');
            $table->string('billing_city')->nullable()->after('billing_street');
            $table->string('billing_state')->nullable()->after('billing_city');
            $table->string('billing_postal_code')->nullable()->after('billing_state');
            $table->string('billing_country')->nullable()->after('billing_postal_code');

            $table->string('shipping_street')->nullable()->after('billing_country');
            $table->string('shipping_city')->nullable()->after('shipping_street');
            $table->string('shipping_state')->nullable()->after('shipping_city');
            $table->string('shipping_postal_code')->nullable()->after('shipping_state');
            $table->string('shipping_country')->nullable()->after('shipping_postal_code');

            // Add index for hierarchy queries
            $table->index('parent_company_id');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropIndex(['parent_company_id']);
            $table->dropConstrainedForeignId('parent_company_id');

            $table->dropColumn([
                'account_type',
                'ownership',
                'phone',
                'primary_email',
                'currency_code',
                'social_links',
                'billing_street',
                'billing_city',
                'billing_state',
                'billing_postal_code',
                'billing_country',
                'shipping_street',
                'shipping_city',
                'shipping_state',
                'shipping_postal_code',
                'shipping_country',
            ]);
        });
    }
};
