{{-- Formulario de ingreso de clientes y vendedores (usuario de Odoo).
     Lo usan el desplegable de computadora y la ventana de celular. --}}
<h2 class="text-center text-[22px] font-bold leading-none text-slate-900">Iniciar sesión</h2>

@if($errors->has('login') || $errors->has('password'))
    <div class="mt-4 rounded-[4px] border border-red-200 bg-red-50 px-3 py-2 text-[12px] text-red-600">
        @foreach($errors->get('login') + $errors->get('password') as $error)<p>{{ $error }}</p>@endforeach
    </div>
@endif

<form method="POST" action="{{ route('ingresar.post') }}" class="mt-5 space-y-4">
    @csrf

    <div>
        <label for="{{ $prefijo }}-login" class="mb-1.5 block text-[14px] font-medium text-slate-700">Usuario</label>
        <input id="{{ $prefijo }}-login" x-ref="login" type="text" name="login" value="{{ old('login') }}" required
               autocomplete="username" inputmode="email"
               class="h-[42px] w-full rounded-[4px] border border-slate-300 px-3 text-[14px] text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#002B56]">
    </div>

    <div>
        <label for="{{ $prefijo }}-password" class="mb-1.5 block text-[14px] font-medium text-slate-700">Contraseña</label>
        <input id="{{ $prefijo }}-password" type="password" name="password" required autocomplete="current-password"
               class="h-[42px] w-full rounded-[4px] border border-slate-300 px-3 text-[14px] text-slate-800 outline-none transition focus:border-[#002B56]">
    </div>

    <div class="flex justify-center pt-1">
        <button type="submit"
                class="h-[42px] cursor-pointer rounded-[4px] bg-[#002B56] px-6 text-[13px] font-bold uppercase tracking-wide text-white transition hover:bg-[#0A2249]">
            Iniciar sesión
        </button>
    </div>
</form>

<div class="mt-5 border-t border-slate-200 pt-4 text-center">
    <a href="mailto:{{ $contactData?->mail_adm ?: 'ventas@rejovot.com.ar' }}?subject=Problemas%20para%20ingresar"
       class="text-[14px] text-slate-800 underline underline-offset-4 transition hover:text-[#002B56]">
        Problemas para ingresar
    </a>
    <p class="mt-1.5 text-[13px] text-slate-600">
        ¿No tenés cuenta?
        <a href="mailto:{{ $contactData?->mail_adm ?: 'ventas@rejovot.com.ar' }}?subject=Quiero%20registrarme"
           class="underline underline-offset-2 transition hover:text-[#002B56]">Registrate</a>
    </p>
</div>
