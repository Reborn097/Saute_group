@extends('layouts.dashboard')

@section('titulo', 'Agregar Proveedor')

@section('contenido')
<div class="contenedor">

    {{-- Mensajes --}}
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

    <form action="{{ route('dashboard.proveedores.guardar') }}" method="POST" id="formProveedor">
        @csrf

        @php
            // Esto viene de tu controller crear()
            $draft = $proveedorDraft ?? session('proveedor_draft', []);
            $tarjetas = $tarjetasDraft ?? session('proveedor_tarjetas_draft', []);
        @endphp

        {{-- Nombre --}}
        <div class="mb-3">
            <label><b>Nombre del proveedor</b></label>
            <input type="text" name="nombre" class="form-control"
                placeholder="Ej. Distribuidora López" required
                value="{{ old('nombre', $draft['nombre'] ?? '') }}">
        </div>

        {{-- Teléfono --}}
        <div class="mb-3">
            <label><b>Teléfono del proveedor</b></label>
            <input type="text" name="telefono" id="telefono"
                maxlength="13" class="form-control" placeholder="Ej. 2283654321"
                value="{{ old('telefono', $draft['telefono'] ?? '') }}">
        </div>

        {{-- Contacto --}}
        <div class="mb-3">
            <label><b>Nombre del contacto</b></label>
            <input type="text" name="nombre_contacto" class="form-control"
                placeholder="Ej. Juan Pérez"
                value="{{ old('nombre_contacto', $draft['nombre_contacto'] ?? '') }}">
        </div>

        {{-- Teléfono contacto --}}
        <div class="mb-3">
            <label><b>Teléfono del contacto</b></label>
            <input type="text" name="telefono_contacto" id="telefono_contacto"
                maxlength="13" class="form-control" placeholder="Ej. 2298745632"
                value="{{ old('telefono_contacto', $draft['telefono_contacto'] ?? '') }}">
        </div>

        {{-- CP --}}
        <div class="mb-3">
            <label><b>Código Postal</b></label>
            <input type="text" name="codigo_postal" maxlength="5"
                class="form-control" placeholder="Ej. 91017" required
                value="{{ old('codigo_postal', $draft['codigo_postal'] ?? '') }}">
        </div>

        {{-- Colonia --}}
        <div class="mb-3">
            <label><b>Colonia</b></label>
            <input type="text" name="colonia" class="form-control"
                placeholder="Ej. Centro" required
                value="{{ old('colonia', $draft['colonia'] ?? '') }}">
        </div>

        {{-- Calle --}}
        <div class="mb-3">
            <label><b>Calle</b></label>
            <input type="text" name="calle" class="form-control"
                placeholder="Ej. Calle Principal #25"
                value="{{ old('calle', $draft['calle'] ?? '') }}">
        </div>

        {{-- Num dirección --}}
        <div class="mb-3">
            <label><b>Número de Dirección</b></label>
            <input type="text" name="num_direccion" id="num_direccion"
                maxlength="5" class="form-control" placeholder="Ej. 10-B"
                value="{{ old('num_direccion', $draft['num_direccion'] ?? '') }}">
        </div>

        {{-- RFC --}}
        <div class="mb-3">
            <label><b>RFC</b></label>
            <input type="text" name="rfc" id="rfc" maxlength="13"
                class="form-control" placeholder="Ej. LOPE890123JKL"
                value="{{ old('rfc', $draft['rfc'] ?? '') }}">
        </div>

        {{-- ===========================
            TARJETAS (OPCIONAL)
        ============================ --}}
        <div class="tarjetas-box">
            <div class="tarjetas-header">
                <div class="tarjetas-head-left">
                    <b>Datos de la cuenta (opcional)</b>
                    <div class="tarjetas-sub">
                        Puedes registrar una o varias tarjetas antes de guardar el proveedor.
                    </div>
                </div>

                {{-- ESTE BOTÓN manda POST a tarjetaCrear y guarda el draft en sesión --}}
                <button type="submit"
                        class="btn-secundario"
                        formaction="{{ route('dashboard.proveedores.tarjetas.crear') }}"
                        formmethod="POST"
                        formnovalidate>
                    Registrar datos de tarjeta
                </button>
            </div>

            @if(!empty($tarjetas))
                <div class="tarjetas-list">
                    <div style="margin-bottom: 10px;">
                        <b>Tarjetas registradas:</b> {{ count($tarjetas) }}
                    </div>

                    @foreach($tarjetas as $i => $t)
                        <div class="tarjeta-item">
                            <div class="tarjeta-info">
                                <div class="tarjeta-title">
                                    {{ $t['alias'] ?? ('Tarjeta #' . ($i+1)) }}
                                    <span class="pill">{{ strtoupper($t['tipo'] ?? 'empresa') }}</span>
                                </div>
                                <div class="tarjeta-meta">
                                    Banco: {{ $t['banco'] ?? '—' }} |
                                    Titular: {{ $t['titular'] ?? '—' }} |
                                    CLABE: {{ $t['clabe'] ?? '—' }}
                                </div>
                            </div>

                            {{-- ✅ SIN FORM ANIDADO: botón que manda a eliminar --}}
                            <button type="submit"
                                    class="btn-eliminar"
                                    name="index"
                                    value="{{ $i }}"
                                    formaction="{{ route('dashboard.proveedores.tarjetas.eliminar') }}"
                                    formmethod="POST"
                                    formnovalidate>
                                Quitar
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="tarjetas-empty">
                    Aún no has registrado tarjetas.
                </div>
            @endif
        </div>

        {{-- BOTONES --}}
        <div class="footer-botones">
            <a href="{{ route('dashboard.proveedores') }}" class="link-cancelar">
                Cancelar
            </a>

            <button type="submit" class="btn-agregar">
                Guardar Proveedor
            </button>
        </div>

    </form>
</div>

<style>
/* ✅ fuerza Poppins aquí sin romper tu dashboard global */
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

/* INPUTS */
.form-control{
    width:100%;
    height:42px;                 /* ✅ consistente */
    padding:0 12px;
    border-radius:8px;
    border:1px solid #ccc;
    margin-top:6px;
    margin-bottom:15px;
    background:#fff;
    box-sizing:border-box;
    font-size:14px;
}

textarea.form-control{
    height:auto;
    padding:10px 12px;
}

.form-control:focus{
    outline:none;
    border-color:#b22b27;
    box-shadow:0 0 0 2px rgba(178,43,39,0.12);
}

/* BOTÓN GUARDAR */
.btn-agregar{
    background-color:#941c1c;
    color:#fff;
    padding:10px 16px;
    border-radius:8px;
    border:none;
    font-weight:800;
    cursor:pointer;
    white-space:nowrap;
}
.btn-agregar:hover{ background-color:#b82929; }

/* TARJETAS */
.tarjetas-box{
    background:#fff4e6;
    border:1px solid #e9d5c3;
    padding:16px;
    border-radius:10px;
    margin-top:10px;
}

.tarjetas-header{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:12px;
    margin-bottom:12px;
}

.tarjetas-head-left{
    min-width:200px;
}

.tarjetas-sub{
    font-size:.9rem;
    opacity:.85;
    margin-top:4px;
    line-height:1.3;
}

.btn-secundario{
    background:#0F2235;
    color:#fff;
    padding:10px 14px;
    border-radius:8px;
    text-decoration:none;
    font-weight:800;
    border:none;
    cursor:pointer;
    white-space:nowrap;
}
.btn-secundario:hover{ opacity:.92; }

.tarjetas-empty{
    padding:12px;
    background:#fff;
    border:1px dashed #e0c6b1;
    border-radius:8px;
    color:#6b5a4c;
}

.tarjetas-list{ margin-top:10px; }

.tarjeta-item{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    padding:12px;
    background:#fff;
    border:1px solid #eed6c6;
    border-radius:8px;
    margin-bottom:10px;
}

.tarjeta-info{ min-width:0; }

.tarjeta-title{
    font-weight:800;
    display:flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
}

.tarjeta-meta{
    font-size:.9rem;
    color:#5b4d42;
    margin-top:4px;
    line-height:1.3;

    /* ✅ evita que rompa el layout con textos largos */
    word-break:break-word;
}

.pill{
    display:inline-block;
    font-size:.75rem;
    padding:2px 8px;
    border-radius:999px;
    background:#faebdd;
    border:1px solid #e6cdb7;
    color:#0F2235;
}

.btn-eliminar{
    background:#b22b27;
    color:#fff;
    border:none;
    padding:9px 12px;
    border-radius:8px;
    font-weight:800;
    cursor:pointer;
    white-space:nowrap;
}
.btn-eliminar:hover{ opacity:.92; }

/* FOOTER BOTONES */
.footer-botones{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    margin-top:20px;
    flex-wrap:wrap;
}

.link-cancelar{
    color:#b22b27;
    font-weight:800;
    text-decoration:none;
    white-space:nowrap;
}
.link-cancelar:hover{ text-decoration:underline; }

/* ✅ RESPONSIVO */
@media(max-width:720px){
    .contenedor{
        width:100%;
        padding:18px 14px;
    }

    .tarjetas-header{
        flex-direction:column;
        align-items:stretch;
    }

    .btn-secundario{
        width:100%;
        text-align:center;
    }

    .tarjeta-item{
        flex-direction:column;
        align-items:stretch;
    }

    .btn-eliminar{
        width:100%;
    }

    .footer-botones{
        flex-direction:column;
        align-items:stretch;
    }

    .btn-agregar{
        width:100%;
    }

    .link-cancelar{
        width:100%;
        text-align:center;
        padding:8px 0;
        border-radius:8px;
        background:#fff;
        border:1px solid rgba(178,43,39,0.2);
    }
}
</style>

{{-- ===========================
    SCRIPTS
=========================== --}}
<script>
document.getElementById("telefono").addEventListener("input", function() {
    this.value = this.value.substring(0, 13);
});

document.getElementById("telefono_contacto").addEventListener("input", function() {
    this.value = this.value.substring(0, 13);
});

document.getElementById("num_direccion").addEventListener("input", function() {
    this.value = this.value.substring(0, 5);
});

document.getElementById("rfc").addEventListener("input", function() {
    this.value = this.value.substring(0, 13).toUpperCase();
});
</script>

@endsection
