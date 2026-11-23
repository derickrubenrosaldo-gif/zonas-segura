<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SiteSettingsController
{
    private const DEFAULTS = [
        'title' => 'Mapa de afectaciones',
        'location' => 'Coatzacoalcos, Veracruz',
        'notice' => '',
    ];

    public static function settings(): array
    {
        return array_replace(self::DEFAULTS, DB::table('portal_settings')
            ->whereIn('key', array_keys(self::DEFAULTS))->pluck('value', 'key')->all());
    }

    public function index(): JsonResponse
    {
        $settings = self::DEFAULTS;
        if (config('database.connections.pgsql.url')) {
            try {
                $settings = self::settings();
            } catch (\Illuminate\Database\QueryException) {
                // The static public page remains available while the schema is being deployed.
            }
        }

        return new JsonResponse(['data' => $settings], 200, ['Cache-Control' => 'no-store']);
    }
}
