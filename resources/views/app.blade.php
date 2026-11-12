
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="description" content="BlogVerse Pro adalah platform untuk menulis, membaca, dan berbagi gagasan. Temukan artikel menarik, inspirasi, serta perspektif baru dari komunitas penulis.">

    <meta name="theme-color" content="#0f172a">

    <title inertia>{{ config('app.name') }}</title>

    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @inertiaHead
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased">
    @inertia
</body>
</html>
