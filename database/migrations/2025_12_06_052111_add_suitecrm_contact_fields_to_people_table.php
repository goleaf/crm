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
        Schema::table('people', function (Blueprint $table): void {
            $table->string('primary_email')->nullable()->after('name');
            $table->string('alternate_email')->nullable()->after('primary_email');

            $table->string('phone_mobile', 50)->nullable()->after('alternate_email');
            $table->string('phone_office', 50)->nullable()->after('phone_mobile');
            $table->string('phone_home', 50)->nullable()->after('phone_office');
            $table->string('phone_fax', 50)->nullable()->after('phone_home');

            $table->string('job_title')->nullable()->after('phone_fax');
            $table->string('department')->nullable()->after('job_title');
            $table->foreignId('reports_to_id')
                ->nullable()
                ->after('department')
                ->constrained('people')
                ->nullOnDelete();

            $table->date('birthdate')->nullable()->after('reports_to_id');
            $table->string('assistant_name')->nullable()->after('birthdate');
            $table->string('assistant_phone', 50)->nullable()->after('assistant_name');
            $table->string('assistant_email')->nullable()->after('assistant_phone');

            $table->string('address_street')->nullable()->after('assistant_email');
            $table->string('address_city')->nullable()->after('address_street');
            $table->string('address_state')->nullable()->after('address_city');
            $table->string('address_postal_code', 20)->nullable()->after('address_state');
            $table->string('address_country', 100)->nullable()->after('address_postal_code');

            $table->json('social_links')->nullable()->after('address_country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table): void {
            $table->dropForeign(['reports_to_id']);
            $table->dropColumn([
                'primary_email',
                'alternate_email',
                'phone_mobile',
                'phone_office',
                'phone_home',
                'phone_fax',
                'job_title',
                'department',
                'reports_to_id',
                'birthdate',
                'assistant_name',
                'assistant_phone',
                'assistant_email',
                'address_street',
                'address_city',
                'address_state',
                'address_postal_code',
                'address_country',
                'social_links',
            ]);
        });
    }
};
