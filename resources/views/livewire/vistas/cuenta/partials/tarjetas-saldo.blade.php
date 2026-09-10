@php use App\Support\Precio; @endphp

{{-- Saldos del encabezado --}}
<div class="mb-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:max-w-[860px]">

    <div class="rounded-[4px] border border-slate-200 bg-white px-6 py-5">
        <h2 class="text-[22px] font-bold text-[#E11A22]">Saldo vencido</h2>
        <p class="mt-1 text-[18px] text-[#E11A22]">{{ Precio::ar($saldos['vencido']) }}</p>
    </div>

    <div class="rounded-[4px] border border-slate-200 bg-white px-6 py-5">
        <h2 class="text-[22px] font-bold text-slate-900">Saldo total</h2>
        <p class="mt-1 text-[18px] text-slate-700">{{ Precio::ar($saldos['total']) }}</p>
    </div>
</div>
