<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/bootstrap.css', 'resources/css/dashboard.css' ,'resources/js/crm.js'])

</head>
<body>
@include('inc.svg')
@include('inc.header-crm')

<!-- Page Content -->
<div class="container-fluid">
    <div class="row">
    @include('inc.sidebar')
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    {{ $slot }}
</main>
    </div>
</div>

@include('inc.modal-delete')
</body>

</html>
