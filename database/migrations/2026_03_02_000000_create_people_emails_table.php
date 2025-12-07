<?php

declare(strict_types=1);

use App\Enums\ContactEmailType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people_emails', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('people_id')->constrained('people')->cascadeOnDelete();
            $table->string('email', 255);
            $table->string('type', 50)->default(ContactEmailType::Work->value);
            $table->boolean('is_primary')->default(false)->index();
            $table->timestamps();

            $table->unique(['people_id', 'email']);
        });

        // Backfill from existing columns.
        $people = DB::table('people')
            ->select('id', 'primary_email', 'alternate_email')
            ->get();

        foreach ($people as $person) {
            $emails = [];

            if ($person->primary_email !== null) {
                $emails[] = [
                    'people_id' => $person->id,
                    'email' => $person->primary_email,
                    'type' => ContactEmailType::Work->value,
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($person->alternate_email !== null && $person->alternate_email !== $person->primary_email) {
                $emails[] = [
                    'people_id' => $person->id,
                    'email' => $person->alternate_email,
                    'type' => ContactEmailType::Personal->value,
                    'is_primary' => $person->primary_email === null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($emails === []) {
                continue;
            }

            DB::table('people_emails')->insert($emails);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('people_emails');
    }
};
