@php $contact = \App\Models\Contact::first(); @endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.seo')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-[#002B56] px-4 py-10">

    <div class="w-full max-w-[420px]">
        <div class="mb-7 flex justify-center">
            @if($contact?->icono_2)
                <img src="{{ Storage::url($contact->icono_2) }}" alt="Rejovot" class="h-20 w-auto object-contain">
            @else
                @include('partials.logo', ['variant' => 'blanco', 'class' => 'h-20 w-auto'])
            @endif
        </div>

        <div class="rounded-xl bg-white p-7 shadow-[0_20px_50px_rgba(7,27,61,.35)]">
            <h1 class="text-center text-xl font-semibold text-slate-800">Panel administrativo</h1>
            <p class="mt-1 text-center text-sm text-slate-400">Ingresá con tu usuario</p>

            @if($errors->any())
                <div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
                    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-[#002B56] focus:ring-3 focus:ring-[#002B56]/15">
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Contraseña</label>
                    <input id="password" type="password" name="password" required
                           class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-[#002B56] focus:ring-3 focus:ring-[#002B56]/15">
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-[#002B56]">
                    Mantener sesión iniciada
                </label>

                <button type="submit"
                        class="w-full cursor-pointer rounded-lg bg-[#002B56] py-2.5 text-sm font-semibold text-white transition hover:bg-[#071B3D]">
                    Ingresar
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-sm text-white/70">
            <a href="{{ route('productos') }}" class="transition hover:text-white">← Volver al sitio</a>
        </p>
    </div>
</body>
</html>
