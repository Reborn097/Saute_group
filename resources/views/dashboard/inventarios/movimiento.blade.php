@extends('layouts.dashboard')
@section('titulo','Movimiento de Inventario')

@section('contenido')
<div class="contenedor">
    <div class="acciones-superior">
        <button class="btn-menu"
            onclick="window.location.href='{{ route('dashboard.admin') }}'">
            Menú principal
        </button>

        <button class="btn-regresar"
            onclick="window.location.href='{{ url()->previous() }}'">
            Regresar
        </button>
    </div>

    <h2>Registrar movimiento</h2>

    <form method="POST" action="{{ route('inventarios.movimiento.store') }}">
        @csrf

        <div class="grid">

            <div>
                <label>Almacén</label>
                <select name="almacen_id" required>
                    @foreach($almacenes as $a)
                        <option value="{{ $a->id }}">{{ $a->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Producto</label>
                <select name="producto_id" required>
                    @foreach($productos as $p)
                        <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Tipo</label>
                <select name="tipo_movimiento" required>
                    <option value="entrada">Entrada</option>
                    <option value="salida">Salida</option>
                    <option value="ajuste">Ajuste (cantidad final)</option>
                </select>
            </div>

            <div>
                <label>Cantidad</label>
                <input type="number" name="cantidad" min="0.01" step="0.01" required>
            </div>

            <div>
                <label>Lote (opcional)</label>
                <input type="text" name="lote" maxlength="120">
            </div>

            <div>
                <label>Caducidad (opcional)</label>
                <input type="date" name="caducidad">
            </div>

            <div style="grid-column:1/-1;">
                <label>Motivo (opcional)</label>
                <input type="text" name="motivo" maxlength="255">
            </div>

        </div>

        <div style="margin-top:15px; display:flex; gap:10px;">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn-cancelar" href="{{ route('inventarios.index') }}">Cancelar</a>
        </div>
    </form>
</div>

<style>
.contenedor{
    background:#fceede;
    padding:25px 35px;
    border-radius:12px;
    max-width:900px;
    margin:auto;
}
.grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:15px;
}
label{ font-weight:700; display:block; margin-bottom:6px; }
input, select{
    width:100%;
    padding:8px;
    border-radius:8px;
    border:1px solid #ccc;
}
.btn{
    background:#b22b27;
    color:white;
    border:none;
    padding:8px 13px;
    border-radius:8px;
    cursor:pointer;
    text-decoration:none;
}
.btn:hover{ background:#941c1c; }
.btn-cancelar{
    background:#777;
    color:white;
    padding:8px 13px;
    border-radius:8px;
    text-decoration:none;
}

    .btn-regresar{
        background:#777;
        color:white;
        border:none;
        padding:8px 14px;
        border-radius:8px;
        cursor:pointer;
    }

</style>
@endsection
