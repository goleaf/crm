<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('part_number')->nullable()->after('sku');
            $table->string('manufacturer')->nullable()->after('part_number');
            $table->string('product_type', 30)->default('stocked')->after('manufacturer');
            $table->string('status', 30)->default('active')->after('product_type');
            $table->string('lifecycle_stage', 30)->default('released')->after('status');
            $table->decimal('cost_price', 12, 2)->default(0)->after('price');
            $table->timestamp('price_effective_from')->nullable()->after('currency_code');
            $table->timestamp('price_effective_to')->nullable()->after('price_effective_from');
            $table->boolean('is_bundle')->default(false)->after('track_inventory');

            $table->unique(['team_id', 'part_number']);
            $table->index(['status', 'lifecycle_stage']);
        });

        Schema::create('product_price_tiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('min_quantity')->default(1);
            $table->integer('max_quantity')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('currency_code', 3)->default('USD');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('label')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'min_quantity', 'starts_at', 'ends_at']);
        });

        Schema::create('product_discount_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name');
            $table->string('scope', 30)->default('product');
            $table->string('discount_type', 20);
            $table->decimal('discount_value', 12, 2);
            $table->integer('min_quantity')->default(1);
            $table->integer('max_quantity')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'product_category_id', 'company_id']);
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('product_relationships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_product_id')->constrained('products')->cascadeOnDelete();
            $table->string('relationship_type', 30);
            $table->integer('priority')->default(0);
            $table->integer('quantity')->default(1);
            $table->decimal('price_override', 12, 2)->nullable();
            $table->boolean('is_required')->default(false);
            $table->timestamps();

            $table->unique(['product_id', 'related_product_id', 'relationship_type'], 'product_relationship_unique');
            $table->index(['relationship_type', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_relationships');
        Schema::dropIfExists('product_discount_rules');
        Schema::dropIfExists('product_price_tiers');

        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique('products_team_id_part_number_unique');
            $table->dropIndex('products_status_lifecycle_stage_index');

            $table->dropColumn([
                'part_number',
                'manufacturer',
                'product_type',
                'status',
                'lifecycle_stage',
                'cost_price',
                'price_effective_from',
                'price_effective_to',
                'is_bundle',
            ]);
        });
    }
};
