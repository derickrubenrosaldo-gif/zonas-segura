<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class BulletinController
{
    public static function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'area' => ['required', 'string', 'max:120'],
            'issued_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after:issued_at'],
            'url' => ['required', 'url:https', 'max:512'],
        ];
    }

    public static function approvedSource(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return in_array($host, ['www.veracruz.gob.mx', 'veracruz.gob.mx', 'smn.conagua.gob.mx', 'preparados.gob.mx'], true)
            && parse_url($url, PHP_URL_USER) === null;
    }

    public function index(): JsonResponse
    {
        $records = json_decode((string) config('services.bulletins'), true);
        $records = is_array($records) ? $records : [];
        $now = now();
        if (config('database.connections.pgsql.url')) {
            try {
                $published = DB::table('portal_bulletins')->where('published', true)
                    ->where('issued_at', '<=', $now->toIso8601String())
                    ->where('expires_at', '>', $now->toIso8601String())
                    ->orderByDesc('issued_at')->limit(30)->get()
                    ->map(fn ($entry) => (array) $entry)->all();
                $records = [...$published, ...$records];
            } catch (\Illuminate\Database\QueryException) {
                // Keep the previous configuration available during migration.
            }
        }
        $bulletins = [];

        foreach (is_array($records) ? array_slice($records, 0, 30) : [] as $record) {
            if (! is_array($record)) {
                continue;
            }

            $validator = Validator::make($record, self::rules());
            if ($validator->fails()) {
                continue;
            }

            $entry = $validator->validated();
            if (! self::approvedSource($entry['url'])) {
                continue;
            }

            $issued = Carbon::parse($entry['issued_at']);
            $expires = Carbon::parse($entry['expires_at']);
            if ($issued->gt($now) || $expires->lte($now)) {
                continue;
            }

            $bulletins[] = [
                ...$entry,
                'issued_at' => $issued->utc()->toIso8601String(),
                'expires_at' => $expires->utc()->toIso8601String(),
            ];
        }

        usort($bulletins, fn ($a, $b) => strcmp($b['issued_at'], $a['issued_at']));

        return new JsonResponse(['data' => $bulletins], 200, ['Cache-Control' => 'no-store']);
    }
}
