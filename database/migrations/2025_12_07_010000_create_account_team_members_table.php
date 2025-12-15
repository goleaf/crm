<?php

declare(strict_types=1);

use App\Enums\AccountTeamAccessLevel;
use App\Enums\AccountTeamRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_team_members', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('role')->default(AccountTeamRole::ACCOUNT_MANAGER->value);
            $table->string('access_level')->default(AccountTeamAccessLevel::EDIT->value);

            $table->timestamps();

            $table->unique(['company_id', 'user_id']);
            $table->index(['team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_team_members');
    }
};
