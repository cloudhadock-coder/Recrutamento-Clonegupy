<?php

use App\Http\Middleware\DisableSsr;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetCurrentCompany;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'company' => SetCurrentCompany::class,
            'no-ssr' => DisableSsr::class,
        ]);

        // O tenant precisa estar definido antes do route model binding para o global scope valer.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: SetCurrentCompany::class);

        // Rotas do candidato: área /candidato e o fluxo de candidatura no portal.
        $isCandidateArea = fn (Request $request): bool => $request->routeIs('candidate.*', 'portal.*');

        $middleware->redirectGuestsTo(fn (Request $request): string => $isCandidateArea($request)
            ? route('candidate.login')
            : route('login'));

        $middleware->redirectUsersTo(fn (Request $request): string => $isCandidateArea($request)
            ? route('candidate.applications.index')
            : route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
