<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Layout Definitions table
        Schema::create('layout_definitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('module_name');
            $table->string('view_type'); // list, detail, edit, search, subpanel
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('components'); // Layout components configuration
            $table->json('ordering')->nullable(); // Field ordering
            $table->json('visibility_rules')->nullable(); // Visibility conditions
            $table->json('group_overrides')->nullable(); // Per-group layout overrides
            $table->boolean('active')->default(true);
            $table->boolean('system_defined')->default(false);
            $table->timestamps();

            // Indexes
            $table->index(['team_id', 'module_name', 'view_type']);
            $table->index(['team_id', 'active']);
            $table->unique(['team_id', 'module_name', 'view_type', 'name']);
        });

        // Field Dependencies table
        Schema::create('field_dependencies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('module_name');
            $table->string('source_field_code'); // Field that triggers the dependency
            $table->string('target_field_code'); // Field that is affected
            $table->string('dependency_type'); // visibility, required, options, validation
            $table->string('condition_operator'); // equals, not_equals, contains, etc.
            $table->json('condition_value'); // Value(s) to compare against
            $table->string('action_type'); // show, hide, require, optional, etc.
            $table->json('action_config')->nullable(); // Additional action configuration
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['team_id', 'module_name']);
            $table->index(['team_id', 'source_field_code']);
            $table->index(['team_id', 'target_field_code']);
            $table->index(['team_id', 'active']);
        });

        // Label Customizations table
        Schema::create('label_customizations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('module_name');
            $table->string('element_type'); // field, module, action, navigation, tab, section
            $table->string('element_key'); // The key/identifier of the element
            $table->string('original_label'); // Original label for reference
            $table->string('custom_label'); // Custom label
            $table->text('description')->nullable();
            $table->string('locale', 5)->default('en'); // Language locale
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['team_id', 'module_name']);
            $table->index(['team_id', 'element_type']);
            $table->index(['team_id', 'locale']);
            $table->index(['team_id', 'active']);
            $table->unique(['team_id', 'module_name', 'element_type', 'element_key', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_customizations');
        Schema::dropIfExists('field_dependencies');
        Schema::dropIfExists('layout_definitions');
    }
};
