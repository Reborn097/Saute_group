@extends('layouts.dashboard')

@section('titulo', 'Cuentas del proveedor')

@section('contenido')
<div class="contenedor">

    <div class="acciones-superior">
        <a href="{{ route('dashboard.proveedores') }}" class="btn-menu">Regresar</a>

        <div class="proveedor-nombre">
            {{ $proveedor->nombre }}
        </div>
    </div>

    @if($tarjetas->isEmpty())
        <div class="empty">
            Este proveedor no tiene cuentas/tarjetas registradas.
        </div>
    @else
        <div class="lista">
            @foreach($tarjetas as $t)
                <div class="card">
                    <div class="card-top">
                        <div class="alias">
                            <span class="alias-text">
                                {{ $t->alias ?? 'Cuenta' }}
                            </span>
                            <span class="pill">{{ strtoupper($t->tipo ?? 'EMPRESA') }}</span>
                        </div>
                        <div class="activa">{{ $t->activa ? 'Activa' : 'Inactiva' }}</div>
                    </div>

                    <div class="meta">
                        <div><b>Banco:</b> {{ $t->banco ?? '—' }}</div>
                        <div><b>Titular:</b> {{ $t->titular ?? '—' }}</div>
                        <div><b>CLABE:</b> <span class="mono">{{ $t->clabe ?? '—' }}</span></div>
                        <div><b>Cuenta:</b> <span class="mono">{{ $t->cuenta ?? '—' }}</span></div>
                        <div><b>Tarjeta:</b> <span class="mono">{{ $t->tarjeta ?? '—' }}</span></div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

<style>
/* contenedor general */
.contenedor{
    background-color:#fceede;
    padding:25px;
    border-radius:12px;
    max-width:1000px;
    margin:auto;
    box-shadow:0 0 10px rgba(0,0,0,0.1);
}

/* header acciones */
.acciones-superior{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:14px;
    margin-bottom:15px;
    flex-wrap:wrap;              /* ✅ permite bajar elementos si no caben */
}

/* ✅ botón que no se deforma */
.btn-menu{
    background-color:#999;
    color:#fff;
    border:none;
    padding:10px 18px;
    border-radius:10px;
    text-decoration:none;
    font-weight:700;

    display:inline-flex;         /* ✅ evita deformaciones */
    align-items:center;
    justify-content:center;

    white-space:nowrap;          /* ✅ no parte "Regresar" */
    flex:0 0 auto;               /* ✅ no se estira */
    min-width:140px;             /* ✅ ancho estable */
    max-width:100%;
}
.btn-menu:hover{ background-color:#8f1f1c; }

.proveedor-nombre{
    font-weight:800;
    color:#b22b27;
    text-align:right;
    max-width:60%;
    word-break:break-word;       /* ✅ por si el nombre es largo */
}

/* empty */
.empty{
    background:#fff;
    padding:18px;
    border-radius:10px;
    border:1px solid rgba(0,0,0,.06);
}

/* lista / cards */
.lista{
    display:flex;
    flex-direction:column;
    gap:12px;
}

.card{
    background:#fff;
    border-radius:12px;
    padding:14px;
    border:1px solid rgba(0,0,0,.08);
}

.card-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    margin-bottom:10px;
    flex-wrap:wrap;
}

.alias{
    font-weight:900;
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
}

.alias-text{
    max-width:520px;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

.pill{
    background:#f8dcdc;
    color:#b22b27;
    padding:3px 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:900;
}

.activa{
    font-weight:800;
    color:#0e2238;
}

/* meta */
.meta{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:8px 18px;
}

.mono{
    font-variant-numeric: tabular-nums;
    letter-spacing:.2px;
}

/* ✅ responsive */
@media (max-width:700px){
    .acciones-superior{
        flex-direction:column;
        align-items:stretch;      /* ✅ para que el botón use todo el ancho */
    }

    .btn-menu{
        width:100%;               /* ✅ full ancho en móvil */
        min-width:0;
    }

    .proveedor-nombre{
        max-width:100%;
        width:100%;
        text-align:left;          /* ✅ se ve mejor debajo del botón */
    }

    .meta{
        grid-template-columns:1fr;
    }

    .alias-text{
        max-width:100%;
    }
}
</style>
@endsection
