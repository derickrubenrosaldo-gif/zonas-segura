<?php

namespace App\Http\Controllers;

use App\Models\IotStation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StationController
{
    public function index(): JsonResponse
    {
        if (! config('database.connections.pgsql.url')) {
            return new JsonResponse(['message' => 'Las estaciones IoT todavía no están conectadas.'], 503);
        }

        $stations = IotStation::query()->where('is_active', true)->orderBy('station_code')->get()
            ->map(function (IotStation $station): array {
                $data = $station->toArray();
                if (! $station->read_at || $station->read_at->lt(now()->subMinutes(20))) {
                    $data['reading_status'] = 'offline';
                }

                return $data;
            });

        return new JsonResponse(['data' => $stations]);
    }

    public function history(string $stationCode): JsonResponse
    {
        if (! config('database.connections.pgsql.url')) {
            return new JsonResponse(['message' => 'Las estaciones IoT todavía no están conectadas.'], 503);
        }

        $station = IotStation::query()->where('station_code', $stationCode)->where('is_active', true)->first();
        if (! $station) {
            return new JsonResponse(['message' => 'Estación no registrada.'], 404);
        }

        $readings = DB::table('iot_readings')
            ->where('station_id', $station->id)
            ->where('read_at', '>=', now()->subDay())
            ->orderByDesc('read_at')
            ->limit(24)
            ->get(['read_at', 'value'])
            ->map(fn ($reading) => [
                'read_at' => Carbon::parse($reading->read_at)->toIso8601String(),
                'value' => (float) $reading->value,
            ]);

        return new JsonResponse(['data' => $readings, 'unit' => $station->metric_unit]);
    }

    public function register(Request $request): JsonResponse
    {
        if (! $this->authorized($request, 'services.admin_api_key')) {
            return new JsonResponse(['message' => 'No autorizado.'], 401);
        }
        if (! config('database.connections.pgsql.url')) {
            return new JsonResponse(['message' => 'La base de datos todavía no está conectada.'], 503);
        }

        return new JsonResponse(['data' => $this->saveStation($request, new IotStation())], 201);
    }

    public function saveStation(Request $request, IotStation $station): IotStation
    {
        $data = $request->validate([
            'station_code' => ['required', 'string', 'max:48', Rule::unique('iot_stations', 'station_code')->ignore($station)],
            'name' => ['required', 'string', 'max:120'],
            'neighborhood' => ['required', 'string', 'max:120'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'sensor_type' => ['required', Rule::in(['rain', 'water_level', 'temperature', 'air_quality', 'other'])],
            'metric_name' => ['required', 'string', 'max:64'],
            'metric_unit' => ['required', 'string', 'max:16'],
            'warning_threshold' => ['nullable', 'numeric'],
            'critical_threshold' => ['nullable', 'numeric'],
        ]);

        $station->fill([
            ...$data,
            'latitude' => round((float) $data['latitude'], 3),
            'longitude' => round((float) $data['longitude'], 3),
            'reading_status' => $station->reading_status ?? 'offline',
            'is_active' => $station->is_active ?? true,
        ]);

        $station->save();

        return $station;
    }

    public function ingest(Request $request, string $stationCode): JsonResponse
    {
        if (! $this->authorized($request, 'services.iot_api_key')) {
            return new JsonResponse(['message' => 'No autorizado.'], 401);
        }
        if (! config('database.connections.pgsql.url')) {
            return new JsonResponse(['message' => 'La base de datos todavía no está conectada.'], 503);
        }

        $data = $request->validate([
            'value' => ['required', 'numeric', 'between:-99999999.99,99999999.99'],
            'read_at' => ['required', 'date', 'before_or_equal:now'],
        ]);

        return DB::transaction(function () use ($stationCode, $data): JsonResponse {
            $station = IotStation::query()
                ->where('station_code', $stationCode)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();
            if (! $station) {
                return new JsonResponse(['message' => 'Estación no registrada.'], 404);
            }

            $value = (float) $data['value'];
            $readAt = Carbon::parse($data['read_at'])->utc()->startOfSecond();
            $inserted = DB::table('iot_readings')->insertOrIgnore([
                'station_id' => $station->id,
                'read_at' => $readAt->toIso8601String(),
                'value' => $value,
            ]);

            if ($inserted && (! $station->read_at || $readAt->gte($station->read_at))) {
                $status = 'normal';
                if ($station->critical_threshold !== null && $value >= $station->critical_threshold) {
                    $status = 'alert';
                } elseif ($station->warning_threshold !== null && $value >= $station->warning_threshold) {
                    $status = 'attention';
                }

                DB::table('iot_stations')->where('id', $station->id)->update([
                    'reading_value' => $value,
                    'reading_status' => $status,
                    'read_at' => $readAt->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                ]);
            }

            return new JsonResponse(['data' => $station->fresh()]);
        });
    }

    private function authorized(Request $request, string $key): bool
    {
        $configured = (string) config($key);
        $provided = (string) $request->bearerToken();

        return $configured !== '' && $provided !== '' && hash_equals($configured, $provided);
    }
}
