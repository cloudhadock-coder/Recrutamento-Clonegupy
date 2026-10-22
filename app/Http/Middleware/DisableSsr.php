<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * O painel é privado e não precisa de SSR; o SSR fica reservado ao portal público.
 */
class DisableSsr
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        config(['inertia.ssr.enabled' => false]);

        return $next($request);
    }
}
