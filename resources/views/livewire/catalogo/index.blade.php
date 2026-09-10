@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn space-y-6">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Productos</h2>
            <p class="mt-1 text-sm text-slate-500">
                El catálogo se sincroniza desde Odoo. Acá se decide qué se muestra en el sitio y qué va al banner.
                Por defecto se listan sólo los productos que hoy se ven en el sitio.
            </p>
        </div>

        @if($totales['destacados'] > 0)
            <form method="POST" action="{{ route('admin.catalogo.destacados.limpiar') }}"
                  onsubmit="return confirm('¿Quitar los {{ $totales['destacados'] }} productos del banner?')">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm">Vaciar el banner</button>
            </form>
        @endif
    </div>

    @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

    {{-- Resumen --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        @php
            $tarjetas = [
                ['Todo el catálogo', $totales['total'], 'estado=todos', ''],
                ['En el sitio', $totales['publicados'], 'estado=publicados', 'text-green-700'],
                ['Ocultos', $totales['ocultos'], 'estado=ocultos', 'text-slate-600'],
                ['En oferta', $totales['ofertas'], 'oferta=1', 'text-[#E11A22]'],
                ['En el banner', $totales['destacados'], 'estado=todos&destacado=1', 'text-[#0D2B5E]'],
            ];
        @endphp

        @foreach($tarjetas as [$titulo, $valor, $filtro, $color])
            <a href="{{ route('admin.catalogo.index') . '?' . $filtro }}"
               class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm transition hover:border-slate-300">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $titulo }}</p>
                <p class="mt-1 text-2xl font-bold {{ $color ?: 'text-slate-800' }}">{{ number_format($valor, 0, ',', '.') }}</p>
            </a>
        @endforeach
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('admin.catalogo.index') }}" class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label class="f-label" for="q">Buscar</label>
                <input type="text" id="q" name="q" value="{{ $filtros['q'] }}" class="f-input"
                       placeholder="Código, descripción o código OEM">
            </div>

            <div>
                <label class="f-label" for="marca">Marca</label>
                <select id="marca" name="marca" class="f-input">
                    <option value="">Todas</option>
                    @foreach($marcas as $marca)
                        <option value="{{ $marca }}" @selected($filtros['marca'] === $marca)>{{ $marca }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="f-label" for="rubro">Rubro</label>
                <select id="rubro" name="rubro" class="f-input">
                    <option value="">Todos</option>
                    @foreach($rubros as $rubro)
                        <option value="{{ $rubro }}" @selected($filtros['rubro'] === $rubro)>{{ $rubro }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="f-label" for="estado">Estado</label>
                <select id="estado" name="estado" class="f-input">
                    <option value="publicados" @selected($filtros['estado'] === 'publicados')>Se muestran en el sitio</option>
                    <option value="ocultos" @selected($filtros['estado'] === 'ocultos')>Ocultados a mano</option>
                    <option value="sin_web" @selected($filtros['estado'] === 'sin_web')>Sin publicar en Odoo</option>
                    <option value="archivados" @selected($filtros['estado'] === 'archivados')>Archivados en Odoo</option>
                    <option value="todos" @selected($filtros['estado'] === 'todos')>Todo el catálogo</option>
                </select>
            </div>

            <div>
                <label class="f-label" for="stock">Stock</label>
                <select id="stock" name="stock" class="f-input">
                    <option value="">Todos</option>
                    <option value="con" @selected($filtros['stock'] === 'con')>Con stock</option>
                    <option value="sin" @selected($filtros['stock'] === 'sin')>Sin stock</option>
                </select>
            </div>

            <div class="flex items-end gap-5">
                <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="oferta" value="1" @checked($filtros['oferta'])
                           class="h-4 w-4 rounded border-slate-300 accent-[#E11A22]">
                    En oferta
                </label>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="destacado" value="1" @checked($filtros['destacado'])
                           class="h-4 w-4 rounded border-slate-300 accent-[#0D2B5E]">
                    En el banner
                </label>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ route('admin.catalogo.index') }}" class="btn btn-ghost">Limpiar</a>
            </div>
        </div>
    </form>

    {{-- Listado --}}
    <div class="overflow-x-auto rounded-xl border border-slate-100 bg-white shadow-sm">
        <table class="w-full min-w-[900px] text-sm text-slate-700">
            <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase text-slate-400">
                <tr>
                    <th class="w-[70px] px-4 py-3"><span class="sr-only">Imagen</span></th>
                    <th class="px-4 py-3 text-left">Producto</th>
                    <th class="px-4 py-3 text-left">Marca / Rubro</th>
                    <th class="px-4 py-3 text-right">Precio lista</th>
                    <th class="px-4 py-3 text-center">Stock</th>
                    <th class="px-4 py-3 text-center">Estado</th>
                    <th class="px-4 py-3 text-center">Banner</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($productos as $producto)
                    <tr class="transition hover:bg-slate-50/60">
                        <td class="px-4 py-3">
                            <img src="{{ $producto->imagen_url }}" alt="" loading="lazy"
                                 class="h-11 w-11 rounded border border-slate-100 object-contain"
                                 onerror="this.style.visibility='hidden'">
                        </td>

                        <td class="px-4 py-3">
                            <span class="font-medium text-[#2563C9]">{{ $producto->code ?: '—' }}</span>
                            <span class="block max-w-[380px] text-xs text-slate-600">{{ Str::limit($producto->name, 80) }}</span>
                            @if($producto->en_oferta)
                                <span class="mt-1 inline-flex items-center rounded bg-red-50 px-2 py-0.5 text-[11px] font-bold uppercase text-[#E11A22]">
                                    Oferta {{ rtrim(rtrim(number_format((float) $producto->discount_percent, 2, ',', '.'), '0'), ',') }}%
                                    @if($producto->discount_to) · hasta {{ $producto->discount_to->format('d/m/Y') }} @endif
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-xs text-slate-500">
                            {{ $producto->brand?->name ?: '—' }}
                            <span class="block">{{ $producto->category?->name ?: '—' }}</span>
                        </td>

                        <td class="px-4 py-3 text-right font-medium text-slate-800">
                            {{ \App\Support\Precio::ar($producto->list_price) }}
                        </td>

                        <td class="px-4 py-3 text-center">
                            @php
                                $color = match ($producto->semaforo) {
                                    'verde' => 'bg-[#2FBF4B]',
                                    'amarillo' => 'bg-[#F2C438]',
                                    default => 'bg-[#E11A22]',
                                };
                            @endphp
                            <span class="mx-auto block h-[11px] w-[11px] rounded-full {{ $color }}" title="{{ (float) $producto->stock }} unidades"></span>
                            <span class="mt-1 block text-[11px] text-slate-400">{{ (float) $producto->stock }}</span>
                        </td>

                        <td class="px-4 py-3 text-center">
                            @if(! $producto->active)
                                <span class="inline-flex rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-500">Archivado</span>
                            @elseif(! $producto->published)
                                <span class="inline-flex rounded bg-amber-100 px-2 py-0.5 text-xs text-amber-700">Sin publicar</span>
                            @else
                                <form method="POST" action="{{ route('admin.catalogo.ocultar', $producto) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="cursor-pointer rounded px-2 py-1 text-xs font-medium transition {{ $producto->oculto ? 'bg-slate-100 text-slate-600 hover:bg-slate-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }}">
                                        {{ $producto->oculto ? 'Oculto' : 'Visible' }}
                                    </button>
                                </form>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-center">
                            {{-- Al banner sólo puede subir lo que hoy se ve en el sitio. --}}
                            @if($producto->active && $producto->published && ! $producto->oculto)
                                <form method="POST" action="{{ route('admin.catalogo.destacar', $producto) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" title="{{ $producto->destacado ? 'Quitar del banner' : 'Poner en el banner' }}"
                                            class="cursor-pointer text-xl leading-none transition {{ $producto->destacado ? 'text-[#E11A22]' : 'text-slate-300 hover:text-slate-400' }}">
                                        {{ $producto->destacado ? '★' : '☆' }}
                                    </button>
                                </form>
                            @else
                                <span class="text-slate-300" title="No se muestra en el sitio">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500">Ningún producto coincide con esos filtros</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $productos->links() }}</div>
</div>
@endsection
