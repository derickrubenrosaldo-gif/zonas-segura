<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IncidentController
{
    public function index(Request $request): JsonResponse
    {
        if (! config('database.connections.pgsql.url')) {
            return new JsonResponse(['message' => 'La base de datos todavía no está conectada.'], 503);
        }

        $filters = $request->validate([
            'category' => ['nullable', Rule::in(['flood', 'fire', 'traffic', 'fallen_tree', 'other'])],
            'status' => ['nullable', Rule::in(['in_review', 'active', 'resolved'])],
            'from' => ['nullable', 'date'],
        ]);

        $incidents = Incident::query()
            ->whereIn('status', ['in_review', 'active', 'resolved'])
            ->when($filters['category'] ?? null, fn ($query, $value) => $query->where('category', $value))
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($filters['from'] ?? null, fn ($query, $value) => $query->whereDate('occurred_at', '>=', $value))
            ->latest('occurred_at')
            ->limit(300)
            ->get(['id', 'category', 'neighborhood', 'description', 'source_type', 'source_name', 'latitude', 'longitude', 'status', 'occurred_at', 'updated_at', 'affected_area', 'source_url']);

        return new JsonResponse(['data' => $incidents]);
    }

    public function store(Request $request): JsonResponse
    {
        return new JsonResponse([
            'message' => 'La recepción ciudadana está pausada. Esta demostración no transmite reportes a las autoridades.',
        ], 503);
    }

    public function updateStatus(Request $request, Incident $incident): JsonResponse
    {
        if (! $this->authorized($request)) {
            return new JsonResponse(['message' => 'No autorizado.'], 401);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(['in_review', 'active', 'resolved'])],
        ]);

        $incident->update([
            'status' => $data['status'],
            'reviewed_at' => now(),
        ]);

        return new JsonResponse(['data' => $incident->fresh()]);
    }

    public function storeInstitutional(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return new JsonResponse(['message' => 'No autorizado.'], 401);
        }
        if (! config('database.connections.pgsql.url')) {
            return new JsonResponse(['message' => 'La base de datos todavía no está conectada.'], 503);
        }

        return new JsonResponse(['data' => $this->saveInstitutional($request, new Incident())], 201);
    }

    public function saveInstitutional(Request $request, Incident $incident, bool $allowHidden = false): Incident
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(['flood', 'fire', 'traffic', 'fallen_tree', 'other'])],
            'neighborhood' => ['required', 'string', 'min:2', 'max:120'],
            'description' => ['required', 'string', 'min:15', 'max:800'],
            'source_type' => ['required', Rule::in($incident->source_type === 'community' ? ['community', 'firefighters', 'public_security', 'civil_protection'] : ['firefighters', 'public_security', 'civil_protection'])],
            'source_name' => ['nullable', 'string', 'max:120'],
            'source_url' => ['required_with:affected_area', 'nullable', 'url:https', 'max:512'],
            'affected_area' => ['nullable', 'array:type,coordinates'],
            'affected_area.type' => ['required_with:affected_area', 'in:Polygon'],
            'affected_area.coordinates' => ['required_with:affected_area', 'array', 'list', 'size:1'],
            'affected_area.coordinates.0' => ['required_with:affected_area', 'array', 'list', 'min:4', 'max:300'],
            'affected_area.coordinates.0.*' => ['required', 'array', 'list', 'size:2'],
            'affected_area.coordinates.0.*.0' => ['required', 'numeric', 'between:-180,180'],
            'affected_area.coordinates.0.*.1' => ['required', 'numeric', 'between:-90,90'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
            'status' => ['nullable', Rule::in($allowHidden ? ['in_review', 'active', 'resolved', 'pending'] : ['in_review', 'active', 'resolved'])],
        ]);

        $ring = $data['affected_area']['coordinates'][0] ?? null;
        if ($ring !== null) {
            $ring = array_map(fn ($point) => [(float) $point[0], (float) $point[1]], $ring);
            $host = strtolower((string) parse_url($data['source_url'], PHP_URL_HOST));
            if (! str_ends_with($host, '.gob.mx') || parse_url($data['source_url'], PHP_URL_USER) !== null) {
                throw ValidationException::withMessages(['source_url' => 'Enlaza el documento en un dominio oficial gob.mx.']);
            }
            if ($ring[0] !== $ring[array_key_last($ring)] || count(array_unique(array_map('json_encode', $ring))) < 3) {
                throw ValidationException::withMessages(['affected_area' => 'Se requiere un perímetro cerrado con al menos tres vértices distintos.']);
            }
            $data['affected_area']['coordinates'] = [$ring];
        }

        $incident->fill([
            ...$data,
            'latitude' => round((float) $data['latitude'], 3),
            'longitude' => round((float) $data['longitude'], 3),
            'source_name' => $data['source_name'] ?? null,
            'status' => $data['status'] ?? 'in_review',
            'affected_area' => $data['affected_area'] ?? $incident->affected_area,
            'source_url' => $data['source_url'] ?? $incident->source_url,
            'reviewed_at' => now(),
        ]);

        $incident->save();

        return $incident;
    }

    private function authorized(Request $request): bool
    {
        $configuredKey = (string) config('services.admin_api_key');
        $providedKey = (string) $request->bearerToken();

        return $configuredKey !== '' && $providedKey !== '' && hash_equals($configuredKey, $providedKey);
    }
}
