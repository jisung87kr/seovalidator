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

        <title>{{ config('app.name', 'Laravel') }}</title>

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

            /* Mobile-specific optimizations */
            @media (max-width: 768px) {
                .break-keep {
                    word-break: keep-all;
                    line-height: 1.6;
                }

                /* Touch-friendly interactive elements */
                button, a[href] {
                    min-height: 44px;
                    min-width: 44px;
                }

                /* Better focus states for mobile */
                button:focus, a:focus, input:focus, select:focus, textarea:focus {
                    outline: 3px solid #3b82f6;
                    outline-offset: 2px;
                }

                /* Prevent zoom on input focus */
                input[type="url"], input[type="text"], input[type="email"], input[type="password"], input[type="search"], textarea, select {
                    font-size: 16px;
                }

                /* Prevent horizontal scroll */
                body {
                    overflow-x: hidden;
                }

                /* Better form spacing */
                .form-container {
                    padding: 1rem;
                }
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 px-4 sm:px-0">
            <div>
                <a href="/">
                    <x-application-logo class="w-16 h-16 sm:w-20 sm:h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-4 sm:mt-6 px-4 sm:px-6 py-4 sm:py-6 bg-white shadow-md overflow-hidden sm:rounded-lg form-container">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
