@php
    /**
     * Imagen del producto. La sirve Odoo desde /web/image/product.template/…
     * Si el producto no tiene foto cargada, queda el ícono de respaldo.
     */
    $class = $class ?? 'h-12 w-12';
    $src = $src ?? null;
    $alt = $alt ?? 'Imagen del producto';
@endphp

<span class="relative inline-flex shrink-0 items-center justify-center {{ $class }}">
    @if($src)
        <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy" decoding="async"
             class="h-full w-full object-contain"
             onerror="this.parentElement.querySelector('svg').classList.remove('hidden'); this.remove();">
    @endif

    <svg viewBox="0 0 64 64" class="h-full w-full text-slate-400 {{ $src ? 'hidden' : '' }}" fill="none"
         xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{{ $alt }}">
        <rect x="6" y="18" width="34" height="28" rx="6" fill="currentColor" fill-opacity=".22"/>
        <rect x="36" y="24" width="20" height="16" rx="4" fill="currentColor" fill-opacity=".35"/>
        <circle cx="23" cy="32" r="9" fill="currentColor" fill-opacity=".45"/>
        <circle cx="23" cy="32" r="4" fill="#fff" fill-opacity=".8"/>
        <rect x="14" y="10" width="10" height="9" rx="3" fill="currentColor" fill-opacity=".35"/>
    </svg>
</span>
