<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-6R50J0Y4TK"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', 'G-6R50J0Y4TK');
        </script>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SEO Validator') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Mobile Optimization Styles -->
        <style>
            body {
                font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;
                word-break: keep-all;
            }

            @media (max-width: 768px) {
                .break-keep {
                    word-break: keep-all;
                    line-height: 1.6;
                }

                button, a[href] {
                    min-height: 44px;
                    min-width: 44px;
                }

                button:focus, a:focus, input:focus, select:focus, textarea:focus {
                    outline: 3px solid #3b82f6;
                    outline-offset: 2px;
                }

                input[type="url"], input[type="text"], input[type="email"], input[type="password"], input[type="search"], textarea, select {
                    font-size: 16px;
                }

                body {
                    overflow-x: hidden;
                }
            }
        </style>
    </head>
    <body class="font-sans antialiased bg-surface-muted text-content">
        <div class="min-h-screen flex flex-col justify-center items-center py-8 sm:py-12 px-4">
            <!-- Logo -->
            <a href="/" class="mb-8">
                <span class="text-2xl font-bold text-primary">SEO Validator</span>
            </a>

            <!-- Auth Card -->
            <div class="w-full max-w-md">
                <div class="bg-white rounded-2xl border border-border p-6 sm:p-8 shadow-card">
                    {{ $slot }}
                </div>

                <!-- Footer -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-content-muted">
                        &copy; {{ date('Y') }} SEO Validator. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </body>
</html>
