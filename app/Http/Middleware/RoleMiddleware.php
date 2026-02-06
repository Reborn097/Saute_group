<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle($request, Closure $next, ...$roles)
{
    if (!auth()->check()) {
        abort(403);
    }

    $normalize = function ($r) {
        $r = mb_strtolower(trim((string) $r), 'UTF-8');
        $r = preg_replace('/[^a-z0-9]+/i', '_', $r);
        $r = preg_replace('/_+/', '_', $r);
        return trim($r, '_');
    };

    // Allow comma-separated roles in the middleware parameter, or multiple params.
    if (count($roles) === 1 && str_contains((string)$roles[0], ',')) {
        $roles = explode(',', (string)$roles[0]);
    }
    $roles = array_map($normalize, array_map('trim', $roles));
    $userRole = $normalize(auth()->user()->role ?? '');

    if (!in_array($userRole, $roles, true)) {
        abort(403);
    }

    return $next($request);
}

}
