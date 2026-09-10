<div>
    <form wire:submit.prevent="subscribe" class="mx-auto w-full max-w-[290px] lg:mx-0">
        <label for="newsletter-email" class="sr-only">Email para el newsletter</label>
        <div class="relative">
            <input id="newsletter-email" type="email" wire:model.blur="email" placeholder="Email"
                   class="h-[46px] w-full border border-white/40 bg-transparent pl-4 pr-12 text-[14px] text-white outline-none transition-colors placeholder:text-white/60 focus:border-[#E11A22] @error('email') border-red-400 @enderror">
            <button type="submit" wire:loading.attr="disabled" wire:target="subscribe"
                    class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 cursor-pointer items-center justify-center text-[#E11A22] transition hover:text-white disabled:opacity-50"
                    aria-label="Suscribirme al newsletter">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m0 0-6-6m6 6-6 6"/>
                </svg>
            </button>
        </div>
        @error('email')<p class="mt-2 text-[12px] text-red-300">{{ $message }}</p>@enderror
    </form>
</div>
