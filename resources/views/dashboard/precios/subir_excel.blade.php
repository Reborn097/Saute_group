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

    


    <form action="{{ route('precios.importar_excel') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-grupo">
            <label for="proveedor_id">Proveedor que actualiza</label>
            <select name="proveedor_id" id="proveedor_id" required>
                <option value="">-- Seleccione proveedor --</option>
                @foreach($proveedores as $prov)
                    <option value="{{ $prov->id }}">{{ $prov->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-grupo">
            <label for="archivo">Seleccionar archivo Excel (.xlsx)</label>
            <input type="file" name="archivo" id="archivo" required>
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

    <div>
        <button 
            type="button" 
            onclick="toggleInstrucciones()" 
            class="btn-guardar"
            style="margin-bottom: 15px;">
            📘 Instrucciones de llenado
        </button>

        <a 
            href="{{ asset('plantillas/plantilla_precios.xlsx') }}" 
            class="btn-cancelar" 
            style="margin-left: 10px;">
            📥 Descargar plantilla
        </a>
    </div>

    {{-- CONTENEDOR OCULTO PARA INSTRUCCIONES --}}
    <div id="instrucciones" style="display:none; margin-top:20px;">
        
        <h3 style="color:#b22b27; font-weight:700; margin-bottom:15px;">
            Instrucciones para llenar el Excel
        </h3>

        <p style="margin-bottom: 15px; font-size: 14px;">
            El archivo debe contener exactamente las siguientes columnas.  
            <strong>No agregar, quitar o renombrar columnas.</strong>
            Las fechas pueden estar en formato fecha o número serial de Excel.
        </p>

        {{-- EJEMPLO DE TABLA --}}
        <table style="
            width:100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: white;
        ">
            <thead>
                <tr style="background:#fbe9d7; color:#b22b27; text-align:left;">
                    <th style="padding: 10px; border:1px solid #ccc;">producto</th>
                    <th style="padding: 10px; border:1px solid #ccc;">valor_medida</th>
                    <th style="padding: 10px; border:1px solid #ccc;">unidad_medida</th>
                    <th style="padding: 10px; border:1px solid #ccc;">precio</th>
                    <th style="padding: 10px; border:1px solid #ccc;">fecha_vigencia_inicio</th>
                    <th style="padding: 10px; border:1px solid #ccc;">fecha_vigencia_final</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 10px; border:1px solid #ccc;">Coca-Cola</td>
                    <td style="padding: 10px; border:1px solid #ccc;">600</td>
                    <td style="padding: 10px; border:1px solid #ccc;">ml</td>
                    <td style="padding: 10px; border:1px solid #ccc;">15.50</td>
                    <td style="padding: 10px; border:1px solid #ccc;">2025-01-05</td>
                    <td style="padding: 10px; border:1px solid #ccc;"></td>
                </tr>
                <tr>
                    <td style="padding: 10px; border:1px solid #ccc;">Spaghetti</td>
                    <td style="padding: 10px; border:1px solid #ccc;">500</td>
                    <td style="padding: 10px; border:1px solid #ccc;">g</td>
                    <td style="padding: 10px; border:1px solid #ccc;">19.00</td>
                    <td style="padding: 10px; border:1px solid #ccc;">2025-01-10</td>
                    <td style="padding: 10px; border:1px solid #ccc;">2025-12-31</td>
                </tr>
            </tbody>
        </table>

        <p style="font-size: 13px; color:#555;">
            <strong>Notas importantes:</strong><br>
            • <strong>producto</strong> debe coincidir exactamente con el nombre registrado en el sistema.<br>
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
    max-width: 900px;
    margin: 40px auto;
    background-color: #fbe9d7;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
}
.titulo-seccion {
    text-align: center;
    margin-bottom: 25px;
    font-size: 1.8rem;
    color: #b22b27;
    font-weight: 700;
}
.form-grupo {
    margin-bottom: 20px;
}
label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}
input[type="text"],
input[type="number"],
input[type="date"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1rem;
    background-color: #fff;
}
.botones {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-top: 25px;
}
.btn-cancelar {
    background-color: #aaa;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    transition: background-color 0.3s;
}
.btn-cancelar:hover {
    background-color: #888;
}
.btn-guardar {
    background-color: #b22b27;
    color: white;
    padding: 10px 25px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s;
}
.btn-guardar:hover {
    background-color: #8c1f1b;
}
</style>

<script>
function toggleInstrucciones() {
    const div = document.getElementById('instrucciones');
    div.style.display = div.style.display === 'none' ? 'block' : 'none';
}
</script>

@endsection
