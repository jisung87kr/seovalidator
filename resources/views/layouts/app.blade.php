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
        @stack('scripts')
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
                /*button, a[href] {*/
                /*    min-height: 44px;*/
                /*    min-width: 44px;*/
                /*}*/

                /* Better focus states for mobile */
                button:focus, a:focus, input:focus, select:focus, textarea:focus {
                    outline: 3px solid #3b82f6;
                    outline-offset: 2px;
                }

                /* Prevent zoom on input focus */
                input[type="url"], input[type="text"], input[type="email"], input[type="password"], input[type="search"], textarea, select {
                    font-size: 16px;
                }

                /* Better tap targets */
                .mobile-menu a {
                    padding: 12px 16px;
                    display: block;
                }

                /* Smooth transitions */
                .mobile-menu {
                    transition: all 0.3s ease-in-out;
                }

                /* Prevent horizontal scroll */
                body {
                    overflow-x: hidden;
                }
            }

            /* Tablet optimizations */
            @media (min-width: 769px) and (max-width: 1024px) {
                .container {
                    padding-left: 1rem;
                    padding-right: 1rem;
                }
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                @yield('content')
            </main>
        </div>
    </body>
</html>
