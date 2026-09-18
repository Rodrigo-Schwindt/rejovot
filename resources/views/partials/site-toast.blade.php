{{-- Toast global: escucha el evento Livewire "show-toast". --}}
<div
    x-data="{
        show: false,
        message: '',
        type: 'success',
        timer: null,
        lanzar(message, type) {
            this.message = message;
            this.type = type || 'success';
            this.show = true;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.show = false, 4000);
        },
        init() {
            Livewire.on('show-toast', (data) => {
                const payload = Array.isArray(data) ? data[0] : data;
                this.lanzar(payload.message ?? 'Listo', payload.type ?? 'success');
            });

            @if(session('toast'))
                this.lanzar(@js(session('toast')), @js(session('toast_type', 'success')));
            @endif
        }
    }"
    x-cloak
    x-show="show"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 -translate-y-3"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed top-5 left-1/2 z-[9999] -translate-x-1/2"
>
    <div class="flex min-w-[300px] max-w-[440px] items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-[0_16px_40px_rgba(13,43,94,.18)] ring-1 ring-black/5">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
              :class="type === 'error' ? 'bg-[#E11A22]' : 'bg-[#002B56]'">
            <svg x-show="type !== 'error'" class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="m5 13 4 4L19 7"/></svg>
            <svg x-show="type === 'error'" class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18 18 6M6 6l12 12"/></svg>
        </span>
        <p class="text-[14px] font-semibold text-slate-800" x-text="message"></p>
        <button type="button" @click="show = false" class="ml-auto text-slate-300 transition hover:text-slate-500" aria-label="Cerrar aviso">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
    </div>
</div>
