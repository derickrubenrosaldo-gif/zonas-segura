<?php

namespace App\Http\Controllers;

use App\Http\Middleware\PanelAccess;
use App\Models\Incident;
use App\Models\IotStation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PanelController
{
    public const SECTIONS = [
        'reportes' => 'Reportes', 'estaciones' => 'Estaciones',
        'avisos' => 'Avisos oficiales', 'portada' => 'Portada',
    ];

    public function loginForm()
    {
        return response()->view('admin.login', ['configured' => PanelAccess::configured()])
            ->header('Cache-Control', 'no-store, private')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function login(Request $request)
    {
        abort_unless(PanelAccess::configured(), 503);
        $username = $request->input('username');
        $password = $request->input('password');
        $validPassword = is_string($password) && ! str_contains($password, "\0")
            && $this->verifyPassword($password);
        $validUsername = is_string($username) && hash_equals(
            strtolower((string) config('services.admin_panel_username')),
            strtolower(trim($username))
        );
        if (! $validUsername || ! $validPassword) {
            return redirect()->route('panel.login')->with('login_error', 'El usuario o la contraseña son incorrectos.')
                ->header('Cache-Control', 'no-store, private');
        }

        $request->session()->regenerate(true);
        $request->session()->put('panel', ['fingerprint' => PanelAccess::fingerprint(), 'expires_at' => time() + 1800]);

        return redirect()->route('panel.section', 'reportes')->header('Cache-Control', 'no-store, private');
    }

    private function verifyPassword(string $password): bool
    {
        $hash = (string) config('services.admin_panel_password_hash');
        if (strlen($password) <= 72 && password_verify($password, $hash)) {
            return true;
        }

        $trimmed = trim($password, " \t\r\n");

        return $trimmed !== $password && strlen($trimmed) <= 72 && password_verify($trimmed, $hash);
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('panel.login');
    }

    public function section(string $section, ?int $item = null)
    {
        abort_unless(array_key_exists($section, self::SECTIONS), 404);
        abort_if($section === 'portada' && $item !== null, 404);
        $table = match ($section) {
            'reportes' => 'incidents',
            'estaciones' => 'iot_stations',
            'avisos' => 'portal_bulletins',
            default => null,
        };

        $rows = $table ? DB::table($table)->orderByDesc('id')->paginate(20) : [];
        $record = $table ? ($item ? DB::table($table)->find($item) : null) : (object) SiteSettingsController::settings();
        abort_if($item && ! $record, 404);

        return view('admin.panel', [
            'sections' => self::SECTIONS, 'section' => $section, 'rows' => $rows,
            'record' => $record, 'item' => $item,
        ]);
    }

    public function save(Request $request, string $section, ?int $item = null)
    {
        switch ($section) {
            case 'reportes':
                $incident = $item ? Incident::findOrFail($item) : new Incident();
                if ($incident->affected_area) {
                    $request->merge(['affected_area' => $incident->affected_area]);
                }
                app(IncidentController::class)->saveInstitutional($request, $incident, true);
                $item = $incident->id;
                break;
            case 'estaciones':
                $station = $item ? IotStation::findOrFail($item) : new IotStation();
                if ($item) {
                    $request->merge(['station_code' => $station->station_code]);
                }
                $item = DB::transaction(function () use ($request, $station): int {
                    $station = app(StationController::class)->saveStation($request, $station);
                    $station->update(['is_active' => $request->boolean('is_active')]);

                    return $station->id;
                });
                break;
            case 'avisos':
                $data = $request->validate(BulletinController::rules());
                if (! BulletinController::approvedSource($data['url'])) {
                    throw ValidationException::withMessages(['url' => 'Usa el enlace original de Protección Civil, el SMN o Preparados México.']);
                }
                foreach (['issued_at', 'expires_at'] as $field) {
                    $data[$field] = Carbon::parse($data[$field], config('app.timezone'))->toIso8601String();
                }
                $data['published'] = $request->boolean('published');

                if ($item) {
                    abort_unless(DB::table('portal_bulletins')->where('id', $item)->exists(), 404);
                    DB::table('portal_bulletins')->where('id', $item)->update($data);
                } else {
                    $item = DB::table('portal_bulletins')->insertGetId($data);
                }
                break;
            case 'portada':
                abort_if($item !== null, 404);
                $data = $request->validate([
                    'title' => ['required', 'string', 'max:90'],
                    'location' => ['required', 'string', 'max:80'],
                    'notice' => ['nullable', 'string', 'max:240'],
                ]);
                $settings = [];
                foreach ($data as $key => $value) {
                    $settings[] = ['key' => $key, 'value' => $value ?? ''];
                }
                DB::table('portal_settings')->upsert($settings, ['key'], ['value']);
                break;
            default:
                abort(404);
        }

        return redirect()->route('panel.section', [$section, $item])->with('saved', 'Cambios guardados.');
    }
}
