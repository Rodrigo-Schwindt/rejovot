<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdmin::class,
            'viewer.readonly' => \App\Http\Middleware\ViewerReadonly::class,
        ]);

        // El sitio no tiene página de login: se vuelve al catálogo con el modal abierto.
        $middleware->redirectGuestsTo(fn () => route('productos', ['ingresar' => 1]));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Formulario abierto demasiado tiempo: se vuelve a él con un aviso en vez
        // de la pantalla «419 Page Expired». Livewire maneja sus propios pedidos.
        // Laravel ya lo convirtió en un HttpException 419 cuando llega acá.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson() || $request->hasHeader('X-Livewire')) {
                return null;
            }

            $aviso = 'La página estuvo abierta mucho tiempo y se venció. Probá de nuevo.';

            // En el modal de ingreso el aviso va como error del login, así se reabre.
            $campo = $request->routeIs('ingresar.post') ? 'login' : 'sesion';

            return redirect()->back()
                ->withInput($request->except(['_token', 'password']))
                ->withErrors([$campo => $aviso])
                ->with('sesion_vencida', $aviso);
        });
    })->create();
