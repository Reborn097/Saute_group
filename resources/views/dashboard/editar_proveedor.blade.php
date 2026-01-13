@extends('layouts.dashboard')

@section('titulo', 'Editar proveedor')

@section('contenido')
<div class="contenedor">

    {{-- Mensajes --}}
    @if (session('success'))
        <div style="background:#d4edda; color:#155724; padding:10px; border-radius:8px; margin-bottom:15px;">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div style="background:#f8d7da; color:#721c24; padding:10px; border-radius:8px; margin-bottom:15px;">
            {{ session('error') }}
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

    {{-- ===========================
        FORM PROVEEDOR
    ============================ --}}
    <form action="{{ route('dashboard.proveedores.actualizar', $proveedor->id) }}" method="POST" id="formProveedor">
        @csrf
        @method('PUT')

        {{-- Nombre --}}
        <div class="mb-3">
            <label><b>Nombre del proveedor</b></label>
            <input type="text" name="nombre" class="form-control"
                   placeholder="Ej. Distribuidora López" required
                   value="{{ old('nombre', $proveedor->nombre) }}">
        </div>

        {{-- Teléfono --}}
        <div class="mb-3">
            <label><b>Teléfono del proveedor</b></label>
            <input type="text" name="telefono" id="telefono"
                   maxlength="13" class="form-control" placeholder="Ej. 2283654321"
                   value="{{ old('telefono', $proveedor->telefono) }}">
        </div>

        {{-- Contacto --}}
        <div class="mb-3">
            <label><b>Nombre del contacto</b></label>
            <input type="text" name="nombre_contacto" class="form-control"
                   placeholder="Ej. Juan Pérez"
                   value="{{ old('nombre_contacto', $proveedor->nombre_contacto) }}">
        </div>

        {{-- Teléfono contacto --}}
        <div class="mb-3">
            <label><b>Teléfono del contacto</b></label>
            <input type="text" name="telefono_contacto" id="telefono_contacto"
                   maxlength="13" class="form-control" placeholder="Ej. 2298745632"
                   value="{{ old('telefono_contacto', $proveedor->telefono_contacto) }}">
        </div>

        {{-- CP --}}
        <div class="mb-3">
            <label><b>Código Postal</b></label>
            <input type="text" name="codigo_postal" maxlength="5"
                   class="form-control" placeholder="Ej. 91017" required
                   value="{{ old('codigo_postal', $proveedor->codigo_postal) }}">
        </div>

        {{-- Colonia --}}
        <div class="mb-3">
            <label><b>Colonia</b></label>
            <input type="text" name="colonia" class="form-control"
                   placeholder="Ej. Centro" required
                   value="{{ old('colonia', $proveedor->colonia) }}">
        </div>

        {{-- Calle --}}
        <div class="mb-3">
            <label><b>Calle</b></label>
            <input type="text" name="calle" class="form-control"
                   placeholder="Ej. Calle Principal #25"
                   value="{{ old('calle', $proveedor->calle) }}">
        </div>

        {{-- Num dirección --}}
        <div class="mb-3">
            <label><b>Número de Dirección</b></label>
            <input type="text" name="num_direccion" id="num_direccion"
                   maxlength="5" class="form-control" placeholder="Ej. 10-B"
                   value="{{ old('num_direccion', $proveedor->num_direccion) }}">
        </div>

        {{-- RFC --}}
        <div class="mb-3">
            <label><b>RFC</b></label>
            <input type="text" name="rfc" id="rfc" maxlength="13"
                   class="form-control" placeholder="Ej. LOPE890123JKL"
                   value="{{ old('rfc', $proveedor->rfc) }}">
        </div>

        {{-- BOTONES --}}
        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
            <a href="{{ route('dashboard.proveedores') }}"
               style="color: #b22b27; font-weight: bold; text-decoration: none;">
                Cancelar
            </a>

            <button type="submit" class="btn-agregar">
                Guardar cambios
            </button>
        </div>
    </form>

    {{-- ===========================
        TARJETAS (BD) EN EDITAR
    ============================ --}}
    <div class="tarjetas-box">
        <div class="tarjetas-header">
            <div>
                <b>Datos de la cuenta (tarjetas registradas)</b>
                <div class="tarjetas-sub">
                    Aquí puedes agregar, editar o eliminar tarjetas vinculadas a este proveedor.
                </div>
            </div>
        </div>

        {{-- FORM AGREGAR NUEVA TARJETA (BD) --}}
        <details class="tarjeta-details">
            <summary class="btn-secundario" style="list-style:none; cursor:pointer;">
                Agregar nueva tarjeta
            </summary>

            <div style="margin-top: 12px;">
                <form action="{{ route('dashboard.proveedores.tarjetas.store', $proveedor->id) }}" method="POST">
                    @csrf

                    <div class="grid-2">
                        <div>
                            <label><b>Tipo</b></label>
                            <select name="tipo" class="form-control" required>
                                <option value="empresa">Empresa</option>
                                <option value="contacto">Contacto</option>
                            </select>
                        </div>

                        <div>
                            <label><b>Alias</b></label>
                            <input type="text" name="alias" class="form-control"
                                   maxlength="60"
                                   placeholder="Ej. Principal / Bancomer">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div>
                            <label><b>Banco</b></label>
                            <input type="text" name="banco" class="form-control"
                                   maxlength="60"
                                   placeholder="Ej. BBVA">
                        </div>
                        <div>
                            <label><b>Titular</b></label>
                            <input type="text" name="titular" class="form-control"
                                   maxlength="80"
                                   placeholder="Ej. Distribuidora López SA">
                        </div>
                    </div>

                    <div class="grid-3">
                        <div>
                            <label><b>CLABE</b></label>
                            <input type="text" name="clabe" maxlength="18"
                                   class="form-control only-digits"
                                   inputmode="numeric" pattern="[0-9]*"
                                   placeholder="18 dígitos">
                        </div>
                        <div>
                            <label><b>Cuenta</b></label>
                            <input type="text" name="cuenta" maxlength="20"
                                   class="form-control only-digits"
                                   inputmode="numeric" pattern="[0-9]*"
                                   placeholder="Opcional">
                        </div>
                        <div>
                            <label><b>Tarjeta</b></label>
                            <input type="text" name="tarjeta" maxlength="20"
                                   class="form-control only-digits"
                                   inputmode="numeric" pattern="[0-9]*"
                                   placeholder="Opcional">
                        </div>
                    </div>

                    <label class="check-row">
                        <input type="checkbox" name="activa" value="1" checked>
                        <b>Activa</b>
                    </label>

                    <div style="display:flex; justify-content:flex-end; margin-top:12px;">
                        <button type="submit" class="btn-agregar">Guardar tarjeta</button>
                    </div>
                </form>
            </div>
        </details>

        {{-- LISTA TARJETAS --}}
        @if(!empty($tarjetas) && count($tarjetas) > 0)
            <div class="tarjetas-list">
                <div style="margin-bottom: 10px;">
                    <b>Tarjetas registradas:</b> {{ count($tarjetas) }}
                </div>

                @foreach($tarjetas as $t)
                    <div class="tarjeta-item">
                        <div style="flex:1; min-width: 0;">
                            <div class="tarjeta-title-row">
                                <div class="text-limit" title="{{ $t->alias ?? ('Tarjeta #' . $t->id) }}">
                                    {{ $t->alias ?? ('Tarjeta #' . $t->id) }}
                                </div>

                                <div class="badges">
                                    <span class="pill">{{ strtoupper($t->tipo ?? 'empresa') }}</span>

                                    @if((int)($t->activa ?? 0) === 1)
                                        <span class="pill pill-ok">ACTIVA</span>
                                    @else
                                        <span class="pill pill-bad">INACTIVA</span>
                                    @endif
                                </div>
                            </div>

                            <div class="tarjeta-meta">
                                <span class="text-limit" title="Banco: {{ $t->banco ?? '—' }}">
                                    Banco: {{ $t->banco ?? '—' }}
                                </span>
                                <span> | </span>
                                <span class="text-limit" title="Titular: {{ $t->titular ?? '—' }}">
                                    Titular: {{ $t->titular ?? '—' }}
                                </span>
                                <span> | </span>
                                <span class="text-limit" title="CLABE: {{ $t->clabe ?? '—' }}">
                                    CLABE: {{ $t->clabe ?? '—' }}
                                </span>
                            </div>

                            {{-- EDITAR (inline) --}}
                            <details style="margin-top:10px;">
                                <summary class="edit-summary">
                                    Editar tarjeta
                                </summary>

                                <div style="margin-top: 10px;">
                                    <form action="{{ route('dashboard.proveedores.tarjetas.update', $t->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')

                                        <div class="grid-2">
                                            <div>
                                                <label><b>Tipo</b></label>
                                                <select name="tipo" class="form-control" required>
                                                    <option value="empresa" {{ ($t->tipo === 'empresa') ? 'selected' : '' }}>Empresa</option>
                                                    <option value="contacto" {{ ($t->tipo === 'contacto') ? 'selected' : '' }}>Contacto</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label><b>Alias</b></label>
                                                <input type="text" name="alias" class="form-control"
                                                       maxlength="15"
                                                       value="{{ old('alias', $t->alias) }}">
                                            </div>
                                        </div>

                                        <div class="grid-2">
                                            <div>
                                                <label><b>Banco</b></label>
                                                <input type="text" name="banco" class="form-control"
                                                       maxlength="15"
                                                       value="{{ old('banco', $t->banco) }}">
                                            </div>
                                            <div>
                                                <label><b>Titular</b></label>
                                                <input type="text" name="titular" class="form-control"
                                                       maxlength="80"
                                                       value="{{ old('titular', $t->titular) }}">
                                            </div>
                                        </div>

                                        <div class="grid-3">
                                            <div>
                                                <label><b>CLABE</b></label>
                                                <input type="text" name="clabe" maxlength="18" class="form-control only-digits"
                                                       inputmode="numeric" pattern="[0-9]*"
                                                       value="{{ old('clabe', $t->clabe) }}">
                                            </div>
                                            <div>
                                                <label><b>Cuenta</b></label>
                                                <input type="text" name="cuenta" maxlength="15" class="form-control only-digits"
                                                       inputmode="numeric" pattern="[0-9]*"
                                                       value="{{ old('cuenta', $t->cuenta) }}">
                                            </div>
                                            <div>
                                                <label><b>Tarjeta</b></label>
                                                <input type="text" name="tarjeta" maxlength="5" class="form-control only-digits"
                                                       inputmode="numeric" pattern="[0-9]*"
                                                       value="{{ old('tarjeta', $t->tarjeta) }}">
                                            </div>
                                        </div>

                                        <label class="check-row">
                                            <input type="checkbox" name="activa" value="1" {{ ((int)($t->activa ?? 0) === 1) ? 'checked' : '' }}>
                                            <b>Activa</b>
                                        </label>

                                        <div style="display:flex; justify-content:flex-end; margin-top:12px; gap:10px;">
                                            <button type="submit" class="btn-secundario" style="border:none; cursor:pointer;">
                                                Guardar cambios tarjeta
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </details>
                        </div>

                        {{-- ELIMINAR tarjeta (BD) --}}
                        <form action="{{ route('dashboard.proveedores.tarjetas.destroy', $t->id) }}" method="POST" style="margin:0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-eliminar"
                                    onclick="return confirm('¿Seguro que deseas eliminar esta tarjeta?');">
                                Eliminar
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @else
            <div class="tarjetas-empty">
                Este proveedor aún no tiene tarjetas registradas.
            </div>
        @endif
    </div>

</div>

<style>
.contenedor {
    background-color: #fceede;
    padding: 25px;
    border-radius: 12px;
    max-width: 800px;
    margin: auto;
}

.form-control {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    margin-top: 5px;
    margin-bottom: 15px;
    font-family: Poppins, sans-serif;
}

.btn-agregar {
    background-color: #941c1c;
    color: #fff;
    padding: 10px 15px;
    border-radius: 8px;
    border: none;
    font-weight: bold;
    cursor: pointer;
}
.btn-agregar:hover { background-color: #b82929; }

.tarjetas-box{
    background:#fff4e6;
    border:1px solid #e9d5c3;
    padding:16px;
    border-radius:10px;
    margin-top: 18px;
}

.tarjetas-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    margin-bottom: 12px;
}

.tarjetas-sub{
    font-size:.9rem;
    opacity:.85;
    margin-top:4px;
}

.btn-secundario{
    background:#0F2235;
    color:#fff;
    padding:10px 14px;
    border-radius:8px;
    text-decoration:none;
    font-weight:700;
    white-space:nowrap;
    display:inline-block;
}
.btn-secundario:hover{ opacity:.92; }

.tarjetas-empty{
    padding:12px;
    background:#fff;
    border:1px dashed #e0c6b1;
    border-radius:8px;
    color:#6b5a4c;
}

.tarjetas-list{ margin-top: 10px; }

.tarjeta-item{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:12px;
    padding:12px;
    background:#fff;
    border:1px solid #eed6c6;
    border-radius:8px;
    margin-bottom:10px;
}

.tarjeta-title-row{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
}

.badges{
    display:flex;
    gap:8px;
    flex-wrap: wrap;
    justify-content:flex-end;
}

.tarjeta-meta{
    font-size:.9rem;
    color:#5b4d42;
    margin-top:4px;
    display:flex;
    gap:4px;
    flex-wrap: wrap;
}

/* ✅ evita que el texto reviente el diseño */
.text-limit{
    max-width: 360px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display:inline-block;
    vertical-align: bottom;
}

/* Pills */
.pill{
    display:inline-block;
    font-size:.75rem;
    padding:2px 8px;
    border-radius:999px;
    background:#faebdd;
    border:1px solid #e6cdb7;
    color:#0F2235;
}
.pill-ok{ background:#e8fff2; border-color:#bde8cd; }
.pill-bad{ background:#fff1f1; border-color:#f2c1c1; }

.btn-eliminar{
    background:#b22b27;
    color:#fff;
    border:none;
    padding:8px 12px;
    border-radius:8px;
    font-weight:700;
    cursor:pointer;
    height: fit-content;
}
.btn-eliminar:hover{ opacity:.92; }

.grid-2{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:12px;
}
.grid-3{
    display:grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap:12px;
}

.check-row{
    display:flex;
    align-items:center;
    gap:8px;
    margin-top:8px;
}

.edit-summary{
    cursor:pointer;
    font-weight:700;
    color:#0F2235;
}

@media (max-width: 720px){
    .grid-2, .grid-3{
        grid-template-columns: 1fr;
    }
    .text-limit{ max-width: 240px; }
}

.tarjeta-details summary::-webkit-details-marker { display:none; }
</style>

<script>
document.getElementById("telefono")?.addEventListener("input", function() {
    this.value = this.value.substring(0, 13);
});

document.getElementById("telefono_contacto")?.addEventListener("input", function() {
    this.value = this.value.substring(0, 13);
});

document.getElementById("num_direccion")?.addEventListener("input", function() {
    this.value = this.value.substring(0, 5);
});

document.getElementById("rfc")?.addEventListener("input", function() {
    this.value = this.value.substring(0, 13).toUpperCase();
});

/* ✅ Solo números para CLABE/Cuenta/Tarjeta (y respeta maxlength) */
document.querySelectorAll('.only-digits').forEach((inp) => {
    inp.addEventListener('input', function () {
        const max = parseInt(this.getAttribute('maxlength') || '999', 10);
        this.value = (this.value || '').replace(/\D+/g, '').substring(0, max);
    });
});
</script>
@endsection
