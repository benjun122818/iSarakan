<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="cmyk">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Dorm Finder</title>

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

    <!-- Styles -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/daisyui@2.17.0/dist/full.css" rel="stylesheet" type="text/css" /> -->
    <!-- <link href="{{ asset('css/daisyui.css') }}" rel="stylesheet" type="text/css" /> -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src="{{ asset('/js/tinymce/tinymce.min.js') }}" referrerpolicy="origin"></script>
</head>

<body class="font-sans antialiased">
    <div class="bg-gray-100" id="app">
        @include('layouts.navigation')

        <!-- Page Heading -->

        <main-layout></main-layout>


    </div>
</body>

</html>