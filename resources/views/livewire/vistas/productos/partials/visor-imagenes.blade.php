{{--
    Visor de imágenes a pantalla completa, compartido por toda la página.
    Se abre desde cualquier imagen con:
        $dispatch('abrir-visor', { imagenes: [...urls], actual: 0, titulo: 'CÓDIGO' })
--}}
<div x-data="{
        abierto: false,
        imagenes: [],
        actual: 0,
        titulo: '',
        abrir(d) { this.imagenes = d.imagenes || []; this.actual = d.actual || 0; this.titulo = d.titulo || ''; this.abierto = this.imagenes.length > 0; },
        ir(i) { this.actual = (i + this.imagenes.length) % this.imagenes.length; },
     }"
     x-effect="document.body.style.overflow = abierto ? 'hidden' : ''"
     @abrir-visor.window="abrir($event.detail)"
     @keydown.escape.window="abierto = false"
     @keydown.arrow-right.window="abierto && ir(actual + 1)"
     @keydown.arrow-left.window="abierto && ir(actual - 1)">

    <template x-teleport="body">
        <div x-show="abierto" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/85 backdrop-blur-sm"
             role="dialog" aria-modal="true" :aria-label="'Imágenes de ' + titulo"
             @click.self="abierto = false">

            <p x-show="titulo" class="absolute left-5 top-5 text-[15px] font-semibold tracking-wide text-white/80" x-text="titulo"></p>

            <button type="button" @click="abierto = false"
                    class="absolute right-4 top-4 flex h-11 w-11 cursor-pointer items-center justify-center rounded-full text-white/80 transition hover:bg-white/10 hover:text-white"
                    aria-label="Cerrar">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>

            <template x-if="imagenes.length > 1">
                <div>
                    <button type="button" @click.stop="ir(actual - 1)"
                            class="absolute left-3 top-1/2 flex h-12 w-12 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full text-white/80 transition hover:bg-white/10 hover:text-white md:left-6"
                            aria-label="Imagen anterior">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m15 19-7-7 7-7"/></svg>
                    </button>
                    <button type="button" @click.stop="ir(actual + 1)"
                            class="absolute right-3 top-1/2 flex h-12 w-12 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full text-white/80 transition hover:bg-white/10 hover:text-white md:right-6"
                            aria-label="Imagen siguiente">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/></svg>
                    </button>
                </div>
            </template>

            <div class="relative h-[80vh] w-[min(90vw,1100px)]" @click.self="abierto = false">
                <template x-for="(src, i) in imagenes" :key="src + i">
                    <img x-show="actual === i"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 scale-[.98]"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         :src="src" :alt="titulo + ' ' + (i + 1)" draggable="false"
                         class="absolute inset-0 m-auto max-h-full max-w-full select-none object-contain">
                </template>
            </div>

            <p x-show="imagenes.length > 1" class="absolute bottom-5 left-1/2 -translate-x-1/2 text-[14px] tracking-wide text-white/70">
                <span x-text="actual + 1"></span> / <span x-text="imagenes.length"></span>
            </p>
        </div>
    </template>
</div>
