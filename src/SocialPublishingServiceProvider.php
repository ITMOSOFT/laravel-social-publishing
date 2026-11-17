<?php

namespace ItmoSoft\SocialPublishing;

use Illuminate\Support\ServiceProvider;

class SocialPublishingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/itmosoft-social-publishing.php',
            'itmosoft-social-publishing'
        );

        $this->app->singleton(SocialPublishingManager::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/itmosoft-social-publishing.php' => config_path('itmosoft-social-publishing.php'),
        ], 'itmosoft-social-publishing-config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'itmosoft-social-publishing-migrations');
    }
}
