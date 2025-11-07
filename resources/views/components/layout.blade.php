<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    @stack('styles')
</head>
<body>
    <div class="wizard-container">
        <header class="wizard-header">
            <h1>{{ $title }}</h1>
        </header>

        <main class="wizard-content">
            {{ $slot }}
        </main>

        <footer class="wizard-footer">
            @stack('footer')
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
