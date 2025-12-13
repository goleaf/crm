<?php

declare(strict_types=1);

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
        Schema::create('inventory_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->morphs('adjustable'); // Can be product or product_variation
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->integer('adjustment_quantity');
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->string('reference_type')->nullable(); // e.g., 'sale', 'purchase', 'manual', 'return'
            $table->string('reference_id')->nullable(); // ID of related record (order, return, etc.)
            $table->timestamps();

            // Performance indexes
            $table->index(['adjustable_type', 'adjustable_id'], 'inventory_adjustments_adjustable_index');
            $table->index(['team_id', 'created_at'], 'inventory_adjustments_team_date_index');
            $table->index(['user_id'], 'inventory_adjustments_user_index');
            $table->index(['reason'], 'inventory_adjustments_reason_index');
            $table->index(['reference_type', 'reference_id'], 'inventory_adjustments_reference_index');
        });

        // Add reserved_quantity column to products table for inventory reservation
        Schema::table('products', function (Blueprint $table): void {
            $table->integer('reserved_quantity')->default(0)->after('inventory_quantity');
            $table->index(['reserved_quantity'], 'products_reserved_quantity_index');
        });

        // Add index to existing reserved_quantity column in product_variations table
        Schema::table('product_variations', function (Blueprint $table): void {
            $table->index(['reserved_quantity'], 'product_variations_reserved_quantity_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop reserved_quantity column from products table
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('products_reserved_quantity_index');
            $table->dropColumn('reserved_quantity');
        });

        // Drop index from product_variations table (column exists in base migration)
        Schema::table('product_variations', function (Blueprint $table): void {
            $table->dropIndex('product_variations_reserved_quantity_index');
        });

        Schema::dropIfExists('inventory_adjustments');
    }
};
