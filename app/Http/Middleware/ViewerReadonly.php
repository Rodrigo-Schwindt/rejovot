<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewerReadonly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === 'viewer' && ! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            abort(403, 'Tu usuario es de solo lectura y no puede realizar cambios.');
        }

        return $next($request);
    }
}
