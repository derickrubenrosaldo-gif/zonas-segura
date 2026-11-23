<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PanelAccess
{
    public static function configured(): bool
    {
        return (string) config('services.admin_panel_username') !== ''
            && password_get_info((string) config('services.admin_panel_password_hash'))['algoName'] === 'bcrypt'
            && (bool) config('database.connections.pgsql.url')
            && config('session.driver') === 'database';
    }

    public static function fingerprint(): string
    {
        return hash('sha256', config('services.admin_panel_username')."\0".config('services.admin_panel_password_hash'));
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! self::configured()
            || ! hash_equals(self::fingerprint(), (string) $request->session()->get('panel.fingerprint'))
            || (int) $request->session()->get('panel.expires_at', 0) <= time()) {
            $request->session()->forget('panel');

            return redirect()->route('panel.login')->header('Cache-Control', 'no-store');
        }

        return $this->privateResponse($next($request));
    }

    private function privateResponse(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
