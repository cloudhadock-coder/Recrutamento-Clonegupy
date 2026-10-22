<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead

        {{-- Com SSR o título vem do <Head> da página; sem SSR, usa o nome da aplicação. --}}
        @if (empty($__inertiaSsrResponse))
            <title inertia>{{ config('app.name', 'Laravel') }}</title>
        @endif
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
