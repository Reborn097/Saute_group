<style id="saute-button-final-override">
    :root{
        --saute-btn-primary:#b22b27;
        --saute-btn-primary-hover:#941c1c;
        --saute-btn-secondary:#777777;
        --saute-btn-secondary-hover:#5f5f5f;
        --saute-btn-danger:#111111;
        --saute-btn-danger-hover:#000000;
        --saute-btn-pdf:#3f3f46;
        --saute-btn-pdf-hover:#2f2f35;
        --saute-btn-success:#2f8f53;
        --saute-btn-success-hover:#267645;
        --saute-btn-warning:#d69a2d;
        --saute-btn-warning-hover:#b27f24;
    }

    .btn, [class*="btn-"]{
        border-radius:9px !important;
        font-weight:700 !important;
        border:1px solid transparent !important;
        display:inline-flex !important;
        align-items:center !important;
        justify-content:center !important;
        gap:8px !important;
        min-height:40px !important;
        padding:0 14px !important;
        font-size:.92rem !important;
        line-height:1 !important;
        text-decoration:none !important;
    }

    .btn-mini,.btn-page,.btn-link,.btn-mini-editar,.btn-mini-eliminar,.btn-mini-almacenes,.btn-cerrar,.btn-black,.btn-gris,.btn-sec,.btn-secundario,.btn-cancelar,.btn-regresar,.btn-limpiar{
        min-height:34px !important;
        padding:0 10px !important;
        font-size:.82rem !important;
    }
    .btn-confirmar,.btn-guardar,.btn-guardar-all,.btn-primary,.btn-reporte,.btn-instrucciones,.btn-plantilla,.btn-descargar-plantilla{
        min-height:46px !important;
        padding:0 18px !important;
        font-size:.98rem !important;
    }

    .btn,.btn-menu,.btn-confirmar,.btn-guardar,.btn-agregar,.btn-accion,.btn-primary,.btn-buscar,.btn-filtrar,.btn-ver,.btn-editar,.btn-seleccionar,.btn-cuenta,.btn-reporte,.btn-detalles,.btn-aprobar,.btn-preaprobar{
        background:var(--saute-btn-primary) !important;
        color:#fff !important;
        border-color:var(--saute-btn-primary) !important;
    }
    .btn:hover,.btn-menu:hover,.btn-confirmar:hover,.btn-guardar:hover,.btn-agregar:hover,.btn-accion:hover,.btn-primary:hover,.btn-buscar:hover,.btn-filtrar:hover,.btn-ver:hover,.btn-editar:hover,.btn-seleccionar:hover,.btn-cuenta:hover,.btn-reporte:hover,.btn-detalles:hover,.btn-aprobar:hover,.btn-preaprobar:hover{
        background:var(--saute-btn-primary-hover) !important;
        border-color:var(--saute-btn-primary-hover) !important;
    }

    .btn-cancelar,.btn-regresar,.btn-secundario,.btn-sec,.btn-limpiar,.btn-link,.btn-page{
        background:var(--saute-btn-secondary) !important;
        color:#fff !important;
        border-color:var(--saute-btn-secondary) !important;
    }
    .btn-cancelar:hover,.btn-regresar:hover,.btn-secundario:hover,.btn-sec:hover,.btn-limpiar:hover,.btn-link:hover,.btn-page:hover{
        background:var(--saute-btn-secondary-hover) !important;
        border-color:var(--saute-btn-secondary-hover) !important;
    }

    .btn-eliminar,.btn-mini-eliminar,.btn-quitar,.btn-rechazar,.btn-rojo,.btn-black{
        background:var(--saute-btn-danger) !important;
        color:#fff !important;
        border-color:var(--saute-btn-danger) !important;
    }
    .btn-eliminar:hover,.btn-mini-eliminar:hover,.btn-quitar:hover,.btn-rechazar:hover,.btn-rojo:hover,.btn-black:hover{
        background:var(--saute-btn-danger-hover) !important;
        border-color:var(--saute-btn-danger-hover) !important;
    }

    .btn-pdf{
        background:var(--saute-btn-pdf) !important;
        color:#fff !important;
        border-color:var(--saute-btn-pdf) !important;
    }
    .btn-pdf:hover{
        background:var(--saute-btn-pdf-hover) !important;
        border-color:var(--saute-btn-pdf-hover) !important;
    }

    .btn-aceptar,.btn-verde{
        background:var(--saute-btn-success) !important;
        color:#fff !important;
        border-color:var(--saute-btn-success) !important;
    }
    .btn-aceptar:hover,.btn-verde:hover{
        background:var(--saute-btn-success-hover) !important;
        border-color:var(--saute-btn-success-hover) !important;
    }

    .btn-ambar{
        background:var(--saute-btn-warning) !important;
        color:#fff !important;
        border-color:var(--saute-btn-warning) !important;
    }
    .btn-ambar:hover{
        background:var(--saute-btn-warning-hover) !important;
        border-color:var(--saute-btn-warning-hover) !important;
    }

    .btn-outline{
        background:transparent !important;
        color:var(--saute-btn-primary) !important;
        border-color:var(--saute-btn-primary) !important;
    }
    .btn-outline:hover{
        background:rgba(178,43,39,.08) !important;
    }
</style>
