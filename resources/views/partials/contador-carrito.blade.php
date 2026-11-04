{{--
    Unidades en el carrito activo ($cantidadCarrito, del layout). Se actualiza
    con el evento «carrito-cantidad» que mandan las pantallas que lo cambian.
--}}
<span x-data="{ cantidad: {{ $cantidadCarrito }} }"
      @carrito-cantidad.window="cantidad = $event.detail.cantidad"
      x-show="cantidad > 0" x-text="cantidad"
      @if($cantidadCarrito <= 0) style="display: none" @endif
      aria-label="unidades en el carrito"
      class="{{ $clase ?? '' }} inline-flex h-[22px] min-w-[22px] items-center justify-center rounded-full bg-[#E11A22] px-1.5 text-[12px] font-bold leading-none text-white">{{ $cantidadCarrito }}</span>
