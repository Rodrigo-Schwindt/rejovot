{{-- Formulario de sliders, con vista previa en vivo. Usa $slider, $accion, $boton y $metodo (opcional). --}}
@php
    $maximo = \App\Models\Slider::MAXIMO_IMAGENES;
    $campos = [];

    foreach (['etiqueta', 'etiqueta_color', 'etiqueta_fondo', 'titulo', 'titulo_color', 'descripcion', 'descripcion_color', 'fondo'] as $campo) {
        $campos[$campo] = old($campo, $slider->{$campo}) ?? '';
    }

    $imagenes = array_pad($slider->imagenes_urls, $maximo, null);
@endphp

@if($errors->any())<div class="alert-error"><ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<form method="POST" action="{{ $accion }}" enctype="multipart/form-data" class="space-y-5"
      x-data="{
        f: @js($campos),
        previas: @js($imagenes),
        quitar: @js(array_fill(0, $maximo, false)),
        elegir(i, evento) {
            const archivo = evento.target.files[0];

            if (archivo) {
                this.previas[i] = URL.createObjectURL(archivo);
                this.quitar[i] = false;
            }
        },
        sacar(i) {
            this.previas[i] = null;
            this.quitar[i] = true;
            this.$refs['archivo' + i].value = '';
        },
      }">
    @csrf
    @isset($metodo) @method($metodo) @endisset

    {{-- Vista previa --}}
    <div>
        <p class="sec-label mb-2">Vista previa</p>
        <div class="relative overflow-hidden rounded-xl px-6 py-5 transition-colors duration-300" :style="`background-color: ${f.fondo}`">
            <span class="absolute inset-y-0 left-0 w-[6px]" :style="`background-color: ${f.etiqueta_fondo}`"></span>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="min-w-0">
                    <p class="mb-2 text-[12px]" :style="`color: ${f.descripcion_color}`">Inicio &gt; Productos</p>
                    <span x-show="f.etiqueta" x-text="f.etiqueta" class="mb-2 inline-block rounded-[3px] px-2.5 py-1 text-[12px] font-bold uppercase tracking-[.12em]"
                          :style="`background-color: ${f.etiqueta_fondo}; color: ${f.etiqueta_color}`"></span>
                    <p class="text-[22px] font-bold leading-[125%]" :style="`color: ${f.titulo_color}`" x-text="f.titulo || 'Título del slider'"></p>
                    <p class="mt-1 whitespace-pre-line text-[14px] leading-[150%]" :style="`color: ${f.descripcion_color}`" x-text="f.descripcion"></p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <template x-for="(url, i) in previas" :key="i">
                        <div x-show="url" class="flex h-[76px] w-[76px] items-center justify-center overflow-hidden rounded-[6px] bg-white p-1.5">
                            <img :src="url" alt="" class="h-full w-full object-contain">
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 rounded-xl border border-slate-100 bg-white p-5 shadow-sm md:grid-cols-2">

        <div class="md:col-span-2"><p class="sec-label">Etiqueta</p></div>
        <div class="md:col-span-2">
            <label class="f-label" for="etiqueta">Texto de la etiqueta</label>
            <input type="text" id="etiqueta" name="etiqueta" x-model="f.etiqueta" maxlength="40" class="f-input" placeholder="Nueva línea">
            <p class="f-hint">Opcional. Sin texto no se muestra la etiqueta.</p>
        </div>
        @include('livewire.sliders.partials.color', ['campo' => 'etiqueta_color', 'label' => 'Color del texto'])
        @include('livewire.sliders.partials.color', ['campo' => 'etiqueta_fondo', 'label' => 'Color de la etiqueta'])

        <div class="md:col-span-2"><p class="sec-label">Textos</p></div>
        <div class="md:col-span-2">
            <label class="f-label" for="titulo">Título</label>
            <input type="text" id="titulo" name="titulo" x-model="f.titulo" maxlength="120" class="f-input" required>
        </div>
        <div class="md:col-span-2">
            <label class="f-label" for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" x-model="f.descripcion" maxlength="400" rows="3" class="f-textarea"></textarea>
        </div>
        @include('livewire.sliders.partials.color', ['campo' => 'titulo_color', 'label' => 'Color del título'])
        <div>
            @include('livewire.sliders.partials.color', ['campo' => 'descripcion_color', 'label' => 'Color de la descripción'])
            <p class="f-hint">Las migas de pan y los puntos del carrusel toman este color.</p>
        </div>

        <div class="md:col-span-2"><p class="sec-label">Banner</p></div>
        @include('livewire.sliders.partials.color', ['campo' => 'fondo', 'label' => 'Color de fondo'])
        <div>
            <label class="f-label" for="sort_order">Orden</label>
            <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $slider->sort_order ?? 0) }}" class="f-input" required>
            <p class="f-hint">Los números más bajos aparecen primero.</p>
        </div>

        <div class="md:col-span-2">
            <p class="sec-label">Imágenes (hasta {{ $maximo }})</p>
            <p class="f-hint">JPG, PNG o WEBP, hasta 4 MB. Se muestran sobre un cuadrado blanco.</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-3 md:col-span-2">
            @for($i = 0; $i < $maximo; $i++)
                <div>
                    {{-- El input va oculto: lo abren el recuadro y el botón, que son labels suyos. --}}
                    <input type="file" id="archivo-{{ $i }}" name="imagenes[{{ $i }}]" accept=".jpg,.jpeg,.png,.webp" x-ref="archivo{{ $i }}"
                           @change="elegir({{ $i }}, $event)" class="peer sr-only">
                    <input type="hidden" name="quitar[{{ $i }}]" :value="quitar[{{ $i }}] ? 1 : 0">

                    <label for="archivo-{{ $i }}" title="Elegir imagen"
                           class="mb-2 flex h-[110px] cursor-pointer items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-slate-200 bg-slate-50 p-2 transition hover:border-[#002B56] hover:bg-blue-50/50 peer-focus-visible:border-[#002B56]"
                           :class="previas[{{ $i }}] && 'border-solid bg-white'">
                        <img x-show="previas[{{ $i }}]" :src="previas[{{ $i }}]" alt="" class="h-full w-full object-contain">
                        <span x-show="! previas[{{ $i }}]" class="flex flex-col items-center gap-1 text-xs font-medium text-slate-500">
                            <svg class="h-6 w-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/></svg>
                            Hacé clic para subir
                        </span>
                    </label>

                    <div class="flex items-center justify-between gap-2">
                        <label for="archivo-{{ $i }}" class="btn btn-ghost btn-sm cursor-pointer" x-text="previas[{{ $i }}] ? 'Cambiar imagen' : 'Subir imagen'"></label>
                        <button type="button" x-show="previas[{{ $i }}]" @click="sacar({{ $i }})" class="cursor-pointer text-xs font-medium text-red-600 hover:underline">
                            Quitar
                        </button>
                    </div>
                </div>
            @endfor
        </div>
    </div>

    <div class="flex justify-end gap-2">
        <a href="{{ route('admin.sliders.index') }}" class="btn btn-ghost">Cancelar</a>
        <button type="submit" class="btn btn-primary px-8">{{ $boton }}</button>
    </div>
</form>
