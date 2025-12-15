<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table): void {
            // SLA and escalation fields
            $table->integer('escalation_level')->default(0)->after('escalated_at');
            $table->timestamp('sla_breach_at')->nullable()->after('sla_due_at');
            $table->boolean('sla_breached')->default(false)->after('sla_breach_at');
            $table->integer('response_time_minutes')->nullable()->after('first_response_at');
            $table->integer('resolution_time_minutes')->nullable()->after('resolved_at');

            // Portal visibility
            $table->boolean('portal_visible')->default(false)->after('customer_portal_url');

            // Knowledge base linkage
            $table->foreignId('knowledge_article_id')->nullable()->after('knowledge_base_reference')->constrained('knowledge_articles')->nullOnDelete();

            // Add indexes for performance
            $table->index(['sla_due_at', 'sla_breached']);
            $table->index(['escalation_level']);
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table): void {
            $table->dropForeign(['knowledge_article_id']);
            $table->dropColumn([
                'escalation_level',
                'sla_breach_at',
                'sla_breached',
                'response_time_minutes',
                'resolution_time_minutes',
                'portal_visible',
                'knowledge_article_id',
            ]);
        });
    }
};
