<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enhance the existing roles table
        Schema::table('roles', function (Blueprint $table): void {
            $table->string('display_name')->nullable()->after('name');
            $table->text('description')->nullable()->after('display_name');
            $table->boolean('is_template')->default(false)->after('description');
            $table->boolean('is_admin_role')->default(false)->after('is_template');
            $table->boolean('is_studio_role')->default(false)->after('is_admin_role');
            $table->unsignedBigInteger('parent_role_id')->nullable()->after('is_studio_role');
            $table->json('metadata')->nullable()->after('parent_role_id');
            $table->softDeletes();

            $table->foreign('parent_role_id')->references('id')->on('roles')->onDelete('set null');
            $table->index(['is_template', 'team_id']);
            $table->index(['is_admin_role', 'team_id']);
            $table->index(['is_studio_role', 'team_id']);
        });

        // Create role audit logs table
        Schema::create('role_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->string('action'); // created, updated, deleted, permissions_changed, users_assigned, etc.
            $table->json('changes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['role_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_audit_logs');

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->dropForeign(['parent_role_id']);
            $table->dropIndex(['is_template', 'team_id']);
            $table->dropIndex(['is_admin_role', 'team_id']);
            $table->dropIndex(['is_studio_role', 'team_id']);
            $table->dropColumn([
                'display_name',
                'description',
                'is_template',
                'is_admin_role',
                'is_studio_role',
                'parent_role_id',
                'metadata',
            ]);
        });
    }
};
