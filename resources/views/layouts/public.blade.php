<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.seo')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        :root {
            --rj-navy: #0D2B5E;
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
        $navItems = [
            ['label' => 'Productos', 'route' => 'productos'],
            ['label' => 'Búsqueda por vehículo', 'route' => 'vehiculos'],
            ['label' => 'Carrito', 'route' => 'carrito'],
            ['label' => 'Mis Pedidos', 'route' => 'pedidos'],
            ['label' => 'Lista de precios', 'route' => 'precios'],
            ['label' => 'Estado de la Cuenta', 'route' => 'cuenta'],
            ['label' => 'Info de Pagos', 'route' => 'pagos'],
            ['label' => 'Márgenes', 'route' => 'margenes'],
        ];
    @endphp

    @include('partials.site-toast')

    <header x-data="{ open: false }" @keydown.escape.window="open = false"
            class="relative z-30 w-full bg-white" style="box-shadow: 0 4px 7px rgba(0,0,0,.05);">
        <div class="mx-auto flex h-[92px] w-full max-w-[1300px] items-center justify-between gap-4 px-4 xl:px-6">

            <a wire:navigate href="{{ route('productos') }}" class="flex shrink-0 items-center" aria-label="Rejovot Autopartes">
                @if($contactData?->icono_1)
                    <img src="{{ Storage::url($contactData->icono_1) }}" alt="Rejovot Autopartes" class="h-[62px] w-auto object-contain">
                @else
                    @include('partials.logo', ['class' => 'h-[62px] w-auto'])
                @endif
            </a>

            <nav class="hidden items-center gap-[18px] xl:flex">
                @foreach($navItems as $item)
                    @php $activo = $item['route'] && request()->routeIs($item['route']); @endphp
                    <a href="{{ $item['route'] ? route($item['route']) : '#' }}"
                       @if($item['route']) wire:navigate @endif
                       class="nav-link text-[15px] leading-none text-[#101828] {{ $activo ? 'active text-[#0D2B5E]' : '' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach

                @auth('sitio')
                    <div class="relative ml-1 shrink-0" x-data="{ abierto: false }" @click.outside="abierto = false">
                        <button type="button" @click="abierto = !abierto"
                                class="inline-flex h-[42px] cursor-pointer items-center gap-2 whitespace-nowrap rounded-[4px] bg-[#0D2B5E] px-5 text-[15px] font-semibold text-white transition hover:bg-[#0A2249]">
                            {{ Str::limit(auth('sitio')->user()->name, 16) }}
                            <svg class="h-4 w-4 transition" :class="abierto && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
                        </button>

                        <div x-show="abierto" x-cloak x-transition.opacity.duration.100ms
                             class="absolute right-0 z-50 mt-1 w-[240px] rounded-[4px] border border-slate-200 bg-white py-1 shadow-[0_12px_32px_rgba(13,43,94,.22)]">
                            <p class="border-b border-slate-100 px-4 py-2.5 text-[13px] text-slate-500">
                                {{ auth('sitio')->user()->esVendedor() ? 'Vendedor' : 'Cliente' }}
                                <span class="block truncate text-slate-700">{{ auth('sitio')->user()->email }}</span>
                            </p>
                            <form method="POST" action="{{ route('salir') }}">
                                @csrf
                                <button type="submit" class="w-full cursor-pointer px-4 py-2.5 text-left text-[14px] text-slate-700 transition hover:bg-slate-50">
                                    Cerrar sesión
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('ingresar') }}"
                       class="ml-1 inline-flex h-[42px] shrink-0 items-center whitespace-nowrap rounded-[4px] bg-[#0D2B5E] px-5 text-[15px] font-semibold text-white transition hover:bg-[#0A2249]">
                        Ingresar
                    </a>
                @endauth
            </nav>

            <button type="button" @click="open = true"
                    class="flex h-11 w-11 flex-col items-end justify-center gap-[6px] xl:hidden"
                    aria-label="Abrir menú" :aria-expanded="open">
                <span class="block h-[3px] w-8 rounded bg-[#0D2B5E]"></span>
                <span class="block h-[3px] w-6 rounded bg-[#E11A22]"></span>
                <span class="block h-[3px] w-8 rounded bg-[#0D2B5E]"></span>
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

            <div class="flex items-center justify-between bg-[#0D2B5E] px-6 py-7">
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
                    @php $activo = $item['route'] && request()->routeIs($item['route']); @endphp
                    <a href="{{ $item['route'] ? route($item['route']) : '#' }}"
                       @click="open = false"
                       class="block rounded-lg px-4 py-3 text-[15px] font-medium transition
                              {{ $activo ? 'bg-[#0D2B5E] text-white shadow-md' : 'text-slate-700 hover:bg-slate-50 hover:text-[#0D2B5E]' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </aside>
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
