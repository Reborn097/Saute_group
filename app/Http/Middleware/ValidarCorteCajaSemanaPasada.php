<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ValidarCorteCajaSemanaPasada
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user) return $next($request);

        // Solo encargado_cafeteria
        if (($user->role ?? '') !== 'encargado_cafeteria') {
            return $next($request);
        }

        $unidadId = $user->unidad_operativa_id ?? null; // 👈 si tu user usa otro campo, cámbialo aquí
        if (!$unidadId) return $next($request);

        // Semana pasada (Lun-Vie)
        $inicio = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY)->toDateString();
        $dias = [];
        for ($i = 0; $i < 5; $i++) {
            $dias[] = Carbon::parse($inicio)->addDays($i)->toDateString();
        }

        // ¿ya hay registros para esos 5 días?
        $registrados = DB::table('corte_caja')
            ->where('unidad_id', $unidadId)   // 👈 en tu tabla es unidad_id
            ->whereIn('fecha', $dias)
            ->pluck('fecha')
            ->unique()
            ->values()
            ->all();

        $faltan = array_values(array_diff($dias, $registrados));

        // No bloquear rutas permitidas (evitar loop)
        $rutasPermitidas = [
            'dashboard.corte-caja.forzar',
            'dashboard.corte-caja.forzar.guardar',
            'logout',
        ];

        $rutaActual = optional($request->route())->getName();

        if (count($faltan) > 0 && !in_array($rutaActual, $rutasPermitidas, true)) {
            return redirect()->route('dashboard.corte-caja.forzar');
        }

        return $next($request);
    }
}
