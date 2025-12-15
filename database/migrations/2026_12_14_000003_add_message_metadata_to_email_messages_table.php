<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_messages', function (Blueprint $table): void {
            if (! Schema::hasColumn('email_messages', 'message_id')) {
                $table->string('message_id')->nullable()->after('id');
            }
            if (! Schema::hasColumn('email_messages', 'in_reply_to')) {
                $table->string('in_reply_to')->nullable()->after('message_id');
            }
            if (! Schema::hasColumn('email_messages', 'references')) {
                $table->text('references')->nullable()->after('in_reply_to');
            }
        });
    }

    public function down(): void
    {
        Schema::table('email_messages', function (Blueprint $table): void {
            $columns = [];

            if (Schema::hasColumn('email_messages', 'references')) {
                $columns[] = 'references';
            }
            if (Schema::hasColumn('email_messages', 'in_reply_to')) {
                $columns[] = 'in_reply_to';
            }
            if (Schema::hasColumn('email_messages', 'message_id')) {
                $columns[] = 'message_id';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
