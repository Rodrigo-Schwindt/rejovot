@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn space-y-6">

    <h2 class="text-xl font-semibold text-slate-800">Cuentas bancarias</h2>
    <p class="-mt-4 text-sm text-slate-500">Son las que ve el cliente en <b>Info de pagos</b> para hacer el depósito.</p>

    @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="alert-error"><ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('admin.pagos.cuentas.save') }}" class="space-y-6">
        @csrf

        <div class="space-y-4 rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <span class="sec-label">Cuentas</span>
                <button type="button" id="add-cuenta" class="btn btn-primary btn-sm">+ Añadir cuenta</button>
            </div>
            <p class="-mt-2 text-sm text-slate-500">Los campos vacíos no se muestran en el sitio. Una cuenta sin ningún dato se descarta al guardar.</p>

            <div id="cuentas" class="space-y-4">
                @forelse($cuentas as $i => $cuenta)
                    @include('livewire.pagos.partials.cuenta-row', ['cuenta' => $cuenta, 'i' => $i])
                @empty
                    @include('livewire.pagos.partials.cuenta-row', ['cuenta' => null, 'i' => 0])
                @endforelse
            </div>
        </div>

        <template id="cuenta-template">
            @include('livewire.pagos.partials.cuenta-row', ['cuenta' => null])
        </template>

        <div class="flex justify-end">
            <button type="submit" class="btn btn-primary px-8">Guardar cambios</button>
        </div>
    </form>
</div>

<script>
    (function () {
        const container = document.getElementById('cuentas');
        const template = document.getElementById('cuenta-template');
        const addBtn = document.getElementById('add-cuenta');
        if (!container || !template || !addBtn) return;

        let indice = {{ max(count($cuentas), 1) }};

        addBtn.addEventListener('click', function () {
            const html = template.innerHTML.replaceAll('__INDEX__', indice++);
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            container.appendChild(wrapper.firstElementChild);
        });

        container.addEventListener('click', function (e) {
            if (!e.target.classList.contains('remove-cuenta')) return;
            if (container.querySelectorAll('.cuenta-item').length === 1) {
                e.target.closest('.cuenta-item').querySelectorAll('input').forEach(i => i.value = '');
                return;
            }
            e.target.closest('.cuenta-item').remove();
        });
    })();
</script>
@endsection
