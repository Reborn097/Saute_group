<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ComensalRegistro;
use Carbon\Carbon;

class ValidarComensalesSemanaPasada
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user) return $next($request);

        // ✅ Solo aplica a estos roles (ajusta si quieres)
        $role = $user->role ?? '';
        $rolesObjetivo = ['encargado_cocina']; // agrega 'encargado_cafeteria' si aplica
        if (!in_array($role, $rolesObjetivo, true)) {
            return $next($request);
        }

        // ✅ Debe tener unidad
        $unidadId = (int)($user->unidad_operativa_id ?? 0);
        if ($unidadId <= 0) {
            return $next($request);
        }

        // ✅ Rango semana pasada Lun–Vie
        // Semana pasada: lunes a viernes
        $inicio = Carbon::now()->startOfWeek(Carbon::MONDAY)->subWeek(); // lunes semana pasada
        $fin    = (clone $inicio)->addDays(4); // viernes semana pasada

        // ✅ Días esperados (Y-m-d)
        $diasEsperados = [];
        for ($i = 0; $i < 5; $i++) {
            $diasEsperados[] = (clone $inicio)->addDays($i)->toDateString();
        }

        // ✅ Días ya registrados (distinct fecha)
        $diasRegistrados = ComensalRegistro::query()
            ->where('unidad_operativa_id', $unidadId)
            ->whereDate('fecha', '>=', $inicio->toDateString())
            ->whereDate('fecha', '<=', $fin->toDateString())
            ->pluck('fecha')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->values()
            ->all();

        $faltantes = array_values(array_diff($diasEsperados, $diasRegistrados));

        // ✅ Si faltan días → forzar pantalla especial
        // Importante: permitir que pase si ya está en el flujo de comensales
        $rutaPermitida = $request->routeIs(
            'comensales.forzar',
            'comensales.forzar.guardar',
            'logout'
        );

        if (count($faltantes) > 0 && !$rutaPermitida) {
            return redirect()->route('dashboard.comensales.forzar');
        }

        return $next($request);
    }
}
