<?php

namespace App\Http\Middleware;

use App\Support\CurrentCompany;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Define a empresa do recrutador autenticado como tenant do request.
 *
 * Roda antes do route model binding (ver bootstrap/app.php), então registros de
 * outras empresas nem chegam a ser encontrados: resultam em 404.
 */
class SetCurrentCompany
{
    public function __construct(private CurrentCompany $currentCompany) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');

        if ($user === null) {
            return $next($request);
        }

        $this->currentCompany->set($user->loadMissing('company')->company);

        try {
            return $next($request);
        } finally {
            $this->currentCompany->set(null);
        }
    }
}
