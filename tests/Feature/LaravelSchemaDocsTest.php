<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

beforeEach(function (): void {
    $this->schemaDocsPath = storage_path('framework/testing-schema-docs.yaml');
    config()->set('laravel-schema-docs.yaml_file', $this->schemaDocsPath);

    File::delete($this->schemaDocsPath);
});

it('generates schema docs yaml via artisan command', function (): void {
    Artisan::call('laravelschemadocs:generate');

    expect(File::exists($this->schemaDocsPath))->toBeTrue();

    $schema = Yaml::parseFile($this->schemaDocsPath);

    expect($schema)->toHaveKey('tables');
    expect($schema['tables'])->toBeArray();
    expect($schema['tables'])->toHaveKey('users');
});

it('requires authentication for the schema docs dashboard', function (): void {
    File::put($this->schemaDocsPath, Yaml::dump(['tables' => []], 4));

    $this->get(route('laravelschemadocs.index'))
        ->assertRedirect(route('login'));
});

it('renders the schema docs dashboard when YAML is present', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $user->switchTeam($user->personalTeam());

    $schema = [
        'tables' => [
            'users' => [
                'columns' => [
                    'id' => [
                        'type' => 'integer',
                        'nullable' => false,
                        'default' => null,
                        'primary' => true,
                        'unique' => false,
                        'comment' => '',
                    ],
                ],
                'relations' => [],
            ],
        ],
    ];

    File::put($this->schemaDocsPath, Yaml::dump($schema, 4));

    $this->actingAs($user)
        ->get(route('laravelschemadocs.index'))
        ->assertOk()
        ->assertSee('Database Tables')
        ->assertSee('users');
});
