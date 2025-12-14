<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enhance the existing groups table to support security group features
        Schema::table('groups', function (Blueprint $table): void {
            // Hierarchical structure
            $table->foreignId('parent_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->integer('level')->default(0);
            $table->string('path')->nullable(); // Materialized path for hierarchy

            // Security features
            $table->boolean('is_security_group')->default(true);
            $table->boolean('inherit_permissions')->default(true);
            $table->boolean('owner_only_access')->default(false);
            $table->boolean('group_only_access')->default(false);

            // Layout and customization
            $table->json('layout_overrides')->nullable();
            $table->json('custom_layouts')->nullable();

            // Automation and assignment
            $table->json('auto_assignment_rules')->nullable();
            $table->json('mass_assignment_settings')->nullable();

            // Broadcast and messaging
            $table->boolean('enable_broadcast')->default(false);
            $table->json('broadcast_settings')->nullable();

            // Primary group designation
            $table->boolean('is_primary_group')->default(false);

            // Login-as functionality
            $table->boolean('allow_login_as')->default(false);
            $table->json('login_as_permissions')->nullable();

            // Record-level security
            $table->json('record_level_permissions')->nullable();
            $table->json('field_level_permissions')->nullable();

            // Status and metadata
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable();

            // Indexes for performance
            $table->index(['parent_id', 'level']);
            $table->index(['team_id', 'is_security_group']);
            $table->index(['team_id', 'is_primary_group']);
            $table->index(['active', 'is_security_group']);
        });

        // Create security group memberships table with enhanced features
        Schema::create('security_group_memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Membership properties
            $table->boolean('is_owner')->default(false);
            $table->boolean('is_admin')->default(false);
            $table->boolean('inherit_from_parent')->default(true);
            $table->boolean('can_manage_members')->default(false);
            $table->boolean('can_assign_records')->default(false);

            // Permissions override
            $table->json('permission_overrides')->nullable();

            // Membership metadata
            $table->timestamp('joined_at')->useCurrent();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['group_id', 'user_id']);
            $table->index(['user_id', 'is_owner']);
            $table->index(['group_id', 'is_admin']);
        });

        // Create security group record access table for record-level security
        Schema::create('security_group_record_access', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->morphs('record'); // Polymorphic relation to any model

            // Access levels
            $table->enum('access_level', ['none', 'read', 'write', 'admin', 'owner'])->default('read');
            $table->json('field_permissions')->nullable(); // Field-level permissions

            // Inheritance and overrides
            $table->boolean('inherit_from_parent')->default(true);
            $table->json('permission_overrides')->nullable();

            // Metadata
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['group_id', 'record_type', 'record_id']);
            $table->index(['group_id', 'access_level']);
        });

        // Create security group audit log
        Schema::create('security_group_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Audit details
            $table->string('action'); // created, updated, deleted, member_added, member_removed, etc.
            $table->string('entity_type')->nullable(); // group, membership, record_access
            $table->unsignedBigInteger('entity_id')->nullable();

            // Change tracking
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();

            // Context
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['group_id', 'action']);
            $table->index(['user_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
        });

        // Create broadcast messages table
        Schema::create('security_group_broadcast_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();

            // Message content
            $table->string('subject');
            $table->text('message');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');

            // Delivery settings
            $table->boolean('include_subgroups')->default(true);
            $table->boolean('require_acknowledgment')->default(false);
            $table->timestamp('scheduled_at')->nullable();

            // Status
            $table->enum('status', ['draft', 'scheduled', 'sent', 'cancelled'])->default('draft');
            $table->timestamp('sent_at')->nullable();

            // Metadata
            $table->json('delivery_stats')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['group_id', 'status']);
            $table->index(['sender_id', 'created_at']);
            $table->index(['scheduled_at', 'status']);
        });

        // Create message acknowledgments table
        Schema::create('security_group_message_acknowledgments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')->constrained('security_group_broadcast_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->timestamp('acknowledged_at')->useCurrent();
            $table->text('response')->nullable();

            $table->timestamps();

            $table->unique(['message_id', 'user_id']);
            $table->index(['user_id', 'acknowledged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_group_message_acknowledgments');
        Schema::dropIfExists('security_group_broadcast_messages');
        Schema::dropIfExists('security_group_audit_logs');
        Schema::dropIfExists('security_group_record_access');
        Schema::dropIfExists('security_group_memberships');

        Schema::table('groups', function (Blueprint $table): void {
            $table->dropForeign(['parent_id']);
            $table->dropColumn([
                'parent_id',
                'level',
                'path',
                'is_security_group',
                'inherit_permissions',
                'owner_only_access',
                'group_only_access',
                'layout_overrides',
                'custom_layouts',
                'auto_assignment_rules',
                'mass_assignment_settings',
                'enable_broadcast',
                'broadcast_settings',
                'is_primary_group',
                'allow_login_as',
                'login_as_permissions',
                'record_level_permissions',
                'field_level_permissions',
                'active',
                'metadata',
            ]);
        });
    }
};
