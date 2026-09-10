@php $contact = \App\Models\Contact::first(); @endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.seo')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-[#0D2B5E] px-4 py-10">

    <div class="w-full max-w-[440px]">
        <div class="mb-7 flex justify-center">
            @if($contact?->icono_2)
                <img src="{{ Storage::url($contact->icono_2) }}" alt="Rejovot Autopartes" class="h-20 w-auto object-contain">
            @else
                @include('partials.logo', ['variant' => 'blanco', 'class' => 'h-20 w-auto'])
            @endif
        </div>

        <div class="rounded-[4px] bg-white p-7 shadow-[0_20px_50px_rgba(7,27,61,.35)]">
            <h1 class="text-center text-[22px] font-bold text-slate-900">Ingresá a tu cuenta</h1>
            <p class="mt-1 text-center text-[14px] text-slate-500">
                Con el mismo usuario y contraseña que usás en el sistema.
            </p>

            @if($errors->any())
                <div class="mt-5 rounded-[4px] border border-red-200 bg-red-50 px-4 py-3 text-[14px] text-red-600">
                    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('ingresar.post') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label for="login" class="mb-1.5 block text-[14px] font-medium text-slate-700">Usuario o email</label>
                    <input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus
                           autocomplete="username" inputmode="email"
                           class="h-[46px] w-full rounded-[4px] border border-slate-300 px-3 text-[15px] text-slate-800 outline-none transition focus:border-[#0D2B5E]">
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-[14px] font-medium text-slate-700">Contraseña</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                           class="h-[46px] w-full rounded-[4px] border border-slate-300 px-3 text-[15px] text-slate-800 outline-none transition focus:border-[#0D2B5E]">
                </div>

                <label class="flex items-center gap-2 text-[14px] text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 accent-[#0D2B5E]">
                    Mantener la sesión iniciada
                </label>

                <button type="submit"
                        class="h-[48px] w-full cursor-pointer rounded-[4px] bg-[#0D2B5E] text-[15px] font-bold uppercase tracking-wide text-white transition hover:bg-[#0A2249]">
                    Ingresar
                </button>
            </form>

            <p class="mt-5 border-t border-slate-100 pt-4 text-center text-[13px] text-slate-500">
                ¿No tenés usuario? Escribinos a
                <a href="mailto:{{ $contact?->mail_adm ?: 'ventas@rejovot.com.ar' }}" class="font-semibold text-[#0D2B5E]">{{ $contact?->mail_adm ?: 'ventas@rejovot.com.ar' }}</a>
            </p>
        </div>

        <p class="mt-6 text-center text-[14px] text-white/70">
            <a href="{{ route('productos') }}" class="transition hover:text-white">&larr; Ver el catálogo</a>
        </p>
    </div>
</body>
</html>
