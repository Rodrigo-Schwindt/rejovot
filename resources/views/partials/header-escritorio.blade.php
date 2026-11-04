{{--
    Header de escritorio (xl+), según el diseño:
    - Arriba, con una línea abajo: «Ingresar» o, con sesión, CUIT · descuento ·
      vendedor · NOMBRE (al tocarlo, el desplegable con el resto de las secciones).
    - Abajo: Productos, Búsqueda por marca, Mis Pedidos y el botón Carrito.
      Sin sesión, los privados se ven en gris y abren el ingreso.
--}}
@php

    $principales = [
        ['label' => 'Productos', 'route' => 'productos', 'activo' => request()->routeIs('producto') && ! $desdeVehiculos],
        ['label' => 'Búsqueda por marca', 'route' => 'vehiculos', 'activo' => $desdeVehiculos, 'privada' => true],
        ['label' => 'Mis Pedidos', 'route' => 'pedidos', 'privada' => true],
    ];

    $desplegable = [
        ['label' => 'Lista de precios', 'route' => 'precios'],
        ['label' => 'Estado de la cuenta', 'route' => 'cuenta'],
        ['label' => 'Info de pagos', 'route' => 'pagos'],
        ['label' => 'Márgenes', 'route' => 'margenes'],
        ['label' => 'Garantías', 'route' => 'reclamos', 'patron' => 'reclamos*'],
    ];
@endphp

<div class="hidden min-w-0 flex-1 flex-col xl:flex">

    {{-- Fila de arriba --}}
    <div class="flex min-h-[36px] items-center justify-end gap-6 border-b border-[#D9D9D9] pb-2 text-[14px] text-[#101828]">
        @auth('sitio')
            @if($clienteHeader)
                @if($clienteHeader->vat)<span class="whitespace-nowrap">CUIT {{ $clienteHeader->vat }}</span>@endif
                @if($descuentoHeader > 0)
                    <span class="whitespace-nowrap">{{ rtrim(rtrim(number_format($descuentoHeader, 2, ',', '.'), '0'), ',') }}% de descuento</span>
                @endif
                @if($clienteHeader->salesperson)<span class="whitespace-nowrap">{{ $clienteHeader->salesperson->name }}</span>@endif
            @elseif($usuarioSitio->esVendedor())
                <span class="whitespace-nowrap text-slate-400">Elegí un cliente para operar</span>
            @endif

            {{-- Nombre: abre el desplegable con el resto de las secciones --}}
            <div class="relative min-w-0" x-data="{ abierto: false }" @click.outside="abierto = false" @keydown.escape.window="abierto = false">
                <button type="button" @click="abierto = !abierto" :aria-expanded="abierto"
                        class="flex max-w-[480px] cursor-pointer items-center gap-1.5 font-bold uppercase text-[#101828] transition hover:text-[#002B56]"
                        title="{{ $nombreHeader }}">
                    <span class="truncate">{{ $nombreHeader }}</span>
                    <svg class="h-4 w-4 shrink-0 transition" :class="abierto && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
                </button>

                <div x-show="abierto" x-cloak x-transition.opacity.duration.100ms
                     class="absolute right-0 z-50 mt-2 w-[290px] rounded-[4px] bg-white px-4 py-3 shadow-[0_12px_32px_rgba(13,43,94,.22)]">
                    <p class="border-b border-slate-200 pb-3 text-[16px] font-bold uppercase leading-[130%] text-[#002B56]">{{ $nombreHeader }}</p>

                    {{-- Vendedor: elige para qué cliente de su cartera opera --}}
                    @if($usuarioSitio->esVendedor())
                        <form method="POST" action="{{ route('cliente.elegir') }}" class="mt-3">
                            @csrf
                            <label for="hdr-cliente" class="sr-only">Cliente</label>
                            <select id="hdr-cliente" name="cliente" onchange="this.form.submit()"
                                    class="h-[40px] w-full cursor-pointer rounded-[4px] border border-slate-300 bg-white px-2 text-[14px] text-slate-800 outline-none focus:border-[#002B56]">
                                <option value="" @selected(! $clienteHeader)>{{ $carteraHeader->isEmpty() ? 'No tenés clientes asignados' : 'Cliente' }}</option>
                                @foreach($carteraHeader as $c)
                                    <option value="{{ $c->id }}" @selected($clienteHeader?->id === $c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    @endif

                    <nav class="mt-2 flex flex-col">
                        @foreach($desplegable as $item)
                            <a wire:navigate href="{{ route($item['route']) }}"
                               class="py-1.5 text-[15px] transition hover:text-[#002B56] {{ request()->routeIs($item['patron'] ?? $item['route']) ? 'font-semibold text-[#002B56]' : 'text-slate-700' }}">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                        <form method="POST" action="{{ route('salir') }}">
                            @csrf
                            <button type="submit" class="w-full cursor-pointer py-1.5 text-left text-[15px] text-slate-700 transition hover:text-[#002B56]">Cerrar sesión</button>
                        </form>
                    </nav>
                </div>
            </div>
        @else
            {{-- Ingreso de clientes / vendedores: desplegable debajo de «Ingresar» --}}
            <div class="relative"
                 x-data="{ abierto: {{ $errors->has('login') || $errors->has('password') || request()->boolean('ingresar') ? 'true' : 'false' }} }"
                 @click.outside="abierto = false" @keydown.escape.window="abierto = false"
                 {{-- setTimeout: el mismo click que abre el modal dispara el click.outside,
                      así que se abre recién cuando ese click terminó de propagarse. --}}
                 @abrir-login.window="setTimeout(() => { abierto = true; $nextTick(() => $refs.login?.focus()) })">
                <button type="button" @click="abierto = !abierto; $nextTick(() => abierto && $refs.login.focus())"
                        class="cursor-pointer font-bold text-[#101828] transition hover:text-[#002B56]" :aria-expanded="abierto">
                    Ingresar
                </button>

                <div x-show="abierto" x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="-translate-y-1 opacity-0"
                     x-transition:enter-end="translate-y-0 opacity-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="translate-y-0 opacity-100"
                     x-transition:leave-end="-translate-y-1 opacity-0"
                     class="absolute right-0 z-50 mt-2 w-[300px] rounded-[10px] bg-white px-6 pb-5 pt-6 shadow-[0_12px_40px_rgba(13,43,94,.25)]">
                    @include('partials.ingreso-form', ['prefijo' => 'hdr'])
                </div>
            </div>
        @endauth
    </div>

    {{-- Fila de abajo --}}
    <nav class="flex items-center justify-end gap-6 pt-3">
        @foreach($principales as $item)
            @php $activo = ($item['activo'] ?? false) || request()->routeIs($item['route']); @endphp
            @if($bloqueada($item))
                <button type="button" x-data @click="$dispatch('abrir-login')"
                        title="Ingresá con tu usuario para ver {{ $item['label'] }}"
                        class="shrink-0 cursor-pointer text-[15px] leading-none text-slate-400">
                    {{ $item['label'] }}
                </button>
            @else
                <a wire:navigate href="{{ route($item['route']) }}"
                   class="nav-link shrink-0 text-[15px] leading-none text-[#101828] {{ $activo ? 'active text-[#002B56]' : '' }}">
                    {{ $item['label'] }}
                </a>
            @endif
        @endforeach

        @if($soloConSesion)
            <button type="button" x-data @click="$dispatch('abrir-login')" title="Ingresá con tu usuario para ver el carrito"
                    class="inline-flex h-[42px] cursor-pointer items-center rounded-[4px] bg-slate-200 px-5 text-[15px] font-semibold text-slate-400">
                Carrito
            </button>
        @else
            <a wire:navigate href="{{ route('carrito') }}"
               class="inline-flex h-[42px] items-center gap-2 rounded-[4px] bg-[#002B56] px-5 text-[15px] font-semibold text-white transition hover:bg-[#0A2249]">
                Carrito
                @include('partials.contador-carrito')
            </a>
        @endif
    </nav>
</div>
