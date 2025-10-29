<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes App</title>

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    @livewireStyles
</head>
<body class="bg-gray-100 font-sans text-gray-900">

    <header class="bg-blue-600 text-white py-4 shadow mb-6">
        <div class="container mx-auto text-xl font-bold">📝 Notes App</div>
    </header>

    <main class="container mx-auto px-4">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
