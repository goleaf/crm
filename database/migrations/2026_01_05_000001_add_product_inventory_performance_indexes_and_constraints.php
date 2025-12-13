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
        // Add performance indexes for products table (only if table exists)
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table): void {
                // Performance indexes as specified in design document
                $table->index(['team_id', 'is_active'], 'products_team_active_index');
                $table->index(['team_id', 'status', 'lifecycle_stage'], 'products_team_status_lifecycle_index');
                $table->index(['track_inventory'], 'products_track_inventory_index');
                $table->index(['created_at'], 'products_created_at_index');
                $table->index(['updated_at'], 'products_updated_at_index');
            });
        }

        // Add performance indexes for product_categories table
        Schema::table('product_categories', function (Blueprint $table): void {
            $table->index(['parent_id'], 'product_categories_parent_id_index');
            $table->index(['team_id', 'parent_id'], 'product_categories_team_parent_index');
        });

        // Add performance indexes for product_attributes table
        Schema::table('product_attributes', function (Blueprint $table): void {
            $table->index(['is_configurable'], 'product_attributes_configurable_index');
            $table->index(['is_filterable'], 'product_attributes_filterable_index');
            $table->index(['data_type'], 'product_attributes_data_type_index');
        });

        // Add performance indexes for product_variations table
        Schema::table('product_variations', function (Blueprint $table): void {
            $table->index(['product_id', 'is_default'], 'product_variations_product_default_index');
            $table->index(['track_inventory'], 'product_variations_track_inventory_index');
        });

        // Add performance indexes for pivot tables
        Schema::table('category_product', function (Blueprint $table): void {
            $table->index(['product_category_id'], 'category_product_category_index');
        });

        Schema::table('product_configurable_attributes', function (Blueprint $table): void {
            $table->index(['product_attribute_id'], 'product_configurable_attributes_attribute_index');
        });

        // Add performance indexes for product_attribute_assignments table
        Schema::table('product_attribute_assignments', function (Blueprint $table): void {
            $table->index(['product_attribute_id'], 'product_attribute_assignments_attribute_index');
            $table->index(['product_attribute_value_id'], 'product_attribute_assignments_value_index');
        });

        // Add performance indexes for product_attribute_values table
        Schema::table('product_attribute_values', function (Blueprint $table): void {
            $table->index(['sort_order'], 'product_attribute_values_sort_order_index');
        });

        // Add missing sort_order column to product_categories if not exists
        if (! Schema::hasColumn('product_categories', 'sort_order')) {
            Schema::table('product_categories', function (Blueprint $table): void {
                $table->integer('sort_order')->default(0)->after('description');
                $table->index(['sort_order'], 'product_categories_sort_order_index');
            });
        } else {
            Schema::table('product_categories', function (Blueprint $table): void {
                $table->index(['sort_order'], 'product_categories_sort_order_index');
            });
        }

        // Add performance indexes for enhanced product tables
        if (Schema::hasTable('product_relationships')) {
            Schema::table('product_relationships', function (Blueprint $table): void {
                $table->index(['related_product_id'], 'product_relationships_related_product_index');
                $table->index(['relationship_type', 'priority'], 'product_relationships_type_priority_index');
            });
        }

        if (Schema::hasTable('product_price_tiers')) {
            Schema::table('product_price_tiers', function (Blueprint $table): void {
                $table->index(['team_id'], 'product_price_tiers_team_index');
                $table->index(['starts_at', 'ends_at'], 'product_price_tiers_date_range_index');
            });
        }

        if (Schema::hasTable('product_discount_rules')) {
            Schema::table('product_discount_rules', function (Blueprint $table): void {
                $table->index(['team_id'], 'product_discount_rules_team_index');
                $table->index(['scope'], 'product_discount_rules_scope_index');
                $table->index(['discount_type'], 'product_discount_rules_type_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from products table
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('products_team_active_index');
            $table->dropIndex('products_team_status_lifecycle_index');
            $table->dropIndex('products_track_inventory_index');
            $table->dropIndex('products_created_at_index');
            $table->dropIndex('products_updated_at_index');
        });

        // Drop indexes from product_categories table
        Schema::table('product_categories', function (Blueprint $table): void {
            $table->dropIndex('product_categories_parent_id_index');
            $table->dropIndex('product_categories_team_parent_index');
            if (Schema::hasColumn('product_categories', 'sort_order')) {
                $table->dropIndex('product_categories_sort_order_index');
            }
        });

        // Drop indexes from product_attributes table
        Schema::table('product_attributes', function (Blueprint $table): void {
            $table->dropIndex('product_attributes_configurable_index');
            $table->dropIndex('product_attributes_filterable_index');
            $table->dropIndex('product_attributes_data_type_index');
        });

        // Drop indexes from product_variations table
        Schema::table('product_variations', function (Blueprint $table): void {
            $table->dropIndex('product_variations_product_default_index');
            $table->dropIndex('product_variations_track_inventory_index');
        });

        // Drop indexes from pivot tables
        Schema::table('category_product', function (Blueprint $table): void {
            $table->dropIndex('category_product_category_index');
        });

        Schema::table('product_configurable_attributes', function (Blueprint $table): void {
            $table->dropIndex('product_configurable_attributes_attribute_index');
        });

        // Drop indexes from product_attribute_assignments table
        Schema::table('product_attribute_assignments', function (Blueprint $table): void {
            $table->dropIndex('product_attribute_assignments_attribute_index');
            $table->dropIndex('product_attribute_assignments_value_index');
        });

        // Drop indexes from product_attribute_values table
        Schema::table('product_attribute_values', function (Blueprint $table): void {
            $table->dropIndex('product_attribute_values_sort_order_index');
        });

        // Drop indexes from enhanced product tables
        if (Schema::hasTable('product_relationships')) {
            Schema::table('product_relationships', function (Blueprint $table): void {
                $table->dropIndex('product_relationships_related_product_index');
                $table->dropIndex('product_relationships_type_priority_index');
            });
        }

        if (Schema::hasTable('product_price_tiers')) {
            Schema::table('product_price_tiers', function (Blueprint $table): void {
                $table->dropIndex('product_price_tiers_team_index');
                $table->dropIndex('product_price_tiers_date_range_index');
            });
        }

        if (Schema::hasTable('product_discount_rules')) {
            Schema::table('product_discount_rules', function (Blueprint $table): void {
                $table->dropIndex('product_discount_rules_team_index');
                $table->dropIndex('product_discount_rules_scope_index');
                $table->dropIndex('product_discount_rules_type_index');
            });
        }
    }
};
