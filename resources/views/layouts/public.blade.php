<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.seo')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        html, body, button, input, select, textarea, h1, h2, h3, h4, h5, h6 {
            font-family: 'Montserrat', ui-sans-serif, system-ui, sans-serif;
        }
        :root {
            --rj-navy: #002B56;
            --rj-navy-deep: #0A2249;
            --rj-red: #E11A22;
        }
        .nav-link { position: relative; white-space: nowrap; }
        .nav-link::after {
            content: ''; position: absolute; left: 0; right: 0; bottom: -6px;
            height: 2px; background: var(--rj-red); transform: scaleX(0);
            transition: transform .18s ease;
        }
        .nav-link:hover::after { transform: scaleX(1); }
        .nav-link.active { font-weight: 700; }
        .nav-link.active::after { transform: scaleX(1); }
    </style>
</head>
<body class="layout-public flex min-h-screen flex-col bg-white text-[#1b1b18]">

    @php
        $contactData = \App\Models\Contact::with('infoItems')->first();
        $wsspItem = $contactData?->infoItems->firstWhere('type', 'whatsapp_flotante');
        $wssp = $wsspItem->value ?? $contactData?->wssp;
        // En el detalle de un producto queda activa la sección desde la que se llegó.
        $desdeVehiculos = request()->routeIs('producto') && request('desde') === 'vehiculos';
        // Estas secciones no tienen sentido sin sesión: se ven apagadas y no navegan.
        $soloConSesion = ! auth('sitio')->check();

        $navItems = [
            ['label' => 'Productos', 'route' => 'productos', 'activo' => request()->routeIs('producto') && ! $desdeVehiculos],
            ['label' => 'Búsqueda por marca', 'route' => 'vehiculos', 'activo' => $desdeVehiculos, 'privada' => true],
            ['label' => 'Carrito', 'route' => 'carrito', 'privada' => true],
            ['label' => 'Mis Pedidos', 'route' => 'pedidos', 'privada' => true],
            ['label' => 'Lista de precios', 'route' => 'precios', 'privada' => true],
            ['label' => 'Estado de la Cuenta', 'route' => 'cuenta', 'privada' => true],
            ['label' => 'Info de Pagos', 'route' => 'pagos', 'privada' => true],
            ['label' => 'Márgenes', 'route' => 'margenes', 'privada' => true],
        ];

        $bloqueada = fn (array $item) => ($item['privada'] ?? false) && $soloConSesion;

        // Cuenta: cliente activo, su descuento y, si es vendedor, su cartera.
        // Lo usan el header de escritorio y el menú del celular.
        $usuarioSitio = auth('sitio')->user();
        $clienteHeader = app(\App\Services\Sesion\ClienteActivo::class)->actual();
        // El descuento sale de Márgenes, no del que tiene cargado en Odoo.
        $descuentoHeader = $clienteHeader ? app(\App\Services\Margenes\Margenes::class)->descuento() : 0;
        $carteraHeader = $usuarioSitio?->esVendedor()
            ? \App\Models\Customer::where('active', true)->where('salesperson_id', $usuarioSitio->salesperson_id)->orderBy('name')->get(['id', 'name'])
            : collect();
        $nombreHeader = $clienteHeader?->name ?? $usuarioSitio?->name;
        // Unidades en el carrito activo. Las pantallas que lo cambian avisan
        // con el evento «carrito-cantidad» y el contador se actualiza solo.
        $cantidadCarrito = $usuarioSitio ? app(\App\Services\Carrito\Carrito::class)->cantidadTotal() : 0;
    @endphp

    @include('partials.site-toast')

    <header x-data="{ open: false }" @keydown.escape.window="open = false"
            class="relative z-30 w-full bg-white" style="box-shadow: 0 4px 7px rgba(0,0,0,.05);">
        <div class="mx-auto flex h-[116px] w-full max-w-[1300px] items-center justify-between gap-8 px-4 xl:px-6">

            <a wire:navigate href="{{ route('productos') }}" class="flex shrink-0 items-center" aria-label="Rejovot Autopartes">
                @if($contactData?->icono_1)
                    <img src="{{ Storage::url($contactData->icono_1) }}" alt="Rejovot Autopartes" class="h-[94px] w-auto object-contain">
                @else
                    @include('partials.logo', ['class' => 'h-[94px] w-auto'])
                @endif
            </a>

            @include('partials.header-escritorio')

            <button type="button" @click="open = true"
                    class="relative flex h-11 w-11 flex-col items-end justify-center gap-[6px] xl:hidden"
                    aria-label="Abrir menú" :aria-expanded="open">
                @include('partials.contador-carrito', ['clase' => 'absolute -left-1 -top-0.5'])
                <span class="block h-[3px] w-8 rounded bg-[#002B56]"></span>
                <span class="block h-[3px] w-6 rounded bg-[#E11A22]"></span>
                <span class="block h-[3px] w-8 rounded bg-[#002B56]"></span>
            </button>
        </div>

        {{-- Menú mobile --}}
        <div x-show="open" x-cloak x-transition.opacity @click="open = false"
             class="fixed inset-0 z-[98] bg-black/60 backdrop-blur-sm xl:hidden"></div>

        <aside x-show="open" x-cloak
               x-transition:enter="transition ease-out duration-300"
               x-transition:enter-start="translate-x-full opacity-0"
               x-transition:enter-end="translate-x-0 opacity-100"
               x-transition:leave="transition ease-in duration-200"
               x-transition:leave-start="translate-x-0 opacity-100"
               x-transition:leave-end="translate-x-full opacity-0"
               class="fixed inset-y-0 right-0 z-[99] w-[85%] max-w-[380px] overflow-y-auto bg-white shadow-2xl xl:hidden">

            <div class="flex items-center justify-between bg-[#002B56] px-6 py-7">
                <div>
                    <h2 class="text-[22px] font-bold text-white">Menú</h2>
                    <p class="mt-1 text-[13px] text-white/70">{{ auth('sitio')->check() ? auth('sitio')->user()->name : 'Ingresá a tu cuenta' }}</p>
                </div>
                <button @click="open = false" class="rounded-lg p-2 text-white transition hover:bg-white/10" aria-label="Cerrar menú">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <nav class="space-y-1 px-5 py-6">
                @foreach($navItems as $item)
                    @php $activo = ($item['activo'] ?? false) || ($item['route'] && request()->routeIs($item['route'])); @endphp
                    @if($bloqueada($item))
                        {{-- Sin sesión: apagada, y al tocarla se abre el ingreso. --}}
                        <button type="button" @click="open = false; $dispatch('abrir-login')"
                                class="block w-full cursor-pointer rounded-lg px-4 py-3 text-left text-[15px] font-medium text-slate-400">
                            {{ $item['label'] }}
                        </button>
                    @else
                        <a href="{{ $item['route'] ? route($item['route']) : '#' }}"
                           @click="open = false"
                           class="flex items-center justify-between rounded-lg px-4 py-3 text-[15px] font-medium transition
                                  {{ $activo ? 'bg-[#002B56] text-white shadow-md' : 'text-slate-700 hover:bg-slate-50 hover:text-[#002B56]' }}">
                            {{ $item['label'] }}
                            @if($item['route'] === 'carrito')
                                @include('partials.contador-carrito')
                            @endif
                        </a>
                    @endif
                @endforeach
            </nav>

            <div class="border-t border-slate-100 px-5 py-6">
                @auth('sitio')
                    {{-- Igual que el header de escritorio: datos del cliente y, si es
                         vendedor, el selector de cliente. --}}
                    <p class="px-4 text-[13px] text-slate-500">
                        <span class="block text-[15px] font-semibold uppercase leading-[130%] text-slate-800">{{ $nombreHeader }}</span>
                        @if($clienteHeader)
                            <span class="mt-0.5 block">
                                @if($clienteHeader->vat) CUIT {{ $clienteHeader->vat }} @endif
                                @if($descuentoHeader > 0) · {{ rtrim(rtrim(number_format($descuentoHeader, 2, ',', '.'), '0'), ',') }}% de descuento @endif
                                @if($clienteHeader->salesperson) · {{ $clienteHeader->salesperson->name }} @endif
                            </span>
                        @else
                            <span class="mt-0.5 block">{{ $usuarioSitio->esVendedor() ? 'Vendedor' : 'Cliente' }}</span>
                        @endif
                    </p>

                    @if($usuarioSitio->esVendedor())
                        <form method="POST" action="{{ route('cliente.elegir') }}" class="mt-3 px-4">
                            @csrf
                            <label for="mob-cliente" class="sr-only">Cliente</label>
                            <select id="mob-cliente" name="cliente" onchange="this.form.submit()"
                                    class="h-[42px] w-full cursor-pointer rounded-[4px] border border-slate-300 bg-white px-2 text-[14px] text-slate-800 outline-none focus:border-[#002B56]">
                                <option value="" @selected(! $clienteHeader)>{{ $carteraHeader->isEmpty() ? 'No tenés clientes asignados' : 'Elegí un cliente' }}</option>
                                @foreach($carteraHeader as $c)
                                    <option value="{{ $c->id }}" @selected($clienteHeader?->id === $c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                    <a wire:navigate href="{{ route('reclamos') }}" @click="open = false"
                       class="mt-3 block rounded-lg px-4 py-3 text-[15px] font-medium transition {{ request()->routeIs('reclamos*') ? 'bg-[#002B56] text-white shadow-md' : 'text-slate-700 hover:bg-slate-50 hover:text-[#002B56]' }}">
                        Garantías
                    </a>
                    <form method="POST" action="{{ route('salir') }}">
                        @csrf
                        <button type="submit" class="block w-full cursor-pointer rounded-lg px-4 py-3 text-left text-[15px] font-medium text-slate-700 transition hover:bg-slate-50 hover:text-[#002B56]">
                            Cerrar sesión
                        </button>
                    </form>
                @else
                    <button type="button" @click="open = false; $dispatch('abrir-login')"
                            class="h-[46px] w-full cursor-pointer rounded-[4px] bg-[#002B56] text-[15px] font-semibold text-white transition hover:bg-[#0A2249]">
                        Ingresar
                    </button>
                @endauth
            </div>
        </aside>

        @guest('sitio')
            {{-- Ingreso en celular: el desplegable de computadora está dentro del menú
                 de escritorio, que en pantallas chicas no se ve. --}}
            <div x-data="{ abierto: {{ $errors->has('login') || $errors->has('password') || request()->boolean('ingresar') ? 'true' : 'false' }} }"
                 @keydown.escape.window="abierto = false"
                 @abrir-login.window="setTimeout(() => abierto = true)"
                 x-show="abierto" x-cloak x-transition.opacity
                 class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm xl:hidden">
                <div @click.outside="abierto = false"
                     class="relative w-full max-w-[360px] rounded-[10px] bg-white px-6 pb-5 pt-6 shadow-[0_12px_40px_rgba(13,43,94,.25)]">
                    <button type="button" @click="abierto = false" aria-label="Cerrar"
                            class="absolute right-3 top-3 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                    @include('partials.ingreso-form', ['prefijo' => 'mob'])
                </div>
            </div>
        @endguest
    </header>

    <main class="flex-1">
        {{ $slot }}
    </main>

    @if($wssp)
        <a href="https://wa.me/{{ preg_replace('/\D/', '', $wssp) }}" target="_blank" rel="noopener"
           class="fixed bottom-6 right-6 z-[9990] flex h-16 w-16 items-center justify-center rounded-full bg-[#25D366] shadow-[0_4px_12px_rgba(37,211,102,.45)] transition hover:scale-105"
           title="Contactar por WhatsApp" aria-label="Contactar por WhatsApp">
            <svg class="h-9 w-9 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
            </svg>
        </a>
    @endif

    @livewire('footer')

    @livewireScripts
</body>
</html>
