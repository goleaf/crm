<?php

declare(strict_types=1);

use App\Services\AI\CodebaseIntrospectionService;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Mateffy\Introspect\DTO\Model;
use Mateffy\Introspect\DTO\ModelProperty;
use Mateffy\Introspect\LaravelIntrospect;
use Mockery;

beforeEach(function (): void {
    Cache::flush();

    config([
        'cache.default' => 'array',
        'introspect.directories' => ['app', 'app-modules/SystemAdmin/src'],
        'introspect.namespaces' => ['App\\', 'Relaticle\\'],
        'introspect.cache.ttl' => 0,
    ]);

    $this->introspect = Mockery::mock(LaravelIntrospect::class);
    app()->instance(LaravelIntrospect::class, $this->introspect);
});

afterEach(function (): void {
    Mockery::close();
});

it('builds a snapshot covering views, routes, classes, and models', function (): void {
    $views = collect(['home', 'components.button']);

    $route = new Route(
        methods: ['GET'],
        uri: '/companies',
        action: [
            'as' => 'companies.index',
            'uses' => [\App\Http\Controllers\HomeController::class, '__invoke'],
            'controller' => \App\Http\Controllers\HomeController::class.'@__invoke',
            'middleware' => ['auth'],
        ],
    );

    $modelProperty = new ModelProperty(
        name: 'name',
        description: 'Company name',
        default: null,
        readable: true,
        writable: true,
        fillable: true,
        hidden: false,
        appended: false,
        relation: false,
        cast: null,
        types: collect(['string']),
    );

    $modelDto = new Model(
        classpath: \App\Models\Company::class,
        description: 'Company record',
        properties: collect([$modelProperty])->keyBy('name'),
    );

    $this->introspect->shouldReceive('views')->andReturn(fakeQuery($views));
    $this->introspect->shouldReceive('routes')->andReturn(fakeQuery(collect([$route])));
    $this->introspect->shouldReceive('classes')->andReturn(fakeQuery(collect([\App\Models\Company::class, 'Vendor\\Package\\Thing'])));
    $this->introspect->shouldReceive('models')->andReturn(fakeQuery(collect([$modelDto])));

    $service = resolve(CodebaseIntrospectionService::class);

    $snapshot = $service->snapshot(forceRefresh: true);

    expect($snapshot['directories'])->toBe(['app', 'app-modules/SystemAdmin/src']);
    expect($snapshot['views'])->toBe($views->all());
    expect($snapshot['classes'])->toBe([\App\Models\Company::class]);
    expect($snapshot['routes'][0]['name'])->toBe('companies.index');
    expect($snapshot['routes'][0]['uri'])->toBe('companies');
    expect($snapshot['routes'][0]['middlewares'])->toContain('auth');
    expect($snapshot['models'][0]['classpath'])->toBe(\App\Models\Company::class);
    expect($snapshot['models'][0]['properties'][0]['name'])->toBe('name');
});

/**
 * @param  Collection<int, mixed>  $items
 */
function fakeQuery(Collection $items): object
{
    return new readonly class($items)
    {
        public function __construct(private Collection $items) {}

        public function get(): Collection
        {
            return $this->items;
        }
    };
}
