<?php

declare(strict_types=1);

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\FaviconServiceProvider::class,
    App\Providers\Filament\AppPanelProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,
    App\Providers\JetstreamServiceProvider::class,
    App\Providers\HttpClientServiceProvider::class,
    App\Providers\MacroServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,
    Relaticle\Documentation\DocumentationServiceProvider::class,
    Relaticle\SystemAdmin\SystemAdminPanelProvider::class,
];
