@php
    use App\Models\Contact;
    $contact = Contact::first();
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.seo')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        :root {
            --ap: #002B56;          /* azul institucional */
            --as: #2A5CA8;          /* azul claro (íconos) */
            --aa: #E11A22;          /* rojo institucional */
            --ap-light: rgba(13,43,94,.12);
            --sidebar-bg: #002B56;
            --sidebar-bg-deep: #071B3D;
            --sidebar-border: rgba(255,255,255,.10);
            --sidebar-text: #F4F7FC;
            --sidebar-muted: #B9C6DC;
        }

        @@keyframes fadeIn { from { opacity:0; transform:translateY(8px) } to { opacity:1; transform:translateY(0) } }
        .animate-fadeIn { animation: fadeIn .35s ease; }

        [x-cloak] { display:none!important; }

        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background:rgba(225,26,34,.65); border-radius:10px; }

        /* ── sidebar ── */
        .nav-item {
            display:flex; align-items:center; gap:10px;
            padding:9px 14px; border-radius:8px;
            font-size:.875rem; font-weight:500; transition:all .18s;
        }
        #admin-sidebar {
            background:linear-gradient(180deg, var(--sidebar-bg) 0%, var(--sidebar-bg-deep) 100%);
            border-right:1px solid rgba(225,26,34,.28);
            color:var(--sidebar-text);
            box-shadow:18px 0 48px rgba(7,27,61,.18);
        }
        #admin-sidebar .sidebar-logo {
            background:linear-gradient(180deg, rgba(255,255,255,.05), rgba(255,255,255,.015));
            border-color:var(--sidebar-border);
        }
        #admin-sidebar .nav-item { color:var(--sidebar-muted) !important; border:1px solid transparent; }
        #admin-sidebar .nav-item svg { color:#8FB2E6 !important; transition:color .18s; }
        #admin-sidebar .nav-item:hover:not(.active-link) {
            color:var(--sidebar-text) !important;
            background:rgba(255,255,255,.08);
            border-color:rgba(255,255,255,.10);
            transform:translateX(2px);
        }
        #admin-sidebar .active-link {
            background:linear-gradient(135deg, #E11A22, #B8141B);
            color:#fff !important;
            border-color:rgba(255,255,255,.14);
            box-shadow:0 8px 22px rgba(225,26,34,.28);
        }
        #admin-sidebar .active-link svg { color:#fff !important; }
        .sidebar-divider { background:linear-gradient(90deg, transparent, rgba(225,26,34,.45), transparent) !important; }
        #admin-sidebar .sidebar-logout { border-color:var(--sidebar-border); background:rgba(0,0,0,.12); }
        .admin-mobile-header {
            background:rgba(13,43,94,.97) !important;
            border-color:rgba(225,26,34,.3) !important;
            backdrop-filter:blur(12px);
        }

        /* ── botones ── */
        .btn {
            display:inline-flex; align-items:center; gap:.5rem;
            border-radius:.5rem; font-size:.875rem; font-weight:500;
            cursor:pointer; transition:all .15s; border:none;
            padding:.5rem 1.25rem; text-decoration:none; white-space:nowrap;
        }
        .btn-primary { background:linear-gradient(135deg, var(--ap), #163d80); color:#fff; box-shadow:0 2px 6px rgba(13,43,94,.22); }
        .btn-primary:hover { filter:brightness(1.1); }
        .btn-ghost { background:#f8fafc; color:#475569; border:1px solid #e2e8f0; }
        .btn-ghost:hover { background:#f1f5f9; }
        .btn-danger { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
        .btn-danger:hover { background:#fee2e2; }
        .btn-sm { padding:.375rem .875rem; font-size:.8125rem; border-radius:.4rem; }
        .btn-xs { padding:.25rem .625rem; font-size:.75rem; border-radius:.35rem; }
        .btn:disabled, .btn[disabled] { opacity:.55; cursor:not-allowed; pointer-events:none; }

        /* ── formularios ── */
        .f-input, .f-select, .f-textarea {
            width:100%; border:1px solid #e2e8f0; border-radius:.5rem;
            padding:.5rem .75rem; font-size:.875rem; background:#fff;
            color:#0f172a; transition:border-color .15s, box-shadow .15s;
        }
        .f-input:focus, .f-select:focus, .f-textarea:focus {
            outline:none; border-color:var(--ap); box-shadow:0 0 0 3px var(--ap-light);
        }
        .f-input.f-input-search { padding-left:2.5rem; padding-right:2.5rem; }
        .f-label { display:block; font-size:.875rem; font-weight:500; color:#374151; margin-bottom:.35rem; }
        .f-hint { font-size:.75rem; color:#94a3b8; margin-top:.35rem; }
        .f-error { font-size:.75rem; color:#dc2626; margin-top:.25rem; }

        .upload-zone {
            border:2px dashed #c7d7ef; border-radius:.75rem;
            background:linear-gradient(135deg,#f5f8ff,#fff);
            padding:1.5rem; text-align:center; cursor:pointer; transition:all .2s;
        }
        .upload-zone:hover { border-color:var(--ap); background:#eef3fc; }
        .upload-zone svg { color:#9dbaea; margin:0 auto .5rem; }
        .upload-zone p { font-size:.875rem; color:#9ca3af; }
        .upload-hint { font-size:.7rem; color:#94a3b8; margin-top:.5rem; line-height:1.7; }
        .upload-hint b { color:#64748b; }

        .sec-label { font-size:.7rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.07em; }

        .tbl-edit { color:#002B56; transition:color .15s; }
        .tbl-edit:hover { color:#071B3D; }
        .tbl-del { color:#ef4444; transition:color .15s; }
        .tbl-del:hover { color:#b91c1c; }

        .alert-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; padding:.75rem 1rem; border-radius:.5rem; font-size:.875rem; }
        .alert-error { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; padding:.75rem 1rem; border-radius:.5rem; font-size:.875rem; }

        /* Tailwind v4 usa `translate`, no `transform` */
        #admin-sidebar { translate: -100% 0; }
        @@media (min-width:1024px) {
            #admin-sidebar { translate: 0 0 !important; display:flex !important; }
            #sidebar-backdrop { display:none !important; }
        }
    </style>
</head>
<body class="bg-slate-50" x-data="{ sidebarOpen: false }">

    <div id="sidebar-backdrop" x-show="sidebarOpen" x-transition.opacity
         @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

    <aside id="admin-sidebar" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col shadow-xl transition-transform duration-200">

        <div class="sidebar-logo flex shrink-0 items-center justify-center border-b px-5 py-4">
            <a href="{{ route('productos') }}">
                @if($contact?->icono_2)
                    <img src="{{ Storage::url($contact->icono_2) }}" class="h-16 w-auto object-contain" alt="Rejovot">
                @else
                    @include('partials.logo', ['variant' => 'blanco', 'class' => 'h-16 w-auto'])
                @endif
            </a>
        </div>

        <nav class="custom-scroll flex-1 space-y-0.5 overflow-y-auto px-3 py-3">
            <a href="{{ route('admin.catalogo.index') }}" class="nav-item {{ request()->routeIs('admin.catalogo.*') ? 'active-link' : '' }}">
                <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Productos
            </a>



            <a href="{{ route('admin.precios.index') }}" class="nav-item {{ request()->routeIs('admin.precios.*') ? 'active-link' : '' }}">
                <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z"/></svg>
                Listas de precios
            </a>

            <a href="{{ route('admin.pagos.cuentas') }}" class="nav-item {{ request()->routeIs('admin.pagos.cuentas*') ? 'active-link' : '' }}">
                <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M5 6h14a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Zm2 9h4"/></svg>
                Cuentas bancarias
            </a>

            <a href="{{ route('admin.pagos.comprobantes.index') }}" class="nav-item {{ request()->routeIs('admin.pagos.comprobantes.*') ? 'active-link' : '' }}">
                <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 4h7l5 5v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/></svg>
                Cuenta corriente
            </a>

            <a href="{{ route('admin.clientes.index') }}" class="nav-item {{ request()->routeIs('admin.clientes.*') ? 'active-link' : '' }}">
                <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM7 10a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z"/></svg>
                Clientes
            </a>

            <a href="{{ route('admin.vendedores.index') }}" class="nav-item {{ request()->routeIs('admin.vendedores.*') ? 'active-link' : '' }}">
                <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Zm-4 7a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7Z"/></svg>
                Vendedores
            </a>

            <div class="sidebar-divider my-2 h-px"></div>

                        <a href="{{ route('admin.contacto') }}" class="nav-item {{ request()->routeIs('admin.contacto') ? 'active-link' : '' }}">
                <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Contacto y logos
            </a>

            <a href="{{ route('admin.newsletter.index') }}" class="nav-item {{ request()->routeIs('admin.newsletter.*') ? 'active-link' : '' }}">
                <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M22 6 12 13 2 6m0 0v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/></svg>
                Newsletter
            </a>

            <a href="{{ route('usuarios.index') }}" class="nav-item {{ request()->routeIs('usuarios.*') ? 'active-link' : '' }}">
                <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Usuarios
            </a>

            <a href="{{ route('admin.metadata') }}" class="nav-item {{ request()->routeIs('admin.metadata*') ? 'active-link' : '' }}">
                <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                Metadata SEO
            </a>

            <div class="sidebar-divider my-2 h-px"></div>

            <a href="{{ route('productos') }}" class="nav-item">
                <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                Ver el sitio
            </a>
        </nav>

        <div class="sidebar-logout shrink-0 border-t p-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn w-full justify-center bg-[#E11A22] py-2.5 text-white hover:brightness-110">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    <header class="admin-mobile-header fixed inset-x-0 top-0 z-20 flex items-center justify-between border-b px-4 py-3 lg:hidden">
        <button @click="sidebarOpen = !sidebarOpen" class="rounded-lg p-1.5" aria-label="Menú">
            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <span class="text-sm font-bold text-white">Panel Rejovot</span>
        <a href="{{ route('productos') }}" class="rounded-lg p-1.5" title="Ver sitio">
            <svg class="h-5 w-5 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        </a>
    </header>

    <main class="min-h-screen bg-slate-50 lg:ml-64">
        <div class="animate-fadeIn px-5 pb-5 pt-20 lg:px-7 lg:pb-7 lg:pt-7">
            @yield('content')
            {{ $slot ?? '' }}
        </div>
    </main>

    @if(auth()->check() && auth()->user()->role === 'viewer')
        <div class="fixed left-1/2 top-4 z-[100] -translate-x-1/2 rounded-full bg-[#002B56] px-4 py-2 text-sm font-medium text-white shadow-lg">
            Modo espectador: solo lectura
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.tbl-edit, .tbl-del, a.btn-primary').forEach(el => el.style.display = 'none');
                document.querySelectorAll('button.btn-primary').forEach(function (b) {
                    if (b.closest('.sidebar-logout')) return;
                    b.style.display = 'none';
                });
                document.querySelectorAll('form').forEach(function (form) {
                    if ((form.getAttribute('method') || 'get').toUpperCase() === 'GET') return;
                    if (form.closest('.sidebar-logout')) return;
                    form.addEventListener('submit', function (e) {
                        e.preventDefault();
                        alert('Tu usuario es de solo lectura y no puede realizar cambios.');
                    });
                });
            });
        </script>
    @endif

    @livewireScripts
</body>
</html>
