<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

if (PHP_SAPI !== 'cli' || getenv('VERCEL_ENV') !== 'production') {
    exit;
}

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$console = $app->make(Kernel::class);
$console->bootstrap();

$directUrl = \Illuminate\Support\Env::get('DATABASE_URL_UNPOOLED');
if (! \Illuminate\Support\Env::get('ZONA_SEGURA_SUPABASE_URL') && is_string($directUrl) && $directUrl !== '') {
    config(['database.connections.pgsql.url' => $directUrl]);
}

if (! config('database.connections.pgsql.url')) {
    fwrite(STDERR, "Missing production database configuration.\n");
    exit(1);
}

try {
    DB::transaction(function () use ($console): void {
        DB::statement("SET LOCAL lock_timeout = '10s'");
        DB::select('select pg_advisory_xact_lock(827190, 119043)');
        if ($console->call('migrate', ['--force' => true, '--no-interaction' => true]) !== 0) {
            throw new RuntimeException('Migration did not complete.');
        }
    });
    fwrite(STDOUT, "Zona Segura: production migrations applied.\n");
} catch (Throwable $error) {
    $message = strtolower($error->getMessage());
    preg_match('/sqlstate\[([0-9a-z]{5})\]/', $message, $matches);
    $state = isset($matches[1]) ? ' ('.strtoupper($matches[1]).')' : '';
    fwrite(STDERR, "Zona Segura: migration failed$state.\n");
    exit(1);
}
