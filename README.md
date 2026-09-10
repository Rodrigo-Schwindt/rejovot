# Rejovot Autopartes

Laravel 12 + Livewire 4 + Tailwind 4.

El **catálogo** (productos, precios, stock, clientes, ofertas) va a venir de **Odoo**.
Mientras tanto está hardcodeado detrás de una interfaz, así que las vistas no se tocan
cuando llegue la conexión real.

El **admin** administra sólo lo que NO viene de Odoo: datos de contacto, logos, redes,
newsletter, metadata SEO y usuarios.

## Puesta en marcha

```bash
composer install
npm install
cp .env.example .env && php artisan key:generate   # DB_DATABASE=rejovot
php artisan migrate --seed
php artisan storage:link
npm run build      # o: npm run dev
php artisan serve
```

Usuario admin del seeder: `admin@rejovot.com.ar` / `rejovot2026` (cambiarlo).

## Rutas

| Ruta | Qué es |
|------|--------|
| `/` → `/productos` | Catálogo (única vista pública por ahora) |
| `/login` | Acceso al panel |
| `/admin/contacto` | Datos de contacto, logos y redes |
| `/admin/newsletter` | Suscriptores y envío de campañas |
| `/admin/metadata` | Metadata SEO por sección |
| `/admin/usuarios` | ABM de usuarios (admin / usuario / espectador) |

## Estructura

```
app/
  Contracts/CatalogoRepository.php      ← interfaz del catálogo (el seam con Odoo)
  Services/Catalogo/CatalogoDemo.php    ← datos hardcodeados de hoy
  Livewire/
    Footer.php, NewsletterForm.php      ← componentes globales
    Vistas/Productos/ProductosPage.php  ← vistas públicas
  Http/Controllers/<Seccion>/…          ← CRUDs del admin
  Http/Middleware/                      ← IsAdmin, ViewerReadonly
  Support/                              ← Seo, Precio

resources/views/
  layouts/            public.blade.php · admin.blade.php
  partials/           logo · seo · contact-info-item · site-toast
  livewire/
    footer · newsletter-form
    vistas/productos/ productos-page + partials/   ← vistas públicas
    contacto/ · newsletter/ · metadata/ · usuarios/ ← vistas del admin
  vendor/pagination/admin.blade.php
```

## Conectar Odoo

1. Crear `app/Services/Catalogo/CatalogoOdoo.php` implementando `CatalogoRepository`.
2. Cambiar el bind en `AppServiceProvider::register()`.

Las vistas y `ProductosPage` no cambian: reciben los mismos arrays
(`ofertas`, `clientes`, `filtros`, `productos`, `detalle`).

## Paleta

- Azul institucional `#0D2B5E` (profundo `#071B3D`)
- Rojo `#E11A22`
- Tipografía Montserrat
