<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-800">
        <div class="min-h-screen flex flex-col items-center justify-center px-4 py-12">
            <div class="w-full max-w-3xl">
                <div class="flex items-center gap-3">
                    <x-application-logo class="h-12 w-12 text-indigo-600" />
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">{{ config('app.name') }}</h1>
                        <p class="text-sm text-gray-500">Roles, permissions, field-level access and an audit trail for Laravel, built from scratch.</p>
                    </div>
                </div>

                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    @foreach ([
                        ['Role-based access', 'Roles and permissions wired into Laravel\'s Gate, so @can, policies and the can middleware just work.'],
                        ['Field-level permissions', 'Only roles with products.edit-cost can see or change a product\'s cost price.'],
                        ['Audit log', 'Every create, update and delete stored with before/after values, user, IP and time.'],
                    ] as [$title, $body])
                        <div class="rounded-lg bg-white p-5 shadow-sm">
                            <h2 class="font-semibold text-gray-900">{{ $title }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ $body }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 rounded-lg bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="font-semibold text-gray-900">Try the demo accounts</h2>
                            <p class="text-sm text-gray-500">Created by <code class="text-xs">php artisan migrate --seed</code>. Password for each: <code class="text-xs">password</code></p>
                        </div>
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Go to dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Log in</a>
                        @endauth
                    </div>
                    <ul class="mt-4 divide-y divide-gray-100 text-sm">
                        <li class="flex justify-between py-2"><span class="font-mono">admin@example.com</span><span class="text-gray-500">Admin: everything</span></li>
                        <li class="flex justify-between py-2"><span class="font-mono">manager@example.com</span><span class="text-gray-500">Manager: edit products, read audit log, no cost</span></li>
                        <li class="flex justify-between py-2"><span class="font-mono">viewer@example.com</span><span class="text-gray-500">Viewer: read-only products</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </body>
</html>
