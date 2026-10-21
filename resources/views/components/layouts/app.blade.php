<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes — Your personal workspace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>
    <header class="site-header"><a class="brand" href="{{ route('notes') }}"><span class="brand-mark">N</span> Notes<span class="brand-detail">PERSONAL WORKSPACE</span></a>
        @auth <div class="account"><span>{{ auth()->user()->name }}</span><form action="{{ route('logout') }}" method="POST">@csrf<button class="quiet" type="submit">Sign out</button></form></div> @endauth
    </header>
    <main id="main">{{ $slot }}</main>
    <footer>Built with Laravel & Livewire · Private by design</footer>
    @livewireScripts
</body>
</html>
