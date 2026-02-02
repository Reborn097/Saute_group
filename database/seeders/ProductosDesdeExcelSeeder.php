<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\ProductoPresentacion;
use App\Models\PresentacionProveedor;
use App\Models\Proveedor;

class ProductosDesdeExcelSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/productos_import_raw.json');
        if (!file_exists($path)) {
            $this->command?->error("No existe el archivo: {$path}");
            return;
        }

        $rows = json_decode(file_get_contents($path), true);
        if (!is_array($rows)) {
            $this->command?->error('El JSON de entrada es invalido.');
            return;
        }

        $cacheCategorias = [];
        $cacheProveedores = [];
        $cacheProductos = [];
        $cachePresentaciones = [];

        $stats = [
            'rows' => 0,
            'productos' => 0,
            'presentaciones' => 0,
            'proveedores' => 0,
            'relaciones' => 0,
            'omitidas' => 0,
            'productos_actualizados' => 0,
        ];

        foreach ($rows as $row) {
            $stats['rows']++;

            $rowNorm = $this->normalizeRowKeys($row);

            $nombre = $this->cleanText($rowNorm['nombre'] ?? null);
            $categoriaNombre = $this->cleanText($rowNorm['categoria'] ?? null) ?? 'Sin categoria';
            $tipo = $this->cleanText($rowNorm['tipo'] ?? null);
            $presentacionRaw = $this->cleanText($rowNorm['presentacion'] ?? null);
            $marca = $this->cleanText($rowNorm['marca'] ?? null);
            $contenidoRaw = $this->cleanText($rowNorm['contenido'] ?? null);
            $proveedorNombre = $this->cleanText($rowNorm['proveedor'] ?? null);
            $precioRaw = $rowNorm['precio'] ?? null;
            $estadoRaw = $this->cleanText($rowNorm['estado'] ?? null);

            if (!$nombre) {
                $stats['omitidas']++;
                continue;
            }

            // Categoria
            $catKey = $this->normKey($categoriaNombre);
            if (!isset($cacheCategorias[$catKey])) {
                $categoria = Categoria::firstOrCreate(
                    ['nombre' => $categoriaNombre],
                    ['estado' => 'Activo']
                );
                $cacheCategorias[$catKey] = $categoria->id;
            }
            $categoriaId = $cacheCategorias[$catKey];

            // Proveedor
            $proveedorId = null;
            if ($proveedorNombre) {
                $provKey = $this->normKey($proveedorNombre);
                if (!isset($cacheProveedores[$provKey])) {
                    $proveedor = Proveedor::firstOrCreate(['nombre' => $proveedorNombre]);
                    $cacheProveedores[$provKey] = $proveedor->id;
                    $stats['proveedores']++;
                }
                $proveedorId = $cacheProveedores[$provKey];
            }

            // Producto (busca por nombre + marca, y actualiza categoria si cambio)
            $prodKey = $this->normKey($nombre) . '|' . $this->normKey($marca ?? '');
            if (!isset($cacheProductos[$prodKey])) {
                $productoQuery = Producto::where('nombre', $nombre);
                if ($marca) {
                    $productoQuery->where('marca', $marca);
                } else {
                    $productoQuery->whereNull('marca');
                }
                $producto = $productoQuery->first();

                if (!$producto) {
                    $producto = Producto::create([
                        'nombre' => $nombre,
                        'categoria_id' => $categoriaId,
                        'marca' => $marca,
                        'estado' => $this->estadoToBool($estadoRaw),
                    ]);
                    $stats['productos']++;
                } else {
                    $needsUpdate = false;
                    if ((int) $producto->categoria_id !== (int) $categoriaId) {
                        $producto->categoria_id = $categoriaId;
                        $needsUpdate = true;
                    }
                    $nuevoEstado = $this->estadoToBool($estadoRaw);
                    if ($producto->estado !== $nuevoEstado) {
                        $producto->estado = $nuevoEstado;
                        $needsUpdate = true;
                    }
                    if ($marca && $producto->marca !== $marca) {
                        $producto->marca = $marca;
                        $needsUpdate = true;
                    }
                    if ($needsUpdate) {
                        $producto->save();
                        $stats['productos_actualizados']++;
                    }
                }

                $cacheProductos[$prodKey] = $producto->id;
            }
            $productoId = $cacheProductos[$prodKey];

            // Presentacion
            $presentacionDesc = $presentacionRaw ?: 'Default';
            if ($contenidoRaw && $contenidoRaw !== '0') {
                if (stripos($presentacionDesc, $contenidoRaw) === false) {
                    $presentacionDesc = trim($presentacionDesc . ' - ' . $contenidoRaw);
                }
            }

            [$contenidoNum, $unidadContenido] = $this->parseContenido($contenidoRaw);
            $unidadBase = $this->normalizeUnidad($tipo);
            if (!$unidadBase && $unidadContenido) {
                $unidadBase = $unidadContenido;
            }

            $presKey = $productoId . '|' . $this->normKey($presentacionDesc);
            if (!isset($cachePresentaciones[$presKey])) {
                $presentacion = ProductoPresentacion::firstOrCreate(
                    [
                        'producto_id' => $productoId,
                        'descripcion' => $presentacionDesc,
                    ],
                    [
                        'contenido' => $contenidoNum,
                        'unidad_contenido' => $unidadContenido,
                        'unidad_base' => $unidadBase,
                        'estado' => $this->estadoToBool($estadoRaw),
                    ]
                );

                // Si ya existia, intenta completar datos faltantes
                $needsUpdate = false;
                if ($contenidoNum !== null && $presentacion->contenido === null) {
                    $presentacion->contenido = $contenidoNum;
                    $needsUpdate = true;
                }
                if ($unidadContenido && !$presentacion->unidad_contenido) {
                    $presentacion->unidad_contenido = $unidadContenido;
                    $needsUpdate = true;
                }
                if ($unidadBase && !$presentacion->unidad_base) {
                    $presentacion->unidad_base = $unidadBase;
                    $needsUpdate = true;
                }
                if ($needsUpdate) {
                    $presentacion->save();
                }

                $cachePresentaciones[$presKey] = $presentacion->id;
                $stats['presentaciones']++;
            }
            $presentacionId = $cachePresentaciones[$presKey];

            // Relacion presentacion-proveedor
            $precio = $this->toNumber($precioRaw);
            if ($proveedorId && $precio !== null) {
                PresentacionProveedor::updateOrCreate(
                    [
                        'presentacion_id' => $presentacionId,
                        'proveedor_id' => $proveedorId,
                    ],
                    [
                        'precio_vigente' => $precio,
                        'estado' => $this->estadoToBool($estadoRaw),
                    ]
                );
                $stats['relaciones']++;
            } else {
                $stats['omitidas']++;
            }
        }

        $this->command?->info('Seeder terminado.');
        $this->command?->line('Filas: ' . $stats['rows']);
        $this->command?->line('Productos: ' . $stats['productos']);
        $this->command?->line('Productos actualizados: ' . $stats['productos_actualizados']);
        $this->command?->line('Presentaciones: ' . $stats['presentaciones']);
        $this->command?->line('Proveedores: ' . $stats['proveedores']);
        $this->command?->line('Relaciones: ' . $stats['relaciones']);
        $this->command?->line('Omitidas: ' . $stats['omitidas']);
    }

    private function normalizeRowKeys(array $row): array
    {
        $out = [];
        foreach ($row as $key => $value) {
            $k = $this->normalizeHeader((string) $key);
            if ($k !== '') {
                $out[$k] = $value;
            }
        }
        return $out;
    }

    private function normalizeHeader(string $key): string
    {
        $k = trim(mb_strtolower($key));
        // Fix common mojibake for accents (UTF-8 seen as Latin-1)
        $k = str_replace(
            ['Ã¡','Ã©','Ã­','Ã³','Ãº','Ã±','Ã¼','Ã‰','Ã“','Ãš','Ã','Ã','Ã‘'],
            ['a','e','i','o','u','n','u','e','o','u','a','i','n'],
            $k
        );
        $k = str_replace(['á','é','í','ó','ú','ü','ñ'], ['a','e','i','o','u','u','n'], $k);
        $k = preg_replace('/\s+/', ' ', $k);
        $k = preg_replace('/[^a-z]/', '', $k);

        if (str_contains($k, 'nombre')) return 'nombre';
        if (str_contains($k, 'categoria')) return 'categoria';
        if (str_contains($k, 'tipo')) return 'tipo';
        if (str_contains($k, 'presentacion')) return 'presentacion';
        if (str_contains($k, 'marca')) return 'marca';
        if (str_contains($k, 'contenido')) return 'contenido';
        if (str_contains($k, 'proveedor')) return 'proveedor';
        if (str_contains($k, 'precio')) return 'precio';
        if (str_contains($k, 'estado')) return 'estado';

        return '';
    }

    private function cleanText($value): ?string
    {
        if ($value === null) return null;
        $text = trim((string) $value);
        $text = preg_replace('/\s+/', ' ', $text);
        if ($text === '' || $text === '0' || $text === '.') return null;
        return $text;
    }

    private function normKey(string $value): string
    {
        $v = mb_strtolower(trim($value));
        $v = preg_replace('/\s+/', ' ', $v);
        return $v;
    }

    private function estadoToBool(?string $estado): int
    {
        if (!$estado) return 1;
        return stripos($estado, 'activo') !== false ? 1 : 0;
    }

    private function toNumber($value): ?float
    {
        if ($value === null || $value === '') return null;
        $v = str_replace(',', '.', (string) $value);
        if (!is_numeric($v)) return null;
        return round((float) $v, 4);
    }

    private function normalizeUnidad(?string $raw): ?string
    {
        if (!$raw) return null;
        $u = mb_strtolower($raw);
        $u = str_replace(['.', ','], '', $u);
        $u = trim($u);

        $map = [
            'l' => 'lt',
            'lt' => 'lt',
            'lts' => 'lt',
            'litro' => 'lt',
            'litros' => 'lt',
            'ml' => 'ml',
            'mililitro' => 'ml',
            'mililitros' => 'ml',
            'kg' => 'kg',
            'kilo' => 'kg',
            'kilos' => 'kg',
            'kilogramo' => 'kg',
            'kilogramos' => 'kg',
            'gr' => 'gr',
            'g' => 'gr',
            'gramo' => 'gr',
            'gramos' => 'gr',
            'pieza' => 'pieza',
            'pza' => 'pieza',
            'pzas' => 'pieza',
            'piezas' => 'pieza',
            'paquete' => 'paquete',
            'paqt' => 'paquete',
            'pqte' => 'paquete',
            'paq' => 'paquete',
            'caja' => 'caja',
            'bote' => 'bote',
            'botella' => 'botella',
            'bolsa' => 'bolsa',
            'charola' => 'charola',
            'lata' => 'lata',
            'garrafa' => 'garrafa',
            'bidon' => 'bidon',
            'envase' => 'envase',
            'frasco' => 'frasco',
            'rollo' => 'rollo',
        ];

        return $map[$u] ?? null;
    }

    private function parseContenido(?string $raw): array
    {
        if (!$raw) return [null, null];
        $text = mb_strtolower($raw);
        $text = str_replace(',', '.', $text);

        preg_match_all('/(\d+(?:\.\d+)?)\s*(kg|kilo|kilogramo|kilogramos|gr|g|gramo|gramos|ml|lt|l|litro|litros)/i', $text, $matches, PREG_SET_ORDER);
        if (!$matches) return [null, null];

        $last = end($matches);
        $num = $this->toNumber($last[1]);
        $unit = $this->normalizeUnidad($last[2]);
        if ($num === null || !$unit) return [null, null];

        return [$num, $unit];
    }
}
