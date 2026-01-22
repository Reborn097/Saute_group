@if ($paginator->hasPages())
    <nav class="paginacion-centro">
        {{-- Anterior --}}
        @if ($paginator->onFirstPage())
            <span class="page-btn disabled">‹</span>
        @else
            <a class="page-btn"
               href="{{ $paginator->previousPageUrl() }}"
               rel="prev">‹</a>
        @endif

        {{-- Números --}}
        @foreach ($elements as $element)
            {{-- Separador ... --}}
            @if (is_string($element))
                <span class="page-separator">{{ $element }}</span>
            @endif

            {{-- Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="page-btn active">{{ $page }}</span>
                    @else
                        <a class="page-btn" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Siguiente --}}
        @if ($paginator->hasMorePages())
            <a class="page-btn"
               href="{{ $paginator->nextPageUrl() }}"
               rel="next">›</a>
        @else
            <span class="page-btn disabled">›</span>
        @endif
    </nav>
@endif
<style>.paginacion-centro{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:6px;
    margin:18px 0 10px;
    flex-wrap:wrap;
}

.page-btn{
    min-width:36px;
    height:36px;
    padding:0 10px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:10px;
    font-weight:700;
    font-size:14px;
    background:#fff;
    border:1px solid rgba(178,43,39,.35);
    color:#b22b27;
    text-decoration:none;
    transition:.15s ease;
}

.page-btn:hover{
    background:#b22b27;
    color:#fff;
}

.page-btn.active{
    background:#b22b27;
    color:#fff;
    cursor:default;
}

.page-btn.disabled{
    opacity:.35;
    cursor:not-allowed;
    pointer-events:none;
}

.page-separator{
    padding:0 6px;
    font-weight:700;
    color:#777;
}
</style>