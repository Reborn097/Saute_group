@extends('layouts.dashboard')

@section('titulo', 'Registrar datos de tarjeta')

@section('contenido')
<div class="contenedor">

    <p class="nota">
        Estos datos se guardan temporalmente y se registrarán junto con el proveedor cuando presiones <b>Guardar Proveedor</b>.
    </p>

    @if (session('success'))
        <div style="background:#d4edda; color:#155724; padding:10px; border-radius:8px; margin-bottom:15px;">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="background:#f8d7da; color:#721c24; padding:10px; border-radius:8px; margin-bottom:15px;">
            <b>Corrige esto:</b>
            <ul style="margin: 8px 0 0 18px;">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('dashboard.proveedores.tarjetas.guardar') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label><b>Tipo</b></label>
            <select name="tipo" class="form-control" required>
                <option value="empresa">Empresa</option>
                <option value="contacto">Contacto</option>
            </select>
        </div>

        <div class="mb-3">
            <label><b>Alias</b></label>
            <input type="text" name="alias" class="form-control" maxlength="80"
                   placeholder="Ej. Principal / Juan Pérez">
        </div>

        <div class="mb-3">
            <label><b>Banco</b></label>
            <input type="text" name="banco" class="form-control" maxlength="80"
                   placeholder="Ej. BBVA">
        </div>

        <div class="mb-3">
            <label><b>Titular</b></label>
            <input type="text" name="titular" class="form-control" maxlength="120"
                   placeholder="Ej. Distribuidora López SA de CV">
        </div>

        <div class="mb-3">
            <label><b>CLABE (18 dígitos)</b></label>
            <input type="text" name="clabe" id="clabe" class="form-control" maxlength="18"
                   placeholder="Ej. 012345678901234567">
        </div>

        <div class="mb-3">
            <label><b>Número de cuenta</b></label>
            <input type="text" name="cuenta" id="cuenta" class="form-control" maxlength="20"
                   placeholder="Ej. 1234567890">
        </div>

        <div class="mb-3">
            <label><b>Tarjeta (opcional)</b></label>
            <input type="text" name="tarjeta" id="tarjeta" class="form-control" maxlength="20"
                   placeholder="Número completo">
        </div>

        <div class="footer-botones">
            <a href="{{ route('dashboard.proveedores.crear') }}" class="link-regresar">
                Regresar
            </a>

            <button type="submit" class="btn-agregar">
                Guardar datos
            </button>
        </div>
    </form>

</div>

<style>
/* ✅ fuente igual en TODO */
.contenedor, .contenedor *{
    font-family:'Poppins', sans-serif;
}

/* CONTENEDOR */
.contenedor{
    background-color:#fceede;
    padding:25px;
    border-radius:12px;
    max-width:800px;
    width:95%;
    margin:auto;
}

.nota{
    margin-bottom:20px;
    color:#555;
    line-height:1.35;
}

/* INPUTS/SELECTS consistentes */
.form-control{
    width:100%;
    height:42px;
    padding:0 12px;
    border-radius:8px;
    border:1px solid #ccc;
    margin-top:6px;
    margin-bottom:15px;
    background:#fff;
    box-sizing:border-box;
    font-size:14px;
}

.form-control:focus{
    outline:none;
    border-color:#b22b27;
    box-shadow:0 0 0 2px rgba(178,43,39,0.12);
}

/* BOTÓN */
.btn-agregar{
    background-color:#0e2238;
    color:#fff;
    padding:10px 16px;
    border-radius:8px;
    border:none;
    font-weight:800;
    cursor:pointer;
    white-space:nowrap;
}
.btn-agregar:hover{ background-color:#13314f; }

/* FOOTER BOTONES */
.footer-botones{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    margin-top:20px;
    flex-wrap:wrap;
}

.link-regresar{
    color:#b22b27;
    font-weight:800;
    text-decoration:none;
    white-space:nowrap;
}
.link-regresar:hover{ text-decoration:underline; }

/* ✅ RESPONSIVO */
@media(max-width:720px){
    .contenedor{
        width:100%;
        padding:18px 14px;
    }

    .footer-botones{
        flex-direction:column;
        align-items:stretch;
    }

    .btn-agregar{
        width:100%;
        text-align:center;
    }

    .link-regresar{
        width:100%;
        text-align:center;
        padding:8px 0;
        border-radius:8px;
        background:#fff;
        border:1px solid rgba(178,43,39,0.2);
    }
}
</style>

<script>
function onlyDigits(el, max) {
    if(!el) return;
    el.addEventListener("input", function () {
        this.value = this.value.replace(/\D/g, "").substring(0, max);
    });
}
onlyDigits(document.getElementById("clabe"), 18);
onlyDigits(document.getElementById("cuenta"), 20);
onlyDigits(document.getElementById("tarjeta"), 20);
</script>
@endsection
