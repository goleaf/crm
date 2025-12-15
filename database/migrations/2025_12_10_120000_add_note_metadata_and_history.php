<?php

declare(strict_types=1);

use App\Enums\NoteHistoryEvent;
use App\Enums\NoteVisibility;
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
        Schema::table('notes', function (Blueprint $table): void {
            $table->string('category')->nullable()->index()->after('title');
            $table->string('visibility')
                ->default(NoteVisibility::INTERNAL->value)
                ->index()
                ->after('category');
            $table->boolean('is_template')->default(false)->after('visibility');
        });

        Schema::create('note_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('category')->nullable();
            $table->string('visibility')->default(NoteVisibility::INTERNAL->value);
            $table->text('body')->nullable();
            $table->string('event')->default(NoteHistoryEvent::UPDATED->value);
            $table->timestamps();

            $table->index(['note_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table): void {
            $table->dropColumn(['category', 'visibility', 'is_template']);
        });

        Schema::dropIfExists('note_histories');
    }
};
