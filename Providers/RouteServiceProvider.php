<?php

namespace Modules\SkyOps\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleNamespace = 'Modules\\SkyOps\\Http\\Controllers';

    public function map(): void
    {
        $this->mapWebRoutes();
        $this->mapApiRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('SkyOps', 'Routes/web.php'));
    }

    protected function mapApiRoutes(): void
    {
        $path = module_path('SkyOps', 'Routes/api.php');
        if (file_exists($path)) {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->moduleNamespace . '\\Api')
                ->group($path);
        }
    }
}
