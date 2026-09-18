@php use App\Support\Precio; @endphp

{{-- Saldos del encabezado --}}
<div class="mb-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:max-w-[820px]">

    <div class="anim-entrada rounded-[4px] border border-[#D9D9D9] bg-[#F8F8F8] px-6 py-5" style="--retraso: 120">
        <h2 class="text-[24px] font-bold leading-[130%] text-[#E11A22]">Saldo vencido</h2>
        <p class="mt-1 text-[20px] text-[#E11A22]">{{ Precio::ar($saldos['vencido']) }}</p>
    </div>

    <div class="anim-entrada rounded-[4px] border border-[#D9D9D9] bg-[#F8F8F8] px-6 py-5" style="--retraso: 190">
        <h2 class="text-[24px] font-bold leading-[130%] text-black">Saldo total</h2>
        <p class="mt-1 text-[20px] text-black">{{ Precio::ar($saldos['total']) }}</p>
    </div>
</div>
