<?php

namespace App\Imports;

use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\ProductoProveedor;
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
            $valor          = $row['valor_medida'] ?? null;
            $unidad         = $row['unidad_medida'] ?? null;
            $precio         = $row['precio'] ?? null;

            $inicioRaw      = $row['fecha_vigencia_inicio'] ?? null;
            $finRaw         = $row['fecha_vigencia_final'] ?? null;

            $numeroFila = $index + 2;

            // Validaciones básicas
            if (!$productoNombre || !$valor || !$unidad || !$precio || !$inicioRaw) {
                $this->errores[] = "Fila $numeroFila: faltan datos obligatorios.";
                continue;
            }

            /**
             * Convertir fechas (Excel numérico o texto)
             */
            // Convertir inicio
            try {
                if (is_numeric($inicioRaw)) {
                    $inicio = Date::excelToDateTimeObject($inicioRaw)->format('Y-m-d');
                } else {
                    $inicio = date('Y-m-d', strtotime($inicioRaw));
                }
            } catch (\Exception $e) {
                $this->errores[] = "Fila $numeroFila: fecha de inicio inválida.";
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
                    $this->errores[] = "Fila $numeroFila: fecha final inválida.";
                    continue;
                }
            }

            /**
             * Buscar producto exacto
             */
            $producto = Producto::where('nombre', $productoNombre)
                ->where('valor_medida', $valor)
                ->where('unidad_medida', $unidad)
                ->first();

            if (!$producto) {
                $this->errores[] =
                    "Fila $numeroFila: producto '$productoNombre $valor $unidad' no encontrado.";
                continue;
            }

            /**
             * Buscar relación con proveedor
             */
            $relacion = ProductoProveedor::where('producto_id', $producto->id)
                ->where('proveedor_id', $proveedor->id)
                ->first();

            if (!$relacion) {
                $this->errores[] =
                    "Fila $numeroFila: NO existe relación producto-proveedor para '$productoNombre' con proveedor '$proveedorNombre'.";
                continue;
            }

            // 1. Guardar HISTORIAL ANTES de actualizar el precio
            \DB::table('historial_precio')->insert([
                'producto_proveedor_id' => $relacion->id,
                'precio'                => $relacion->precio, // EL PRECIO ANTERIOR DE VERDAD
                'fecha_vigencia_inicio' => $relacion->fecha_vigencia_inicio,
                'fecha_vigencia_final'  => $relacion->fecha_vigencia_final,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);

            // 2. Ahora sí actualizar al nuevo precio
            $relacion->update([
                'precio'               => $precio,
                'fecha_vigencia_inicio'=> $inicio,
                'fecha_vigencia_final' => $fin,
            ]);



            $this->actualizados[] =
                "Fila $numeroFila: Actualizado $productoNombre ($valor $unidad) con proveedor $proveedorNombre.";
        }
    }
}
