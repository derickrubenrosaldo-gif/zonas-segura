<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Keep the public reporting API stateless.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn ($request, $exception) => $request->is('api/*') || $request->expectsJson()
        );
    })
    ->create();

if (getenv('VERCEL') || str_starts_with(__DIR__, '/var/task/')) {
    // Vercel packages the source and vendor tree as read-only.
    $runtimePath = sys_get_temp_dir().'/zona-segura-runtime';
    foreach (['', '/framework/cache', '/framework/sessions', '/framework/views', '/logs'] as $directory) {
        $path = $runtimePath.$directory;
        if (! is_dir($path) && ! mkdir($path, 0700, true) && ! is_dir($path)) {
            throw new RuntimeException('No se pudo preparar el directorio temporal.');
        }
    }

    $app->useStoragePath($runtimePath);
    foreach (['APP_PACKAGES_CACHE' => 'packages.php', 'APP_SERVICES_CACHE' => 'services.php'] as $key => $file) {
        $_ENV[$key] = $_SERVER[$key] = $runtimePath.'/'.$file;
    }
}

return $app;
