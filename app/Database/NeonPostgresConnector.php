<?php

namespace App\Database;

use Illuminate\Database\Connectors\PostgresConnector;

class NeonPostgresConnector extends PostgresConnector
{
    protected function getDsn(array $config)
    {
        $dsn = parent::getDsn($config);

        // Older libpq builds cannot identify the Neon endpoint via TLS SNI.
        if (preg_match('/\A(ep-[a-z0-9-]+)\.[a-z0-9.-]+\.neon\.tech\z/', $config['host'] ?? '', $matches)) {
            $dsn .= ";options='endpoint={$matches[1]}'";
        }

        return $dsn;
    }
}
