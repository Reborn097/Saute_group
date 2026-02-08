@extends('layouts.dashboard')

@section('titulo', 'Actualizar Precios por Excel')

@section('contenido')
<div class="contenedor-form">
    <button type="button" class="btn" onclick="window.location.href='{{ route('dashboard.admin') }}'">Menú principal</button>
    <h2 class="titulo-seccion">Actualizar precios Via Excel</h2>

    {{-- Mensajes de validación --}}
    @if ($errors->any())
        <div class="alert alert-danger" style="margin-bottom: 15px;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Mensaje de éxito --}}
    @if (session('success'))
        <div class="alert alert-success" style="margin-bottom: 15px;">
            {{ session('success') }}
        </div>
    @endif

    <form
        action="{{ auth()->user()->role === 'proveedor' ? route('proveedor.precios.importar_excel') : route('precios.importar_excel') }}"
        method="POST"
        enctype="multipart/form-data">
        @csrf

        @if(auth()->user()->role === 'admin')
            <div class="form-grupo">
                <label for="proveedor_id">Proveedor que actualiza</label>
                <select name="proveedor_id" id="proveedor_id" required>
                    <option value="">-- Seleccione proveedor --</option>
                    @foreach($proveedores as $prov)
                        <option value="{{ $prov->id }}" {{ (string) old('proveedor_id') === (string) $prov->id ? 'selected' : '' }}>{{ $prov->nombre }}</option>
                    @endforeach
                </select>
            </div>
        @else
            <p style="margin-bottom:15px; opacity:.85;">
                Se aplicarán cambios únicamente a <b>tu catálogo</b>.
            </p>
        @endif

        <div class="form-grupo">
            <label for="archivo">Seleccionar archivo Excel (.xlsx)</label>
            <input type="file" name="archivo" id="archivo" accept=".xlsx" required>
            <small class="hint">Solo se aceptan archivos .xlsx</small>
        </div>

        <div class="botones">
            <a href="{{ route('dashboard.precios') }}" class="btn-cancelar">Cancelar</a>
            <button type="submit" class="btn-guardar">Procesar archivo</button>
        </div>
    </form>

    {{-- Reporte de filas actualizadas --}}
    @if(session('actualizados'))
        <div class="alert alert-success" style="margin-top: 25px;">
            <h4>Precios actualizados:</h4>
            <ul>
                @foreach(session('actualizados') as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Reporte de errores --}}
    @if(session('errores'))
        <div class="alert alert-danger" style="margin-top: 25px;">
            <h4>Errores encontrados:</h4>
            <ul>
                @foreach(session('errores') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <hr style="margin: 30px 0;">

    <div class="acciones-instrucciones">
        <button
            type="button"
            onclick="toggleInstrucciones()"
            class="btn-guardar btn-instrucciones">
            📘 Instrucciones de llenado
        </button>

        <a
            href="{{ auth()->user()->role === 'proveedor' ? route('proveedor.precios.plantilla_excel') : '#' }}"
            id="btn-descargar-plantilla"
            class="btn-cancelar btn-plantilla {{ auth()->user()->role === 'admin' && !old('proveedor_id') ? 'btn-disabled' : '' }}">
            📥 Descargar plantilla
        </a>
    </div>

    {{-- CONTENEDOR OCULTO PARA INSTRUCCIONES --}}
    <div id="instrucciones" style="display:none; margin-top:20px;">

        <h3 class="titulo-instrucciones">
            Instrucciones para llenar el Excel
        </h3>

        <p style="margin-bottom: 15px; font-size: 14px;">
            El archivo debe contener exactamente las siguientes columnas.
            <strong>No agregar, quitar o renombrar columnas.</strong>
            Las fechas pueden estar en formato fecha o número serial de Excel.
        </p>

        {{-- ✅ WRAPPER RESPONSIVE PARA LA TABLA --}}
        <div class="tabla-ejemplo-wrap">
            <table class="tabla-ejemplo">
                <thead>
                    <tr>
                        <th>producto</th>
                        <th>presentacion</th>
                        <th>valor_medida</th>
                        <th>unidad_medida</th>
                        <th>precio</th>
                        <th>fecha_vigencia_inicio</th>
                        <th>fecha_vigencia_final</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Coca-Cola</td>
                        <td>Lata 600 ml</td>
                        <td>600</td>
                        <td>ml</td>
                        <td>15.50</td>
                        <td>2025-01-05</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Spaghetti</td>
                        <td>Paquete 500 g</td>
                        <td>500</td>
                        <td>g</td>
                        <td>19.00</td>
                        <td>2025-01-10</td>
                        <td>2025-12-31</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="notas">
            <strong>Notas importantes:</strong><br>
            • <strong>producto</strong> debe coincidir exactamente con el nombre registrado en el sistema.<br>
            • <strong>presentacion</strong> debe coincidir exactamente con la descripción de la presentación registrada.<br>
            • <strong>valor_medida</strong> es solo el número (ej. 600, 500, 1).<br>
            • <strong>unidad_medida</strong> debe coincidir (ml, g, kg, pieza, etc.)<br>
            • <strong>precio</strong> es el nuevo precio a aplicar.<br>
            • <strong>fecha_vigencia_inicio</strong> es obligatoria.<br>
            • <strong>fecha_vigencia_final</strong> es opcional.<br>
            • Las fechas pueden venir como formato Excel (número serial) o como texto.<br>
        </p>
    </div>

</div>

<style>
/* mantiene el mismo diseño que los demás formularios */
.contenedor-form {
    max-width: 1000px;
    width: min(1000px, 100%);
    margin: 40px auto;
    background-color: #fbe9d7;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
    font-family: 'Poppins', sans-serif; /* ✅ misma fuente */
}

.titulo-seccion {
    text-align: center;
    margin-bottom: 25px;
    font-size: 1.8rem;
    color: #b22b27;
    font-weight: 700;
}

/* labels */
label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.form-grupo { margin-bottom: 20px; }

/* ✅ UNIFICAR INPUTS + SELECT */
input[type="text"],
input[type="number"],
input[type="date"],
select,
input[type="file"]{
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 1rem;
    background-color: #fff;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
    font-family: 'Poppins', sans-serif; /* ✅ misma fuente */
}

select{
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image:
        linear-gradient(45deg, transparent 50%, #b22b27 50%),
        linear-gradient(135deg, #b22b27 50%, transparent 50%);
    background-position:
        calc(100% - 18px) calc(50% - 3px),
        calc(100% - 12px) calc(50% - 3px);
    background-size: 6px 6px, 6px 6px;
    background-repeat: no-repeat;
    padding-right: 38px;
}

input[type="file"]{
    padding: 9px 12px;
    cursor: pointer;
}
input[type="file"]::file-selector-button{
    background: #b22b27;
    color: #fff;
    border: none;
    padding: 8px 12px;
    border-radius: 8px;
    margin-right: 12px;
    cursor: pointer;
    font-weight: 700;
    font-family: 'Poppins', sans-serif;
}
input[type="file"]::file-selector-button:hover{
    background: #8c1f1b;
}

/* ✅ FOCUS BONITO */
select:focus,
input:focus{
    border-color: #b22b27;
    box-shadow: 0 0 0 3px rgba(178,43,39,.18);
}

.hint{
    display:block;
    margin-top:8px;
    font-size: 13px;
    color:#6b6b6b;
}

/* botones del form */
.botones {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-top: 25px;
    flex-wrap: wrap; /* ✅ */
}

.btn-cancelar {
    background-color: #aaa;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    transition: background-color 0.3s;
    font-family: 'Poppins', sans-serif;
    font-weight: 700;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    white-space:nowrap;
}
.btn-cancelar:hover { background-color: #888; }

.btn-guardar {
    background-color: #b22b27;
    color: white;
    padding: 10px 25px;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 0.3s;
    font-family: 'Poppins', sans-serif;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    white-space:nowrap;
}
.btn-guardar:hover { background-color: #8c1f1b; }

/* ✅ acciones instrucciones responsive */
.acciones-instrucciones{
    display:flex;
    gap:10px;
    align-items:center;
    flex-wrap:wrap;
}

.btn-instrucciones,
.btn-plantilla{
    flex:0 0 auto;
}

.btn-disabled{
    pointer-events:none;
    opacity:.55;
}

/* ✅ tabla ejemplo responsive */
.tabla-ejemplo-wrap{
    overflow-x:auto;               /* ✅ evita que reviente */
    border-radius:10px;
    border:1px solid rgba(0,0,0,.08);
    background:#fff;
}

.tabla-ejemplo{
    width:100%;
    min-width: 760px;              /* ✅ fuerza scroll si pantalla chica */
    border-collapse: collapse;
}

.tabla-ejemplo th{
    padding: 10px;
    border:1px solid #ccc;
    background:#fbe9d7;
    color:#b22b27;
    text-align:left;
    font-weight:800;
    white-space:nowrap;
}
.tabla-ejemplo td{
    padding: 10px;
    border:1px solid #ccc;
    white-space:nowrap;
}

.titulo-instrucciones{
    color:#b22b27;
    font-weight:800;
    margin-bottom:15px;
}

.notas{
    font-size: 13px;
    color:#555;
}

/* ✅ responsive */
@media (max-width: 720px){
    .contenedor-form{
        padding: 18px;
        margin: 18px auto;
    }

    .titulo-seccion{
        font-size: 1.35rem;
        margin-bottom: 18px;
    }

    .botones{
        justify-content: stretch;
    }

    .botones .btn-cancelar,
    .botones .btn-guardar{
        width:100%;
    }

    .acciones-instrucciones{
        flex-direction:column;
        align-items:stretch;
    }

    .acciones-instrucciones .btn-guardar,
    .acciones-instrucciones .btn-cancelar{
        width:100%;
        margin-left:0 !important;
    }
}
</style>

<script>
function toggleInstrucciones() {
    const div = document.getElementById('instrucciones');
    div.style.display = div.style.display === 'none' ? 'block' : 'none';
}

@if(auth()->user()->role === 'admin')
document.addEventListener('DOMContentLoaded', function () {
    const proveedorSelect = document.getElementById('proveedor_id');
    const btnPlantilla = document.getElementById('btn-descargar-plantilla');
    const baseUrl = @json(route('precios.plantilla_excel'));

    if (!proveedorSelect || !btnPlantilla) return;

    const actualizarLinkPlantilla = () => {
        const proveedorId = (proveedorSelect.value || '').trim();

        if (proveedorId !== '') {
            btnPlantilla.href = `${baseUrl}?proveedor_id=${encodeURIComponent(proveedorId)}`;
            btnPlantilla.classList.remove('btn-disabled');
            btnPlantilla.removeAttribute('aria-disabled');
            return;
        }

        btnPlantilla.href = '#';
        btnPlantilla.classList.add('btn-disabled');
        btnPlantilla.setAttribute('aria-disabled', 'true');
    };

    proveedorSelect.addEventListener('change', actualizarLinkPlantilla);
    actualizarLinkPlantilla();
});
@endif
</script>

@endsection
