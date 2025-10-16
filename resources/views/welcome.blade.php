<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>T.I.A.S</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>
                /* Minimal Tailwind classes for basic styling */
                .min-h-screen{min-height:100vh}
                .flex{display:flex}
                .justify-center{justify-content:center}
                .items-center{align-items:center}
                .absolute{position:absolute}
                .top-0{top:0}
                .right-0{right:0}
                .p-4{padding:1rem}
                .text-6xl{font-size:3.75rem;line-height:1}
                .font-bold{font-weight:700}
                .text-black{color:rgb(0 0 0)}
                .bg-white{background-color:rgb(255 255 255)}
            </style>
        @endif
    </head>
    <body class="bg-white">
        @if (Route::has('login'))
            <div class="absolute top-0 right-0 p-4">
                @auth
                    <a href="{{ route('selector') }}" class="text-black">Selector</a>
                @else
                    <a href="{{ route('login') }}" class="text-black">Log in</a>
                @endauth
            </div>
        @endif

        <div class="min-h-screen flex flex-col gap-12 justify-center items-center">
            <x-application-logo class="w-48 h-48 mb-12 text-gray-800" />
            <h1 class="text-6xl font-bold text-black">T.I.A.S</h1>
        </div>
    </body>
</html>
