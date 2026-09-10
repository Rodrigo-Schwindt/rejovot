@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn space-y-6">

    <h2 class="text-xl font-semibold text-slate-800">Contacto y logos</h2>
    <p class="-mt-4 text-sm text-slate-500">Todo lo que se ve en el header y el footer del sitio. No depende de Odoo.</p>

    @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="alert-error"><ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('admin.contacto.save') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- Datos de contacto dinámicos --}}
        <div class="space-y-4 rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <span class="sec-label">Información de contacto</span>
                <button type="button" id="add-info-item" class="btn btn-primary btn-sm">+ Añadir dato</button>
            </div>
            <p class="-mt-2 text-sm text-slate-500">
                Agregá los datos que necesites (dirección, WhatsApp, teléfono, email). El <b>WhatsApp flotante</b> es fijo: se edita pero no se elimina.
            </p>

            <div id="info-items" class="space-y-3">
                @forelse($infoItems as $item)
                    @include('livewire.contacto.partials.info-item-row', ['item' => $item])
                @empty
                    @include('livewire.contacto.partials.info-item-row', ['item' => null])
                @endforelse
            </div>
        </div>

        <template id="info-item-template">
            @include('livewire.contacto.partials.info-item-row', ['item' => null])
        </template>

        {{-- Logos --}}
        <div class="space-y-4 rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
            <span class="sec-label">Logos del sitio</span>
            @php $iconTitles = [1 => 'Logo header', 2 => 'Logo panel / login', 3 => 'Logo footer']; @endphp
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                @foreach([1, 2, 3] as $i)
                    @php $icon = "icono_$i"; @endphp
                    <div class="flex flex-col items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                        <p class="text-center text-sm font-medium text-slate-600">{{ $iconTitles[$i] }}</p>

                        @if($contact?->$icon)
                            <img id="preview_icono_{{ $i }}" src="{{ Storage::url($contact->$icon) }}" alt="{{ $iconTitles[$i] }}"
                                 class="max-h-[100px] max-w-[160px] object-contain">
                        @else
                            <div id="preview_icono_{{ $i }}_placeholder" class="flex h-24 w-36 items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-white">
                                <svg class="h-8 w-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <img id="preview_icono_{{ $i }}" class="hidden max-h-[100px] max-w-[160px] object-contain" alt="{{ $iconTitles[$i] }}">
                        @endif

                        <input type="file" id="icono_{{ $i }}_temp" name="icono_{{ $i }}_temp" class="hidden" accept="image/*">
                        <div class="flex gap-2">
                            <button type="button" onclick="document.getElementById('icono_{{ $i }}_temp').click()" class="btn btn-primary btn-xs">+ Subir</button>
                            @if($contact?->$icon)
                                <button type="submit" name="remove_icono_{{ $i }}" value="1" class="btn btn-danger btn-xs"
                                        onclick="return confirm('¿Eliminar este logo?')">Eliminar</button>
                            @endif
                        </div>
                        <p class="upload-hint text-center">
                            <b>Fondo transparente*</b><br>
                            <b>Formatos:</b> PNG, SVG, WebP<br>
                            <b>Tamaño:</b> 400×200 px · <b>Máx:</b> 4 MB
                        </p>
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-slate-400">Si no cargás logos, el sitio usa el logo vectorial de respaldo.</p>
        </div>

        {{-- Redes --}}
        <div class="space-y-4 rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
            <span class="sec-label">Redes sociales</span>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                @foreach(['facebook' => 'Facebook', 'insta' => 'Instagram', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube'] as $key => $label)
                    <div>
                        <label class="f-label" for="rs_{{ $key }}">{{ $label }}</label>
                        <input type="url" id="rs_{{ $key }}" name="{{ $key }}" value="{{ old($key, $contact->$key ?? '') }}" class="f-input" placeholder="https://…">
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Mapa --}}
        <div class="space-y-3 rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
            <span class="sec-label">Mapa</span>
            <div>
                <label class="f-label" for="maps_adm">Link de Google Maps</label>
                <input type="text" id="maps_adm" name="maps_adm" value="{{ old('maps_adm', $contact->maps_adm ?? '') }}" class="f-input" placeholder="https://maps.google.com/…">
            </div>
            <div>
                <label class="f-label" for="frame_adm">Embed del mapa (iframe)</label>
                <textarea id="frame_adm" name="frame_adm" rows="4" class="f-textarea font-mono text-xs"
                          placeholder="&lt;iframe src=&quot;…&quot;&gt;&lt;/iframe&gt;">{{ old('frame_adm', $contact->frame_adm ?? '') }}</textarea>
            </div>
            @if($contact?->frame_adm)
                <div class="mt-2 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">{!! $contact->frame_adm !!}</div>
            @endif
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn btn-primary px-8">Guardar cambios</button>
        </div>
    </form>
</div>

<script>
    document.querySelectorAll('input[type=file][name^="icono_"]').forEach(function (input) {
        input.addEventListener('change', function (e) {
            if (!e.target.files.length) return;
            const num = input.id.match(/icono_(\d+)_temp/)?.[1];
            if (!num) return;
            const preview = document.getElementById('preview_icono_' + num);
            const placeholder = document.getElementById('preview_icono_' + num + '_placeholder');
            preview.src = URL.createObjectURL(e.target.files[0]);
            preview.classList.remove('hidden');
            if (placeholder) placeholder.classList.add('hidden');
        });
    });

    (function () {
        const container = document.getElementById('info-items');
        const template = document.getElementById('info-item-template');
        const addBtn = document.getElementById('add-info-item');
        if (!container || !template || !addBtn) return;

        addBtn.addEventListener('click', function () {
            container.appendChild(template.content.firstElementChild.cloneNode(true));
        });

        container.addEventListener('click', function (e) {
            if (!e.target.classList.contains('remove-info-item')) return;
            const row = e.target.closest('.info-item');
            if (row && row.dataset.fixed !== '1') row.remove();
        });
    })();
</script>
@endsection
