@php
    /**
     * Logo de respaldo: se usa mientras no haya un logo cargado desde el admin
     * (Contacto → Logos del sitio). $variant: 'color' (fondo claro) o 'blanco'.
     */
    $variant = $variant ?? 'color';
    $class = $class ?? 'h-14 w-auto';
    $blanco = $variant === 'blanco';
    $shield = $blanco ? '#FFFFFF' : '#0D2B5E';
    $shieldText = $blanco ? '#0D2B5E' : '#FFFFFF';
    $word = $blanco ? '#FFFFFF' : '#0D2B5E';
    $band = '#E11A22';
@endphp

<svg viewBox="0 0 256 96" class="{{ $class }}" role="img" aria-label="Rejovot Autopartes" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M8 8h60v46c0 18-14 28-30 34C22 82 8 72 8 54V8Z" fill="{{ $shield }}"/>
    <path d="M14 52h48c-2 12-13 19-24 24-11-5-22-12-24-24Z" fill="{{ $band }}"/>
    <text x="38" y="42" text-anchor="middle" font-family="Montserrat, Arial, sans-serif" font-size="22" font-weight="800" fill="{{ $shieldText }}" letter-spacing="1">RJ</text>
    <text x="82" y="48" font-family="Montserrat, Arial, sans-serif" font-size="29" font-weight="800" fill="{{ $word }}" letter-spacing="1">REJOVOT</text>
    <rect x="82" y="57" width="166" height="17" rx="2" fill="{{ $band }}"/>
    <text x="165" y="70" text-anchor="middle" font-family="Montserrat, Arial, sans-serif" font-size="12" font-weight="700" fill="#FFFFFF" letter-spacing="3.5">AUTOPARTES</text>
</svg>
