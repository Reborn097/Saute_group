<?php

namespace App\Imports;

use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\ProductoPresentacion;
use App\Models\PresentacionProveedor;
use App\Models\HistorialPrecio;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PreciosImport implements ToCollection, WithHeadingRow
{
    public $actualizados = [];
    public $errores = [];
    protected $proveedorId;

    /**
     * Recibimos el proveedor desde el controlador.
     */
    public function __construct($proveedorId)
    {
        $this->proveedorId = $proveedorId;
    }

    public function collection(Collection $rows)
    {
        // Validar que el proveedor exista
        $proveedor = Proveedor::find($this->proveedorId);

        if (!$proveedor) {
            $this->errores[] = "El proveedor seleccionado no existe.";
            return;
        }

        $proveedorNombre = $proveedor->nombre;

        foreach ($rows as $index => $row) {

            $row = $row->toArray();

            $productoNombre = $row['producto'] ?? null;
            $presentacion   = $row['presentacion'] ?? null;
            $valor          = $row['valor_medida'] ?? null;
            $unidad         = $row['unidad_medida'] ?? null;
            $precio         = $row['precio'] ?? null;

            $inicioRaw      = $row['fecha_vigencia_inicio'] ?? null;
            $finRaw         = $row['fecha_vigencia_final'] ?? null;

            $numeroFila = $index + 2;

            // Validaciones basicas
            if (!$productoNombre || !$presentacion || !$precio || !$inicioRaw) {
                $this->errores[] = "Fila $numeroFila: faltan datos obligatorios.";
                continue;
            }

            /**
             * Convertir fechas (Excel numerico o texto)
             */
            // Convertir inicio
            try {
                if (is_numeric($inicioRaw)) {
                    $inicio = Date::excelToDateTimeObject($inicioRaw)->format('Y-m-d');
                } else {
                    $inicio = date('Y-m-d', strtotime($inicioRaw));
                }
            } catch (\Exception $e) {
                $this->errores[] = "Fila $numeroFila: fecha de inicio invalida.";
                continue;
            }

            // Convertir fecha final
            $fin = null;
            if (!empty($finRaw)) {
                try {
                    if (is_numeric($finRaw)) {
                        $fin = Date::excelToDateTimeObject($finRaw)->format('Y-m-d');
                    } else {
                        $fin = date('Y-m-d', strtotime($finRaw));
                    }
                } catch (\Exception $e) {
                    $this->errores[] = "Fila $numeroFila: fecha final invalida.";
                    continue;
                }
            }

            /**
             * Buscar producto por nombre
             */
            $producto = Producto::where('nombre', $productoNombre)->first();

            if (!$producto) {
                $this->errores[] =
                    "Fila $numeroFila: producto '$productoNombre' no encontrado.";
                continue;
            }

            /**
             * Buscar presentacion del producto (contenido/unidad)
             */
            $presentacionQuery = ProductoPresentacion::where('producto_id', $producto->id)
                ->where('descripcion', $presentacion);

            if ($valor !== null && $unidad !== null) {
                $presentacionQuery
                    ->where('contenido', $valor)
                    ->where('unidad_contenido', $unidad);
            }

            $presentacion = $presentacionQuery->first();

            if (!$presentacion) {
                $this->errores[] =
                    "Fila $numeroFila: no se encontro presentacion '$presentacion' para '$productoNombre'.";
                continue;
            }

            /**
             * Buscar relacion presentacion-proveedor
             */
            $relacion = PresentacionProveedor::where('presentacion_id', $presentacion->id)
                ->where('proveedor_id', $proveedor->id)
                ->first();

            if (!$relacion) {
                $this->errores[] =
                    "Fila $numeroFila: no existe relacion presentacion-proveedor para '$productoNombre' con proveedor '$proveedorNombre'.";
                continue;
            }

            $ultimo = HistorialPrecio::where('presentacion_proveedor_id', $relacion->id)
                ->orderBy('vigencia_inicio', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();

            $cambio = (float) $relacion->precio_vigente !== (float) $precio
                || ($ultimo && (string) $ultimo->vigencia_inicio !== (string) $inicio)
                || ($ultimo && (string) $ultimo->vigencia_fin !== (string) $fin);

            if ($cambio) {
                HistorialPrecio::create([
                    'presentacion_proveedor_id' => $relacion->id,
                    'precio' => $precio,
                    'vigencia_inicio' => $inicio,
                    'vigencia_fin' => $fin,
                ]);
            }

            $relacion->update([
                'precio_vigente' => $precio,
                'estado' => 1,
            ]);

            $this->actualizados[] =
                "Fila $numeroFila: Actualizado $productoNombre ($valor $unidad) con proveedor $proveedorNombre.";
        }
    }
}
