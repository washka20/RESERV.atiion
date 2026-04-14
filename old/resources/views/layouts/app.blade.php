<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="auto">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@docsearch/css@3">

        <!-- Scripts -->
        @vite(['resources/css/bootstrap.css' ,'resources/css/label.css', 'resources/js/app.js'])

    </head>
    <body>
        @include('inc.svg')
        @include('inc.header')

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>

        @include('inc.footer')
    </body>

</html>
