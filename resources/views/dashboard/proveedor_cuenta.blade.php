@extends('layouts.dashboard')

@section('titulo', 'Cuentas del proveedor')

@section('contenido')
<div class="contenedor">

    <div class="acciones-superior">
        <a href="{{ route('dashboard.proveedores') }}" class="btn-menu">Regresar</a>


        <div style="font-weight:700; color:#b22b27;">
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
                            {{ $t->alias ?? 'Cuenta' }}
                            <span class="pill">{{ strtoupper($t->tipo ?? 'EMPRESA') }}</span>
                        </div>
                        <div class="activa">{{ $t->activa ? 'Activa' : 'Inactiva' }}</div>
                    </div>

                    <div class="meta">
                        <div><b>Banco:</b> {{ $t->banco ?? '—' }}</div>
                        <div><b>Titular:</b> {{ $t->titular ?? '—' }}</div>
                        <div><b>CLABE:</b> {{ $t->clabe ?? '—' }}</div>
                        <div><b>Cuenta:</b> {{ $t->cuenta ?? '—' }}</div>
                        <div><b>Tarjeta:</b> {{ $t->tarjeta ?? '—' }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

<style>
.contenedor{
    background-color:#fceede;
    padding:25px;
    border-radius:12px;
    max-width:1000px;
    margin:auto;
    box-shadow:0 0 10px rgba(0,0,0,0.1);
}
.acciones-superior{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:15px;
}
.btn-menu{
    background-color:#999;
    color:white;
    border:none;
    padding:8px 18px;
    border-radius:8px;
    text-decoration:none;
    font-weight:600;
}
.btn-menu:hover{ background-color:#8f1f1c; }
.empty{
    background:#fff;
    padding:18px;
    border-radius:10px;
}
.lista{
    display:flex;
    flex-direction:column;
    gap:12px;
}
.card{
    background:#fff;
    border-radius:10px;
    padding:14px;
    border:1px solid #eee;
}
.card-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:10px;
}
.alias{ font-weight:800; }
.pill{
    background:#f8dcdc;
    color:#b22b27;
    padding:2px 10px;
    border-radius:999px;
    font-size:12px;
    margin-left:8px;
    font-weight:800;
}
.activa{
    font-weight:700;
    color:#0e2238;
}
.meta{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:8px 18px;
}
@media (max-width:700px){
    .meta{ grid-template-columns:1fr; }
}
</style>
@endsection
