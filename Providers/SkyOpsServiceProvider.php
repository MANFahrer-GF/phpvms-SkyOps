<?php

namespace Modules\SkyOps\Providers;

use Illuminate\Support\ServiceProvider;

class SkyOpsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'SkyOps';
    protected string $moduleNameLower = 'skyops';

    /**
     * Boot the module services.
     *
     * SkyOps uses phpVMS 7 Eloquent models directly — no custom database
     * tables or views are created. Installation is just upload + enable.
     * Since v1.1.3 there is exactly one migration: an index on the core
     * `flights` table for the departures board (no data is changed).
     */
    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerTranslations();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/migrations');
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->moduleName, 'Config/config.php');

        $this->publishes([
            $configPath => config_path($this->moduleNameLower . '.php'),
        ], 'config');

        $this->mergeConfigFrom($configPath, $this->moduleNameLower);
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], 'views');

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'Resources/lang');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom($sourcePath, $this->moduleNameLower);
        }
    }

    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (app('config')->get('view.paths') as $path) {
            $modulePath = $path . '/modules/' . $this->moduleNameLower;
            if (is_dir($modulePath)) {
                $paths[] = $modulePath;
            }
        }
        return $paths;
    }
}
