<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register spatie/laravel-backup even when bootstrap/cache/packages.php is stale, so backup:run always exists
        if (class_exists(\Spatie\Backup\BackupServiceProvider::class)) {
            $this->app->register(\Spatie\Backup\BackupServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
