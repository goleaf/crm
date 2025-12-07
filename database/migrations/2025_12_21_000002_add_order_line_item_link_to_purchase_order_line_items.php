<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_line_items', function (Blueprint $table): void {
            $table->foreign('order_line_item_id')
                ->references('id')
                ->on('order_line_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_line_items', function (Blueprint $table): void {
            $table->dropForeign(['order_line_item_id']);
        });
    }
};
