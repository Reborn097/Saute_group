@extends('layouts.dashboard')

@section('titulo', 'Agregar Categoría')

@section('contenido')
<div class="contenedor cont-form">
    <form action="{{ route('dashboard.categorias.guardar') }}" method="POST" class="form-categoria">
        @csrf

        <div class="form-grupo">
            <label for="nombre">Nombre de la categoría</label>
            <input type="text" id="nombre" name="nombre" placeholder="Ej. Lácteos" required>
        </div>

        <div class="form-grupo">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="3" placeholder="Describe brevemente la categoría"></textarea>
        </div>

        <div class="form-grupo">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
            </select>
        </div>

        <div class="form-botones">
            <button type="button" class="btn cancelar"
                onclick="window.location.href='{{ route('dashboard.productos') }}'">
                Cancelar
            </button>

            <button type="submit" class="btn">
                Guardar Categoría
            </button>
        </div>
    </form>
</div>

<style>
/* ✅ extra: clase para no pisar tu .contenedor global */
.cont-form{
    background-color:#fae7d0;
    padding:35px;
    border-radius:12px;
    box-shadow:0 4px 8px rgba(0,0,0,0.15);
    max-width:800px;
    margin:0 auto;
}

.form-categoria{
    display:flex;
    flex-direction:column;
    gap:20px;
}

.form-grupo{
    display:flex;
    flex-direction:column;
}

.form-grupo label{
    font-weight:600;
    margin-bottom:6px;
    color:#333;
}

/* ✅ ya NO ponemos font-family aquí, lo hereda del dashboard.css */
.form-categoria input,
.form-categoria textarea,
.form-categoria select{
    border:1px solid #ccc;
    border-radius:10px;
    padding:10px 14px;
    background:#fff;
}

/* focus */
.form-categoria input:focus,
.form-categoria textarea:focus,
.form-categoria select:focus{
    border-color:#b22b27;
    outline:none;
    box-shadow:0 0 4px rgba(178, 43, 39, 0.3);
}

.form-categoria textarea{ resize:none; }

.form-botones{
    display:flex;
    justify-content:flex-end;
    gap:12px;
    margin-top:10px;
}

.btn{
    background-color:#b22b27;
    color:#fff;
    border:none;
    padding:10px 18px;
    border-radius:10px;
    font-weight:600;
    cursor:pointer;
    transition:.2s;
}
.btn:hover{ background-color:#911f1d; }

.cancelar{
    background-color:#999;
}
.cancelar:hover{ background-color:#777; }

/* ✅ RESPONSIVE */
@media (max-width:720px){
    .cont-form{
        padding:16px;
    }
    .form-botones{
        flex-direction:column;
        align-items:stretch;
    }
    .form-botones .btn{
        width:100%;
    }
}
</style>
@endsection
