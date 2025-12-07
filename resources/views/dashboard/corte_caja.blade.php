@extends('layouts.dashboard')

@section('titulo', 'Corte de Caja')

@section('contenido')

<div class="contenedor">

    <button class="btn-menu" onclick="window.location.href='{{ route('dashboard.admin') }}'">
        Menú principal
    </button>

    {{-- ====================== FILTROS SUPERIORES ====================== --}}
    <div class="filtros">
        
        <div class="grupo">
            <label>Mes:</label>
            <select name="mes" id="mes">
                @foreach(range(1,12) as $m)
                    <option value="{{ $m }}" {{ $m == $mes ? 'selected' : '' }}>
                        {{ ucfirst(\Carbon\Carbon::create()->month($m)->locale('es')->translatedFormat('F')) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grupo">
            <label>Año:</label>
            <select name="anio" id="anio">
                @foreach(range(date('Y')-3, date('Y')+1) as $a)
                    <option value="{{ $a }}" {{ $a == $anio ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
        </div>

        <div class="grupo">
            <label>Local:</label>
            <select name="local" id="local">
                @foreach($unidades as $u)
                    <option value="{{ $u->id }}">{{ $u->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div class="grupo">
            <label>&nbsp;</label>
            <button type="button" class="btn-buscar" onclick="cargarTabla()">Buscar</button>
        </div>

    </div>



    {{-- ====================== TABLA ====================== --}}
    <div id="tablaCorte">
        @include('dashboard.partials.corte_caja_tabla')
    </div>

</div>



{{-- ====================== SCRIPTS ====================== --}}
<script>
function cargarTabla() {
    let mes = document.getElementById("mes").value;
    let anio = document.getElementById("anio").value;
    let local = document.getElementById("local").value;

    fetch(`/dashboard/corte-caja/datos/${anio}/${mes}/${local}`)
        .then(resp => resp.text())
        .then(html => {
            document.getElementById("tablaCorte").innerHTML = html;
        });
}

function guardarDia(fecha) {
    let efectivo = document.getElementById("efectivo_" + fecha).value;
    let credito  = document.getElementById("credito_" + fecha).value;
    let local    = document.getElementById("local").value;

    fetch("/dashboard/corte-caja/guardar", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify({
            fecha: fecha,
            cantidad_efectivo: efectivo,
            cantidad_credito: credito,
            unidad_id: local
        })
    })
    .then(r => r.json())
    .then(data => {
        cargarTabla();
    });
}
</script>



{{-- ====================== ESTILOS ====================== --}}
<style>
/* ----------------------- CONTENEDOR GENERAL ------------------------ */
.contenedor {
    background: #fceede;
    padding: 35px;
    border-radius: 20px;
    width: 95%;              /* MÁS ANCHO */
    margin: auto;            /* Centrado */
    max-width: 1700px;       /* Permite mayor expansión */
}



/* ----------------------- FILTROS ------------------------ */
.filtros {
    display: flex;
    justify-content: center;   /* ⬅ CENTRA TODO HORIZONTALMENTE */
    align-items: flex-end;
    gap: 40px;                 /* Espacio entre elementos */
    margin-bottom: 30px;
    flex-wrap: wrap;
}


.grupo {
    display: flex;
    flex-direction: column;
}

.filtros label {
    font-weight: 600;
    margin-bottom: 6px;
}

.filtros select {
    padding: 10px 15px;      /* Más grande y cómodo */
    border-radius: 8px;
    border: 1px solid #ccc;
    width: 200px;            /* Más ancho */
    font-size: 15px;
}

.btn-buscar {
    background-color: #b22b27;
    color: white;
    padding: 12px 25px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    font-size: 15px;
}

.btn-buscar:hover {
    background-color: #941c1c;
}



/* ----------------------- TABLA ------------------------ */
table {
    width: 100%;
    background: white;
    border-radius: 12px;
    border-collapse: collapse;
    overflow: hidden;
}

th {
    background: #b22b27;
    color: white;
    padding: 12px;
    text-align: center;
    font-weight: 600;
    font-size: 15px;
}

td {
    padding: 10px;
    text-align: center;
    border-bottom: 1px solid #eee;
    font-size: 14px;
}

/* Más espacio en la división por semanas */
.fila-semana {
    background: #fde6d9;
    font-weight: bold;
}



/* ----------------------- INPUTS ------------------------ */
input[type="number"] {
    width: 95px;             /* MÁS ANCHO */
    padding: 7px;
    border-radius: 7px;
    border: 1px solid #ccc;
    font-size: 14px;
}



/* ----------------------- BOTONES ------------------------ */
.btn-guardar {
    background: #b22b27;
    color: white;
    font-size: 13px;
    border: none;
    padding: 7px 12px;
    border-radius: 8px;
    cursor: pointer;
}

.btn-guardar:hover {
    background: #941c1c;
}


.btn-registrar {
    background-color: #b22b27; /* mismo rojo */
    color: white;
    border: none;
    padding: 6px 14px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 12px;
    transition: background 0.2s;
}

.btn-registrar:hover {
    background-color: #921d1d; /* rojo más oscuro */
}



</style>


@endsection
