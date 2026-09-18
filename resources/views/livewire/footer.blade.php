<footer class="relative flex flex-col bg-[#002B56] text-white">

    <div class="flex-1 py-10 lg:py-14">
        <div class="mx-auto grid w-full max-w-[1300px] grid-cols-1 gap-10 px-4 text-center lg:grid-cols-[260px_240px_320px_minmax(0,1fr)] lg:gap-x-12 lg:text-left xl:px-6">

            <div>
                <a wire:navigate href="{{ route('productos') }}" class="inline-block" aria-label="Rejovot Autopartes">
                    @if($contactData?->icono_3)
                        <img src="{{ Storage::url($contactData->icono_3) }}" alt="Rejovot Autopartes" class="mx-auto h-[130px] w-auto object-contain lg:mx-0">
                    @else
                        @include('partials.logo', ['variant' => 'blanco', 'class' => 'mx-auto h-[120px] w-full max-w-[230px] lg:mx-0'])
                    @endif
                </a>
            </div>

            <div>
                <h3 class="mb-5 text-[20px] font-semibold leading-[120%] text-white">Secciones</h3>
                <ul class="grid grid-cols-2 gap-x-8 gap-y-2.5 text-[16px] leading-[150%]">
                    <li><a href="#" class="block transition hover:text-[#E11A22]">Nosotros</a></li>
                    <li><a href="#" class="block transition hover:text-[#E11A22]">Novedades</a></li>
                    <li><a wire:navigate href="{{ route('productos') }}" class="block transition hover:text-[#E11A22]">Productos</a></li>
                    <li><a href="#" class="block transition hover:text-[#E11A22]">Contacto</a></li>
                    <li><a href="#" class="block transition hover:text-[#E11A22]">Marcas</a></li>
                </ul>
            </div>

            <div>
                <h3 class="mb-5 text-[20px] font-semibold leading-[120%] text-white">Suscribite al Newsletter</h3>
                <livewire:newsletter-form />

                @if($hasSocialMedia)
                    <div class="mt-7">
                        <h3 class="mb-4 text-[18px] font-semibold uppercase leading-[120%] text-white">Seguinos en</h3>
                        <div class="flex items-center justify-center gap-3 text-[#E11A22] lg:justify-start">
                            @if($contactData->insta)
                                <a href="{{ $contactData->insta }}" target="_blank" rel="noopener" class="transition hover:opacity-70" aria-label="Instagram">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
                                    </svg>
                                </a>
                            @endif
                            @if($contactData->facebook)
                                <a href="{{ $contactData->facebook }}" target="_blank" rel="noopener" class="transition hover:opacity-70" aria-label="Facebook">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                                    </svg>
                                </a>
                            @endif
                            @if($contactData->linkedin)
                                <a href="{{ $contactData->linkedin }}" target="_blank" rel="noopener" class="transition hover:opacity-70" aria-label="LinkedIn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/>
                                        <rect x="2" y="9" width="4" height="12"/>
                                        <circle cx="4" cy="4" r="2"/>
                                    </svg>
                                </a>
                            @endif
                            @if($contactData->youtube)
                                <a href="{{ $contactData->youtube }}" target="_blank" rel="noopener" class="transition hover:opacity-70" aria-label="YouTube">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58 2.78 2.78 0 0 0 1.95 1.96C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/>
                                        <polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <div>
                <h3 class="mb-5 text-[20px] font-semibold leading-[120%] text-white">Información de Contacto</h3>
                <div class="mx-auto flex w-full flex-col items-center gap-4 lg:items-start">
                    @forelse(($contactData?->infoItems ?? collect()) as $item)
                        @include('partials.contact-info-item', ['item' => $item, 'dark' => true])
                    @empty
                        <p class="text-[14px] text-white/60">Cargá los datos desde el panel administrativo.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class=" bg-black/25 py-5">
        <div class="mx-auto flex w-full max-w-[1300px] flex-col items-center justify-between gap-2 px-4 text-center lg:flex-row lg:text-left xl:px-6">
            <p class="text-[13px] text-white/80">
                © Copyright {{ date('Y') }} <strong>REJOVOT</strong>. Todos los derechos reservados
            </p>
            <a href="https://osole.com.ar" target="_blank" rel="noopener" class="text-[13px] text-white/80 transition hover:text-white">
                by <strong>Osole</strong>
            </a>
        </div>
    </div>
</footer>
