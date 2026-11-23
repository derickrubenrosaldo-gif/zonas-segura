<?php

namespace App\Providers;

use App\Database\NeonPostgresConnector;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('db.connector.pgsql', NeonPostgresConnector::class);
    }

    public function boot(): void
    {
        if ($this->app->isProduction() && parse_url(config('app.url'), PHP_URL_SCHEME) === 'https') {
            URL::forceScheme('https');
        }

        $compiledViews = config('view.compiled');
        if (! is_dir($compiledViews)) {
            mkdir($compiledViews, 0775, true);
        }
    }
}
